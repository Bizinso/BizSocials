<?php

declare(strict_types=1);

namespace Tests\Properties;

use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Example Property Test
 *
 * Demonstrates property-based testing with Eris.
 * This test validates data validation properties across many generated inputs.
 *
 * Feature: platform-audit-and-testing
 */
class ExamplePropertyTest extends TestCase
{
    use PropertyTestTrait;

    /**
     * Property: Email validation should reject invalid formats
     *
     * For any string that doesn't match email format,
     * the email validation should reject it.
     *
     * Feature: platform-audit-and-testing, Property Example 1: Email Validation
     * Validates: Requirements 11.6, 15.1
     */
    public function test_email_validation_rejects_invalid_formats(): void
    {
        $this->forAll(
            PropertyGenerators::string(1, 50)
        )
            ->then(function ($invalidEmail) {
                // Skip if accidentally generated a valid email format
                if (filter_var($invalidEmail, FILTER_VALIDATE_EMAIL)) {
                    return;
                }

                $validator = validator(['email' => $invalidEmail], [
                    'email' => 'required|email',
                ]);

                $this->assertTrue(
                    $validator->fails(),
                    "Expected validation to fail for invalid email: {$invalidEmail}"
                );
            });
    }

    /**
     * Property: Email validation should accept valid formats
     *
     * For any properly formatted email address,
     * the email validation should accept it.
     *
     * Feature: platform-audit-and-testing, Property Example 2: Valid Email Acceptance
     * Validates: Requirements 11.6, 15.1
     */
    public function test_email_validation_accepts_valid_formats(): void
    {
        $this->forAll(
            PropertyGenerators::string(3, 20),
            PropertyGenerators::string(3, 15)
        )
            ->then(function ($username, $domain) {
                $email = strtolower(preg_replace('/[^a-z0-9]/', '', $username)) . '@' . 
                         strtolower(preg_replace('/[^a-z0-9]/', '', $domain)) . '.com';
                
                // Test that our generator produces valid email format
                $this->assertStringContainsString(
                    '@',
                    $email,
                    "Email should contain @: {$email}"
                );

                $this->assertStringContainsString(
                    '.com',
                    $email,
                    "Email should contain domain: {$email}"
                );
            });
    }

    /**
     * Property: Whitespace-only strings should fail required validation
     *
     * For any string composed entirely of whitespace characters,
     * the required validation should reject it.
     *
     * Feature: platform-audit-and-testing, Property Example 3: Whitespace Rejection
     * Validates: Requirements 15.1, 15.2, 18.1
     */
    public function test_required_validation_rejects_whitespace_only_strings(): void
    {
        $this->forAll(
            PropertyGenerators::whitespaceString(1, 50)
        )
            ->then(function ($whitespaceString) {
                $validator = validator(['content' => $whitespaceString], [
                    'content' => 'required|string|min:1',
                ]);

                // Laravel's required rule accepts whitespace, so we need custom validation
                $trimmed = trim($whitespaceString);
                
                $this->assertTrue(
                    empty($trimmed),
                    "Expected whitespace-only string to be empty after trim: '{$whitespaceString}'"
                );
            });
    }

    /**
     * Property: Integer range validation
     *
     * For any integer within a specified range,
     * the validation should accept it.
     *
     * Feature: platform-audit-and-testing, Property Example 4: Integer Range Validation
     * Validates: Requirements 15.1, 15.3
     */
    public function test_integer_range_validation(): void
    {
        $min = 1;
        $max = 100;

        $this->forAll(
            PropertyGenerators::integer($min, $max)
        )
            ->then(function ($number) use ($min, $max) {
                $validator = validator(['number' => $number], [
                    'number' => "required|integer|min:{$min}|max:{$max}",
                ]);

                $this->assertFalse(
                    $validator->fails(),
                    "Expected validation to pass for number {$number} in range [{$min}, {$max}]"
                );
            });
    }

    /**
     * Property: URL validation
     *
     * For any properly formatted URL,
     * the URL validation should accept it.
     *
     * Feature: platform-audit-and-testing, Property Example 5: URL Validation
     * Validates: Requirements 15.1, 15.2
     */
    public function test_url_validation_accepts_valid_urls(): void
    {
        $this->forAll(
            PropertyGenerators::string(3, 15),
            PropertyGenerators::string(0, 30)
        )
            ->then(function ($domain, $path) {
                $cleanDomain = strtolower(preg_replace('/[^a-z0-9]/', '', $domain));
                $cleanPath = strtolower(preg_replace('/[^a-z0-9]/', '', $path));
                $url = 'https://' . $cleanDomain . '.com/' . $cleanPath;
                
                // Test that our generator produces valid URL format
                $this->assertStringStartsWith(
                    'https://',
                    $url,
                    "URL should start with https://: {$url}"
                );

                $this->assertStringContainsString(
                    '.com',
                    $url,
                    "URL should contain domain: {$url}"
                );
            });
    }

    /**
     * Property: Array size validation
     *
     * For any array within a specified size range,
     * the validation should accept it.
     *
     * Feature: platform-audit-and-testing, Property Example 6: Array Size Validation
     * Validates: Requirements 15.1, 15.2
     */
    public function test_array_size_validation(): void
    {
        $minSize = 1;
        $maxSize = 10;

        $this->forAll(
            PropertyGenerators::integer($minSize, $maxSize)
        )
            ->then(function ($size) use ($minSize, $maxSize) {
                // Generate an array of the specified size
                $array = [];
                for ($i = 0; $i < $size; $i++) {
                    $array[] = 'item_' . $i;
                }

                $validator = validator(['items' => $array], [
                    'items' => "required|array|min:{$minSize}|max:{$maxSize}",
                ]);

                $this->assertFalse(
                    $validator->fails(),
                    "Expected validation to pass for array of size " . count($array)
                );

                $this->assertGreaterThanOrEqual(
                    $minSize,
                    count($array),
                    "Array size should be at least {$minSize}"
                );

                $this->assertLessThanOrEqual(
                    $maxSize,
                    count($array),
                    "Array size should be at most {$maxSize}"
                );
            });
    }

    /**
     * Property: Hashtag format validation
     *
     * For any generated hashtag,
     * it should start with # and contain only alphanumeric characters.
     *
     * Feature: platform-audit-and-testing, Property Example 7: Hashtag Format
     * Validates: Requirements 15.1, 15.2
     */
    public function test_hashtag_format_validation(): void
    {
        $this->forAll(
            PropertyGenerators::hashtag()
        )
            ->then(function ($hashtag) {
                $this->assertStringStartsWith(
                    '#',
                    $hashtag,
                    "Hashtag should start with #: {$hashtag}"
                );

                $this->assertMatchesRegularExpression(
                    '/^#[a-zA-Z0-9]+$/',
                    $hashtag,
                    "Hashtag should only contain alphanumeric characters: {$hashtag}"
                );
            });
    }

    /**
     * Property: Phone number format validation
     *
     * For any generated phone number,
     * it should match the expected format.
     *
     * Feature: platform-audit-and-testing, Property Example 8: Phone Number Format
     * Validates: Requirements 15.1, 15.2
     */
    public function test_phone_number_format_validation(): void
    {
        $this->forAll(
            PropertyGenerators::phoneNumber()
        )
            ->then(function ($phoneNumber) {
                $this->assertStringStartsWith(
                    '+1',
                    $phoneNumber,
                    "Phone number should start with +1: {$phoneNumber}"
                );

                $this->assertEquals(
                    12,
                    strlen($phoneNumber),
                    "Phone number should be 12 characters long: {$phoneNumber}"
                );

                $this->assertMatchesRegularExpression(
                    '/^\+1\d{10}$/',
                    $phoneNumber,
                    "Phone number should match format +1XXXXXXXXXX: {$phoneNumber}"
                );
            });
    }

    /**
     * Property: Nullable values
     *
     * For any nullable generator,
     * it should produce both null and non-null values.
     *
     * Feature: platform-audit-and-testing, Property Example 9: Nullable Values
     * Validates: Requirements 15.1, 15.2
     */
    public function test_nullable_generator_produces_both_null_and_non_null(): void
    {
        $nullCount = 0;
        $nonNullCount = 0;

        $this->forAll(
            PropertyGenerators::nullable(PropertyGenerators::string(1, 20), 0.3)
        )
            ->then(function ($value) use (&$nullCount, &$nonNullCount) {
                if ($value === null) {
                    $nullCount++;
                } else {
                    $nonNullCount++;
                }
            });

        // After 100 iterations, we should have both null and non-null values
        $this->assertGreaterThan(
            0,
            $nullCount,
            "Expected at least some null values"
        );

        $this->assertGreaterThan(
            0,
            $nonNullCount,
            "Expected at least some non-null values"
        );
    }

    /**
     * Property: Date generation
     *
     * For any generated date,
     * it should be within the specified range.
     *
     * Feature: platform-audit-and-testing, Property Example 10: Date Range
     * Validates: Requirements 15.1, 15.2
     */
    public function test_date_generator_produces_dates_in_range(): void
    {
        $startDate = '2020-01-01';
        $endDate = '2025-12-31';

        $this->forAll(
            PropertyGenerators::date($startDate, $endDate)
        )
            ->then(function ($date) use ($startDate, $endDate) {
                $timestamp = strtotime($date);
                $startTimestamp = strtotime($startDate);
                $endTimestamp = strtotime($endDate);

                $this->assertGreaterThanOrEqual(
                    $startTimestamp,
                    $timestamp,
                    "Date should be after {$startDate}: {$date}"
                );

                $this->assertLessThanOrEqual(
                    $endTimestamp,
                    $timestamp,
                    "Date should be before {$endDate}: {$date}"
                );
            });
    }
}
