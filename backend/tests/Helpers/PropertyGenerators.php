<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Content\Post;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\ConstantGenerator;
use Eris\Generator\ElementsGenerator;
use Eris\Generator\IntegerGenerator;
use Eris\Generator\MapGenerator;
use Eris\Generator\SequenceGenerator;
use Eris\Generator\StringGenerator;
use Eris\Generator\TupleGenerator;

/**
 * Property Test Generators
 *
 * Provides reusable generators for property-based testing with Eris.
 * Includes generators for common data types and domain models.
 */
class PropertyGenerators
{
    /**
     * Default number of iterations for property tests.
     */
    public const DEFAULT_ITERATIONS = 100;

    /**
     * Generate a random string of specified length range.
     *
     * @param int $minLength Minimum string length
     * @param int $maxLength Maximum string length
     * @return Generator
     */
    public static function string(int $minLength = 1, int $maxLength = 255): Generator
    {
        return Generator\map(
            function ($length) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ';
                $result = '';
                for ($i = 0; $i < $length; $i++) {
                    $result .= $characters[rand(0, strlen($characters) - 1)];
                }
                return $result;
            },
            Generator\choose($minLength, $maxLength)
        );
    }

    /**
     * Generate a random alphanumeric string.
     *
     * @param int $minLength Minimum string length
     * @param int $maxLength Maximum string length
     * @return Generator
     */
    public static function alphanumeric(int $minLength = 1, int $maxLength = 255): Generator
    {
        return Generator\map(
            function ($length) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $result = '';
                for ($i = 0; $i < $length; $i++) {
                    $result .= $characters[rand(0, strlen($characters) - 1)];
                }
                return $result;
            },
            Generator\choose($minLength, $maxLength)
        );
    }

    /**
     * Generate a random email address.
     *
     * @return Generator
     */
    public static function email(): Generator
    {
        return Generator\map(
            function ($username, $domain) {
                return $username . '@' . $domain . '.com';
            },
            self::alphanumeric(3, 20),
            self::alphanumeric(3, 15)
        );
    }

    /**
     * Generate a random URL.
     *
     * @return Generator
     */
    public static function url(): Generator
    {
        return Generator\map(
            function ($protocol, $domain, $path) {
                return $protocol . '://' . $domain . '.com/' . $path;
            },
            Generator\elements('http', 'https'),
            self::alphanumeric(3, 15),
            self::alphanumeric(0, 30)
        );
    }

    /**
     * Generate a random integer within a range.
     *
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return Generator
     */
    public static function integer(int $min = 0, int $max = 1000): Generator
    {
        return Generator\choose($min, $max);
    }

    /**
     * Generate a random positive integer.
     *
     * @param int $max Maximum value
     * @return Generator
     */
    public static function positiveInteger(int $max = 1000): Generator
    {
        return Generator\choose(1, $max);
    }

    /**
     * Generate a random boolean.
     *
     * @return Generator\ElementsGenerator
     */
    public static function boolean(): Generator\ElementsGenerator
    {
        return Generator\elements(true, false);
    }

    /**
     * Generate a random array of specified size.
     *
     * @param Generator $elementGenerator Generator for array elements
     * @param int $minSize Minimum array size
     * @param int $maxSize Maximum array size
     * @return Generator
     */
    public static function array(Generator $elementGenerator, int $minSize = 0, int $maxSize = 10): Generator
    {
        return Generator\map(
            function ($size) use ($elementGenerator) {
                $result = [];
                for ($i = 0; $i < $size; $i++) {
                    $result[] = $elementGenerator->__invoke(0, mt_rand())->unbox();
                }
                return $result;
            },
            Generator\choose($minSize, $maxSize)
        );
    }

    /**
     * Generate a random associative array.
     *
     * @param array<string, Generator> $schema Schema defining keys and their generators
     * @return Generator
     */
    public static function associativeArray(array $schema): Generator
    {
        return Generator\map(
            function (...$values) use ($schema) {
                return array_combine(array_keys($schema), $values);
            },
            ...array_values($schema)
        );
    }

    /**
     * Generate a random date within a range.
     *
     * @param string $start Start date (Y-m-d format)
     * @param string $end End date (Y-m-d format)
     * @return Generator
     */
    public static function date(string $start = '2020-01-01', string $end = '2030-12-31'): Generator
    {
        $startTimestamp = strtotime($start);
        $endTimestamp = strtotime($end);

        return Generator\map(
            fn($timestamp) => date('Y-m-d H:i:s', $timestamp),
            Generator\choose($startTimestamp, $endTimestamp)
        );
    }

    /**
     * Generate a random future date.
     *
     * @param int $maxDaysInFuture Maximum days in the future
     * @return Generator
     */
    public static function futureDate(int $maxDaysInFuture = 365): Generator
    {
        return Generator\map(
            fn($days) => now()->addDays($days)->format('Y-m-d H:i:s'),
            Generator\choose(1, $maxDaysInFuture)
        );
    }

    /**
     * Generate a random past date.
     *
     * @param int $maxDaysInPast Maximum days in the past
     * @return Generator
     */
    public static function pastDate(int $maxDaysInPast = 365): Generator
    {
        return Generator\map(
            fn($days) => now()->subDays($days)->format('Y-m-d H:i:s'),
            Generator\choose(1, $maxDaysInPast)
        );
    }

    /**
     * Generate a random UUID.
     *
     * @return Generator
     */
    public static function uuid(): Generator
    {
        return Generator\map(
            fn() => \Illuminate\Support\Str::uuid()->toString(),
            Generator\constant(null)
        );
    }

    /**
     * Generate a random User model.
     *
     * @return Generator
     */
    public static function user(): Generator
    {
        return Generator\map(
            function ($email, $name, $status, $role) {
                return User::factory()->create([
                    'email' => $email,
                    'name' => $name,
                    'status' => $status,
                    'role_in_tenant' => $role,
                ]);
            },
            self::email(),
            self::string(3, 50),
            Generator\elements(...UserStatus::cases()),
            Generator\elements(...TenantRole::cases())
        );
    }

    /**
     * Generate a random Post model.
     *
     * @return Generator
     */
    public static function post(): Generator
    {
        return Generator\map(
            function ($content, $status, $type) {
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                return Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'content_text' => $content,
                    'status' => $status,
                    'post_type' => $type,
                ]);
            },
            self::string(1, 500),
            Generator\elements(...PostStatus::cases()),
            Generator\elements(...PostType::cases())
        );
    }

    /**
     * Generate a random Tenant model.
     *
     * @return Generator
     */
    public static function tenant(): Generator
    {
        return Generator\map(
            function ($name, $slug) {
                return Tenant::factory()->create([
                    'name' => $name,
                    'slug' => $slug,
                ]);
            },
            self::string(3, 50),
            self::alphanumeric(3, 30)
        );
    }

    /**
     * Generate a random Workspace model.
     *
     * @return Generator
     */
    public static function workspace(): Generator
    {
        return Generator\map(
            function ($name) {
                $tenant = Tenant::factory()->create();
                return Workspace::factory()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                ]);
            },
            self::string(3, 50)
        );
    }

    /**
     * Generate whitespace-only strings.
     *
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @return Generator
     */
    public static function whitespaceString(int $minLength = 1, int $maxLength = 50): Generator
    {
        return Generator\map(
            function ($length) {
                $whitespaceChars = [' ', "\t", "\n", "\r"];
                $result = '';
                for ($i = 0; $i < $length; $i++) {
                    $result .= $whitespaceChars[array_rand($whitespaceChars)];
                }
                return $result;
            },
            Generator\choose($minLength, $maxLength)
        );
    }

    /**
     * Generate a random hashtag.
     *
     * @return Generator
     */
    public static function hashtag(): Generator
    {
        return Generator\map(
            function ($length) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $tag = '';
                for ($i = 0; $i < $length; $i++) {
                    $tag .= $characters[rand(0, strlen($characters) - 1)];
                }
                return '#' . $tag;
            },
            Generator\choose(3, 20)
        );
    }

    /**
     * Generate an array of hashtags.
     *
     * @param int $minCount Minimum number of hashtags
     * @param int $maxCount Maximum number of hashtags
     * @return Generator
     */
    public static function hashtags(int $minCount = 0, int $maxCount = 10): Generator
    {
        return self::array(self::hashtag(), $minCount, $maxCount);
    }

    /**
     * Generate a random phone number.
     *
     * @return Generator
     */
    public static function phoneNumber(): Generator
    {
        return Generator\map(
            fn($number) => '+1' . str_pad((string)$number, 10, '0', STR_PAD_LEFT),
            Generator\choose(1000000000, 9999999999)
        );
    }

    /**
     * Generate a random timezone.
     *
     * @return Generator
     */
    public static function timezone(): Generator
    {
        $timezones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney'];
        return Generator\elements(...$timezones);
    }

    /**
     * Generate a random language code.
     *
     * @return Generator
     */
    public static function languageCode(): Generator
    {
        return Generator\elements('en', 'es', 'fr', 'de', 'it', 'pt', 'ja', 'zh', 'ar', 'hi');
    }

    /**
     * Generate a random JSON object.
     *
     * @return Generator
     */
    public static function jsonObject(): Generator
    {
        return Generator\map(
            function ($key1, $value1, $key2, $value2) {
                return [
                    $key1 => $value1,
                    $key2 => $value2,
                ];
            },
            self::alphanumeric(3, 10),
            self::string(1, 50),
            self::alphanumeric(3, 10),
            self::integer(0, 1000)
        );
    }

    /**
     * Generate a nullable value.
     *
     * @param Generator $generator Generator for non-null values
     * @param float $nullProbability Probability of generating null (0.0 to 1.0)
     * @return Generator
     */
    public static function nullable(Generator $generator, float $nullProbability = 0.3): Generator
    {
        return Generator\frequency(
            [(int)($nullProbability * 100), Generator\constant(null)],
            [(int)((1 - $nullProbability) * 100), $generator]
        );
    }

    /**
     * Get the default iteration count for property tests.
     *
     * @return int
     */
    public static function getDefaultIterations(): int
    {
        return self::DEFAULT_ITERATIONS;
    }
}
