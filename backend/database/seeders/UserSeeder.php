<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\User\UserInvitationSeeder;
use Database\Seeders\User\UserSeeder as UserDataSeeder;
use Database\Seeders\User\UserSessionSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for User domain.
 *
 * Calls all user-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting User seeders...');

        // 1. Users first (base entity)
        $this->call(UserDataSeeder::class);

        // 2. User Sessions (depends on users)
        $this->call(UserSessionSeeder::class);

        // 3. User Invitations (depends on users)
        $this->call(UserInvitationSeeder::class);

        $this->command->info('User seeders completed successfully!');
    }
}
