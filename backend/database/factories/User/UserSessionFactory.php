<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Enums\User\DeviceType;
use App\Models\User;
use App\Models\User\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for UserSession model.
 *
 * @extends Factory<UserSession>
 */
final class UserSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UserSession>
     */
    protected $model = UserSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deviceType = fake()->randomElement(DeviceType::cases());
        $userAgent = $this->generateUserAgent($deviceType);

        return [
            'user_id' => User::factory(),
            'token_hash' => UserSession::hashToken(UserSession::generateToken()),
            'ip_address' => fake()->boolean(80) ? fake()->ipv4() : fake()->ipv6(),
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'location' => fake()->boolean(60) ? [
                'country' => fake()->countryCode(),
                'city' => fake()->city(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ] : null,
            'last_active_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'expires_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'created_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
        ];
    }

    /**
     * Generate a user agent string based on device type.
     */
    private function generateUserAgent(DeviceType $deviceType): string
    {
        return match ($deviceType) {
            DeviceType::DESKTOP => fake()->randomElement([
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            ]),
            DeviceType::MOBILE => fake()->randomElement([
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            ]),
            DeviceType::TABLET => fake()->randomElement([
                'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                'Mozilla/5.0 (Linux; Android 14; SM-X910) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ]),
            DeviceType::API => fake()->randomElement([
                'curl/8.4.0',
                'PostmanRuntime/7.36.0',
                'python-requests/2.31.0',
                'GuzzleHttp/7.8.1 curl/8.4.0 PHP/8.3.0',
            ]),
        };
    }

    /**
     * Set session as active (not expired).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->addDays(fake()->numberBetween(1, 14)),
        ]);
    }

    /**
     * Set session as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set device type to desktop.
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => DeviceType::DESKTOP,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
    }

    /**
     * Set device type to mobile.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => DeviceType::MOBILE,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        ]);
    }

    /**
     * Set device type to tablet.
     */
    public function tablet(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => DeviceType::TABLET,
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        ]);
    }

    /**
     * Set device type to API.
     */
    public function api(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => DeviceType::API,
            'user_agent' => 'curl/8.4.0',
        ]);
    }
}
