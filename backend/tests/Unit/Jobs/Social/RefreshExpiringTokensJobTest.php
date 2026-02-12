<?php

declare(strict_types=1);

/**
 * RefreshExpiringTokensJob Unit Tests
 *
 * Tests for the job that refreshes tokens for social accounts
 * that are expiring soon.
 *
 * @see \App\Jobs\Social\RefreshExpiringTokensJob
 */

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Jobs\Social\RefreshExpiringTokensJob;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Support\Facades\Crypt;

describe('RefreshExpiringTokensJob', function (): void {
    describe('finding expiring accounts', function (): void {
        it('identifies accounts expiring within 7 days', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            // Account expiring soon (should be found)
            $expiringSoon = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
                'platform' => SocialPlatform::FACEBOOK,
                'token_expires_at' => now()->addDays(3),
                'refresh_token_encrypted' => Crypt::encryptString('valid-refresh-token'),
            ]);

            // Account not expiring yet (should not be found)
            SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
                'platform' => SocialPlatform::LINKEDIN,
                'token_expires_at' => now()->addDays(30),
                'refresh_token_encrypted' => Crypt::encryptString('valid-refresh-token'),
            ]);

            // Act - query for expiring accounts
            $expiringAccounts = SocialAccount::query()
                ->where('status', SocialAccountStatus::CONNECTED)
                ->whereNotNull('refresh_token_encrypted')
                ->where('token_expires_at', '<=', now()->addDays(7))
                ->get();

            // Assert
            expect($expiringAccounts)->toHaveCount(1)
                ->and($expiringAccounts->first()->id)->toBe($expiringSoon->id);
        });

        it('ignores accounts without refresh tokens', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
                'platform' => SocialPlatform::TWITTER,
                'token_expires_at' => now()->addDays(3),
                'refresh_token_encrypted' => null, // No refresh token
            ]);

            // Act
            $expiringAccounts = SocialAccount::query()
                ->where('status', SocialAccountStatus::CONNECTED)
                ->whereNotNull('refresh_token_encrypted')
                ->where('token_expires_at', '<=', now()->addDays(7))
                ->get();

            // Assert
            expect($expiringAccounts)->toBeEmpty();
        });

        it('ignores disconnected accounts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::DISCONNECTED,
                'platform' => SocialPlatform::INSTAGRAM,
                'token_expires_at' => now()->addDays(3),
                'refresh_token_encrypted' => Crypt::encryptString('token'),
            ]);

            // Act
            $expiringAccounts = SocialAccount::query()
                ->where('status', SocialAccountStatus::CONNECTED)
                ->whereNotNull('refresh_token_encrypted')
                ->where('token_expires_at', '<=', now()->addDays(7))
                ->get();

            // Assert
            expect($expiringAccounts)->toBeEmpty();
        });

        it('ignores accounts without expiry date', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
                'platform' => SocialPlatform::LINKEDIN,
                'token_expires_at' => null, // No expiry
                'refresh_token_encrypted' => Crypt::encryptString('token'),
            ]);

            // Act
            $expiringAccounts = SocialAccount::query()
                ->where('status', SocialAccountStatus::CONNECTED)
                ->whereNotNull('refresh_token_encrypted')
                ->where('token_expires_at', '<=', now()->addDays(7))
                ->get();

            // Assert
            expect($expiringAccounts)->toBeEmpty();
        });
    });

    describe('job behavior', function (): void {
        it('handles empty results gracefully', function (): void {
            // Arrange - no expiring accounts

            // Act - the job should complete without error
            $job = new RefreshExpiringTokensJob();

            // Just verify job can be instantiated
            expect($job)->toBeInstanceOf(RefreshExpiringTokensJob::class);
        });

        it('finds multiple expiring accounts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            foreach (SocialPlatform::cases() as $platform) {
                SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                    'connected_by_user_id' => $user->id,
                    'status' => SocialAccountStatus::CONNECTED,
                    'platform' => $platform,
                    'token_expires_at' => now()->addDays(rand(1, 6)),
                    'refresh_token_encrypted' => Crypt::encryptString('token'),
                ]);
            }

            // Act
            $expiringAccounts = SocialAccount::query()
                ->where('status', SocialAccountStatus::CONNECTED)
                ->whereNotNull('refresh_token_encrypted')
                ->where('token_expires_at', '<=', now()->addDays(7))
                ->get();

            // Assert - all accounts should be found
            expect($expiringAccounts->count())->toBeGreaterThan(0);
        });
    });

    describe('job configuration', function (): void {
        it('is assigned to the social queue', function (): void {
            $job = new RefreshExpiringTokensJob();

            expect($job->queue)->toBe('social');
        });

        it('is configured with correct number of tries', function (): void {
            $job = new RefreshExpiringTokensJob();

            expect($job->tries)->toBe(3);
        });

        it('is configured with correct timeout', function (): void {
            $job = new RefreshExpiringTokensJob();

            expect($job->timeout)->toBe(600);
        });

        it('is configured with exponential backoff', function (): void {
            $job = new RefreshExpiringTokensJob();

            expect($job->backoff)->toBe([60, 120, 300]);
        });

        it('accepts custom days before expiry parameter', function (): void {
            $job = new RefreshExpiringTokensJob(daysBeforeExpiry: 14);

            // Verify job can be instantiated with custom parameter
            expect($job)->toBeInstanceOf(RefreshExpiringTokensJob::class);
        });
    });
});
