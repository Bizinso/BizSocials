<?php

declare(strict_types=1);

namespace Database\Seeders\User;

use App\Enums\User\DeviceType;
use App\Enums\User\UserStatus;
use App\Models\User;
use App\Models\User\UserSession;
use Illuminate\Database\Seeder;

/**
 * Seeder for UserSession model.
 *
 * Creates sample sessions for active users.
 */
final class UserSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active users
        $activeUsers = User::where('status', UserStatus::ACTIVE)->get();

        foreach ($activeUsers as $user) {
            // Create 1-3 sessions for each active user
            $sessionCount = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $sessionCount; $i++) {
                $deviceType = fake()->randomElement(DeviceType::cases());

                UserSession::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'ip_address' => fake()->ipv4(),
                    ],
                    [
                        'token_hash' => UserSession::hashToken(UserSession::generateToken()),
                        'user_agent' => $this->generateUserAgent($deviceType),
                        'device_type' => $deviceType,
                        'location' => [
                            'country' => fake()->countryCode(),
                            'city' => fake()->city(),
                            'latitude' => (float) fake()->latitude(),
                            'longitude' => (float) fake()->longitude(),
                        ],
                        'last_active_at' => now()->subMinutes(fake()->numberBetween(1, 60 * 24)),
                        'expires_at' => now()->addDays(fake()->numberBetween(1, 7)),
                        'created_at' => now()->subDays(fake()->numberBetween(1, 7)),
                    ]
                );
            }
        }

        $this->command->info('User sessions seeded successfully.');
    }

    /**
     * Generate a user agent string based on device type.
     */
    private function generateUserAgent(DeviceType $deviceType): string
    {
        return match ($deviceType) {
            DeviceType::DESKTOP => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            DeviceType::MOBILE => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            DeviceType::TABLET => 'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            DeviceType::API => 'curl/8.4.0',
        };
    }
}
