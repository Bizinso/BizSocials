<?php

declare(strict_types=1);

namespace Database\Seeders\Social;

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

/**
 * Seeder for SocialAccount model.
 *
 * Creates sample social accounts for each seeded workspace based on their business type.
 */
final class SocialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspaces = Workspace::all()->keyBy('slug');
        $users = User::all()->keyBy('email');

        // 1. Acme Corporation - Marketing Team
        $marketingWorkspace = $workspaces->get('marketing-team');
        if ($marketingWorkspace) {
            $connectedByUser = $users->get('john.admin@acmecorp.com') ?? User::first();

            // LinkedIn Company Page (connected)
            $this->createSocialAccount([
                'workspace_id' => $marketingWorkspace->id,
                'platform' => SocialPlatform::LINKEDIN,
                'platform_account_id' => 'li_acme_marketing_12345',
                'account_name' => 'Acme Corporation',
                'account_username' => 'acme-corporation',
                'profile_image_url' => 'https://media.licdn.com/dms/image/acme-logo.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(3),
                'token_expires_at' => now()->addDays(30),
                'metadata' => [
                    'organization_id' => 'urn:li:organization:12345',
                    'vanity_name' => 'acme-corporation',
                    'page_type' => 'company',
                ],
            ]);

            // Facebook Page (connected)
            $this->createSocialAccount([
                'workspace_id' => $marketingWorkspace->id,
                'platform' => SocialPlatform::FACEBOOK,
                'platform_account_id' => 'fb_acme_page_67890',
                'account_name' => 'Acme Corporation Official',
                'account_username' => 'AcmeCorporation',
                'profile_image_url' => 'https://graph.facebook.com/acme/picture',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(2),
                'token_expires_at' => now()->addDays(45),
                'metadata' => [
                    'page_id' => '67890123456',
                    'category' => 'Business',
                ],
            ]);

            // Instagram Business (connected)
            $this->createSocialAccount([
                'workspace_id' => $marketingWorkspace->id,
                'platform' => SocialPlatform::INSTAGRAM,
                'platform_account_id' => 'ig_acme_business_11111',
                'account_name' => 'Acme Corp',
                'account_username' => 'acmecorp',
                'profile_image_url' => 'https://instagram.com/acme/profile.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(1),
                'token_expires_at' => now()->addDays(60),
                'metadata' => [
                    'facebook_page_id' => '67890123456',
                    'account_type' => 'BUSINESS',
                    'followers_count' => 15000,
                ],
            ]);

            // Twitter (token_expired - for testing)
            $this->createSocialAccount([
                'workspace_id' => $marketingWorkspace->id,
                'platform' => SocialPlatform::TWITTER,
                'platform_account_id' => 'tw_acme_22222',
                'account_name' => 'Acme Corporation',
                'account_username' => 'AcmeCorp',
                'profile_image_url' => 'https://pbs.twimg.com/acme_profile.jpg',
                'status' => SocialAccountStatus::TOKEN_EXPIRED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(4),
                'token_expires_at' => now()->subDays(5),
                'metadata' => [
                    'user_id' => '22222333344',
                    'verified' => false,
                ],
            ]);
        }

        // 2. Acme Corporation - Sales Team
        $salesWorkspace = $workspaces->get('sales-team');
        if ($salesWorkspace) {
            $connectedByUser = $users->get('sarah.manager@acmecorp.com') ?? User::first();

            // LinkedIn Company Page (connected)
            $this->createSocialAccount([
                'workspace_id' => $salesWorkspace->id,
                'platform' => SocialPlatform::LINKEDIN,
                'platform_account_id' => 'li_acme_sales_33333',
                'account_name' => 'Acme Sales Team',
                'account_username' => 'acme-sales',
                'profile_image_url' => 'https://media.licdn.com/dms/image/acme-sales-logo.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(2),
                'token_expires_at' => now()->addDays(25),
                'metadata' => [
                    'organization_id' => 'urn:li:organization:33333',
                    'vanity_name' => 'acme-sales',
                    'page_type' => 'company',
                ],
            ]);
        }

        // 3. StartupXYZ - Main
        $startupWorkspace = $workspaces->get('main');
        if ($startupWorkspace && $startupWorkspace->tenant?->slug === 'startupxyz') {
            $connectedByUser = $users->get('alex@startupxyz.io') ?? User::first();

            // LinkedIn Company Page (connected)
            $this->createSocialAccount([
                'workspace_id' => $startupWorkspace->id,
                'platform' => SocialPlatform::LINKEDIN,
                'platform_account_id' => 'li_startupxyz_44444',
                'account_name' => 'StartupXYZ',
                'account_username' => 'startupxyz',
                'profile_image_url' => 'https://media.licdn.com/dms/image/startupxyz-logo.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subWeeks(6),
                'token_expires_at' => now()->addDays(50),
                'metadata' => [
                    'organization_id' => 'urn:li:organization:44444',
                    'vanity_name' => 'startupxyz',
                    'page_type' => 'company',
                ],
            ]);

            // Twitter (connected)
            $this->createSocialAccount([
                'workspace_id' => $startupWorkspace->id,
                'platform' => SocialPlatform::TWITTER,
                'platform_account_id' => 'tw_startupxyz_55555',
                'account_name' => 'StartupXYZ',
                'account_username' => 'StartupXYZ',
                'profile_image_url' => 'https://pbs.twimg.com/startupxyz_profile.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subWeeks(4),
                'token_expires_at' => now()->addDays(40),
                'metadata' => [
                    'user_id' => '55555666677',
                    'verified' => true,
                ],
            ]);
        }

        // 4. Fashion Brand Co - Brand Marketing
        $fashionBrandWorkspace = $workspaces->get('brand-marketing');
        if ($fashionBrandWorkspace) {
            $connectedByUser = $users->get('emma@fashionbrand.co') ?? User::first();

            // Instagram Business (connected)
            $this->createSocialAccount([
                'workspace_id' => $fashionBrandWorkspace->id,
                'platform' => SocialPlatform::INSTAGRAM,
                'platform_account_id' => 'ig_fashion_66666',
                'account_name' => 'Fashion Brand Co',
                'account_username' => 'fashionbrandco',
                'profile_image_url' => 'https://instagram.com/fashionbrandco/profile.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(5),
                'token_expires_at' => now()->addDays(55),
                'metadata' => [
                    'facebook_page_id' => '77777888899',
                    'account_type' => 'BUSINESS',
                    'followers_count' => 125000,
                ],
            ]);

            // Facebook Page (connected)
            $this->createSocialAccount([
                'workspace_id' => $fashionBrandWorkspace->id,
                'platform' => SocialPlatform::FACEBOOK,
                'platform_account_id' => 'fb_fashion_77777',
                'account_name' => 'Fashion Brand Co',
                'account_username' => 'FashionBrandCo',
                'profile_image_url' => 'https://graph.facebook.com/fashionbrandco/picture',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(5),
                'token_expires_at' => now()->addDays(35),
                'metadata' => [
                    'page_id' => '77777888899',
                    'category' => 'Brand',
                ],
            ]);
        }

        // 5. Sarah Lifestyle - Content Creation
        $sarahWorkspace = $workspaces->get('content-creation');
        if ($sarahWorkspace) {
            $connectedByUser = $users->get('sarah@sarahlifestyle.com') ?? User::first();

            // Instagram Business (connected)
            $this->createSocialAccount([
                'workspace_id' => $sarahWorkspace->id,
                'platform' => SocialPlatform::INSTAGRAM,
                'platform_account_id' => 'ig_sarah_88888',
                'account_name' => 'Sarah Lifestyle',
                'account_username' => 'sarahlifestyle',
                'profile_image_url' => 'https://instagram.com/sarahlifestyle/profile.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(8),
                'token_expires_at' => now()->addDays(20),
                'metadata' => [
                    'facebook_page_id' => '99999000011',
                    'account_type' => 'CREATOR',
                    'followers_count' => 350000,
                ],
            ]);

            // Twitter (connected)
            $this->createSocialAccount([
                'workspace_id' => $sarahWorkspace->id,
                'platform' => SocialPlatform::TWITTER,
                'platform_account_id' => 'tw_sarah_99999',
                'account_name' => 'Sarah Lifestyle',
                'account_username' => 'SarahLifestyle',
                'profile_image_url' => 'https://pbs.twimg.com/sarah_profile.jpg',
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now()->subMonths(6),
                'token_expires_at' => now()->addDays(45),
                'metadata' => [
                    'user_id' => '99999111122',
                    'verified' => true,
                ],
            ]);
        }

        $this->command->info('Social accounts seeded successfully.');
    }

    /**
     * Create a social account with the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createSocialAccount(array $attributes): SocialAccount
    {
        // Encrypt access token and refresh token
        $attributes['access_token_encrypted'] = Crypt::encryptString('test_access_token_' . $attributes['platform_account_id']);
        $attributes['refresh_token_encrypted'] = Crypt::encryptString('test_refresh_token_' . $attributes['platform_account_id']);

        return SocialAccount::firstOrCreate(
            [
                'workspace_id' => $attributes['workspace_id'],
                'platform' => $attributes['platform'],
                'platform_account_id' => $attributes['platform_account_id'],
            ],
            $attributes
        );
    }
}
