<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Models\Social\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Token Security Property Test
 *
 * Validates that all stored tokens are encrypted in the database.
 * This test ensures that sensitive OAuth tokens are never stored in plain text.
 *
 * Feature: platform-audit-and-testing
 */
class TokenSecurityPropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    /**
     * Property 5: Token Security Verification
     *
     * For any social media platform integration, if tokens are stored in the database,
     * they should be encrypted and not stored in plain text.
     *
     * Feature: platform-audit-and-testing, Property 5: Token Security Verification
     * Validates: Requirements 2.7
     */
    public function test_all_stored_tokens_are_encrypted(): void
    {
        $this->forAll(
            PropertyGenerators::string(32, 128), // access_token
            PropertyGenerators::nullable(PropertyGenerators::string(32, 128), 0.3) // refresh_token
        )
            ->then(function ($accessToken, $refreshToken) {
                // Create a social account with the generated tokens
                $account = SocialAccount::factory()->create([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                ]);

                // Retrieve the raw database record to check encryption
                $rawRecord = DB::table('social_accounts')
                    ->where('id', $account->id)
                    ->first();

                // Verify access token is encrypted in database
                $this->assertNotNull(
                    $rawRecord->access_token_encrypted,
                    'Access token encrypted field should not be null'
                );

                $this->assertNotEquals(
                    $accessToken,
                    $rawRecord->access_token_encrypted,
                    'Access token should not be stored in plain text in database'
                );

                // Verify the encrypted token can be decrypted back to original
                $decryptedAccessToken = Crypt::decryptString($rawRecord->access_token_encrypted);
                $this->assertEquals(
                    $accessToken,
                    $decryptedAccessToken,
                    'Decrypted access token should match original token'
                );

                // Verify refresh token is encrypted if present
                if ($refreshToken !== null) {
                    $this->assertNotNull(
                        $rawRecord->refresh_token_encrypted,
                        'Refresh token encrypted field should not be null when refresh token is provided'
                    );

                    $this->assertNotEquals(
                        $refreshToken,
                        $rawRecord->refresh_token_encrypted,
                        'Refresh token should not be stored in plain text in database'
                    );

                    // Verify the encrypted refresh token can be decrypted back to original
                    $decryptedRefreshToken = Crypt::decryptString($rawRecord->refresh_token_encrypted);
                    $this->assertEquals(
                        $refreshToken,
                        $decryptedRefreshToken,
                        'Decrypted refresh token should match original token'
                    );
                } else {
                    $this->assertNull(
                        $rawRecord->refresh_token_encrypted,
                        'Refresh token encrypted field should be null when no refresh token is provided'
                    );
                }

                // Verify the model accessor returns the decrypted token
                $this->assertEquals(
                    $accessToken,
                    $account->accessToken,
                    'Model accessor should return decrypted access token'
                );

                if ($refreshToken !== null) {
                    $this->assertEquals(
                        $refreshToken,
                        $account->refreshToken,
                        'Model accessor should return decrypted refresh token'
                    );
                }
            });
    }

    /**
     * Property: Token encryption is consistent across updates
     *
     * For any token update operation, the new tokens should be encrypted
     * and the old encrypted values should be replaced.
     *
     * Feature: platform-audit-and-testing, Property 5.1: Token Update Security
     * Validates: Requirements 2.7, 2.8
     */
    public function test_token_updates_maintain_encryption(): void
    {
        $this->forAll(
            PropertyGenerators::string(32, 128), // initial_access_token
            PropertyGenerators::string(32, 128), // new_access_token
            PropertyGenerators::nullable(PropertyGenerators::string(32, 128), 0.3), // initial_refresh_token
            PropertyGenerators::nullable(PropertyGenerators::string(32, 128), 0.3) // new_refresh_token
        )
            ->then(function ($initialAccessToken, $newAccessToken, $initialRefreshToken, $newRefreshToken) {
                // Create a social account with initial tokens
                $account = SocialAccount::factory()->create([
                    'access_token' => $initialAccessToken,
                    'refresh_token' => $initialRefreshToken,
                ]);

                // Get initial encrypted values
                $initialRawRecord = DB::table('social_accounts')
                    ->where('id', $account->id)
                    ->first();

                $initialEncryptedAccessToken = $initialRawRecord->access_token_encrypted;
                $initialEncryptedRefreshToken = $initialRawRecord->refresh_token_encrypted;

                // Update the tokens
                $account->updateTokens(
                    $newAccessToken,
                    $newRefreshToken,
                    now()->addDays(30)
                );

                // Get updated encrypted values
                $updatedRawRecord = DB::table('social_accounts')
                    ->where('id', $account->id)
                    ->first();

                // Verify new access token is encrypted and different from initial
                $this->assertNotEquals(
                    $newAccessToken,
                    $updatedRawRecord->access_token_encrypted,
                    'New access token should not be stored in plain text'
                );

                $this->assertNotEquals(
                    $initialEncryptedAccessToken,
                    $updatedRawRecord->access_token_encrypted,
                    'Encrypted access token should change after update'
                );

                // Verify new encrypted token decrypts to new value
                $decryptedNewAccessToken = Crypt::decryptString($updatedRawRecord->access_token_encrypted);
                $this->assertEquals(
                    $newAccessToken,
                    $decryptedNewAccessToken,
                    'Decrypted new access token should match the new token value'
                );

                // Verify refresh token encryption after update
                if ($newRefreshToken !== null) {
                    $this->assertNotEquals(
                        $newRefreshToken,
                        $updatedRawRecord->refresh_token_encrypted,
                        'New refresh token should not be stored in plain text'
                    );

                    $decryptedNewRefreshToken = Crypt::decryptString($updatedRawRecord->refresh_token_encrypted);
                    $this->assertEquals(
                        $newRefreshToken,
                        $decryptedNewRefreshToken,
                        'Decrypted new refresh token should match the new token value'
                    );
                } else {
                    $this->assertNull(
                        $updatedRawRecord->refresh_token_encrypted,
                        'Refresh token should be null when updated to null'
                    );
                }
            });
    }

    /**
     * Property: Tokens are never exposed in model serialization
     *
     * For any social account, the encrypted token fields should be hidden
     * when the model is serialized to array or JSON.
     *
     * Feature: platform-audit-and-testing, Property 5.2: Token Serialization Security
     * Validates: Requirements 2.7
     */
    public function test_encrypted_tokens_are_hidden_in_serialization(): void
    {
        $this->forAll(
            PropertyGenerators::string(32, 128), // access_token
            PropertyGenerators::nullable(PropertyGenerators::string(32, 128), 0.3) // refresh_token
        )
            ->then(function ($accessToken, $refreshToken) {
                // Create a social account with tokens
                $account = SocialAccount::factory()->create([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                ]);

                // Convert to array
                $accountArray = $account->toArray();

                // Verify encrypted fields are not in the array
                $this->assertArrayNotHasKey(
                    'access_token_encrypted',
                    $accountArray,
                    'Encrypted access token should not be exposed in array serialization'
                );

                $this->assertArrayNotHasKey(
                    'refresh_token_encrypted',
                    $accountArray,
                    'Encrypted refresh token should not be exposed in array serialization'
                );

                // Convert to JSON
                $accountJson = $account->toJson();
                $decodedJson = json_decode($accountJson, true);

                // Verify encrypted fields are not in the JSON
                $this->assertArrayNotHasKey(
                    'access_token_encrypted',
                    $decodedJson,
                    'Encrypted access token should not be exposed in JSON serialization'
                );

                $this->assertArrayNotHasKey(
                    'refresh_token_encrypted',
                    $decodedJson,
                    'Encrypted refresh token should not be exposed in JSON serialization'
                );

                // Verify the plain text tokens are also not exposed
                $this->assertArrayNotHasKey(
                    'access_token',
                    $accountArray,
                    'Plain text access token should not be exposed in array serialization'
                );

                $this->assertArrayNotHasKey(
                    'refresh_token',
                    $accountArray,
                    'Plain text refresh token should not be exposed in array serialization'
                );
            });
    }

    /**
     * Property: Metadata tokens are also encrypted
     *
     * For platforms like Facebook that store additional tokens in metadata
     * (e.g., page_access_token), those tokens should also be encrypted.
     *
     * Feature: platform-audit-and-testing, Property 5.3: Metadata Token Security
     * Validates: Requirements 2.7
     */
    public function test_metadata_tokens_are_encrypted(): void
    {
        $this->forAll(
            PropertyGenerators::string(32, 128) // page_access_token
        )
            ->then(function ($pageAccessToken) {
                // Create a Facebook account with page access token in metadata
                $account = SocialAccount::factory()->facebook()->create([
                    'metadata' => [
                        'page_id' => '123456789',
                        'page_access_token_encrypted' => Crypt::encryptString($pageAccessToken),
                        'category' => 'Business',
                    ],
                ]);

                // Retrieve the raw database record
                $rawRecord = DB::table('social_accounts')
                    ->where('id', $account->id)
                    ->first();

                // Decode the metadata JSON
                $metadata = json_decode($rawRecord->metadata, true);

                // Verify the page access token in metadata is encrypted
                $this->assertArrayHasKey(
                    'page_access_token_encrypted',
                    $metadata,
                    'Metadata should contain encrypted page access token'
                );

                $this->assertNotEquals(
                    $pageAccessToken,
                    $metadata['page_access_token_encrypted'],
                    'Page access token in metadata should not be stored in plain text'
                );

                // Verify it can be decrypted
                $decryptedPageToken = Crypt::decryptString($metadata['page_access_token_encrypted']);
                $this->assertEquals(
                    $pageAccessToken,
                    $decryptedPageToken,
                    'Decrypted page access token should match original'
                );
            });
    }
}
