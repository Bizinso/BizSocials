<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Enums\Support\CannedResponseCategory;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCannedResponse;
use Illuminate\Database\Seeder;

/**
 * Seeder for SupportCannedResponse.
 *
 * Creates default canned responses for support staff.
 */
final class SupportCannedResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Support Canned Responses...');

        $admin = SuperAdminUser::first();

        if (!$admin) {
            $this->command->warn('No super admin found. Skipping canned responses seeding.');
            return;
        }

        $responses = $this->getResponses();

        foreach ($responses as $responseData) {
            SupportCannedResponse::create([
                ...$responseData,
                'created_by' => $admin->id,
            ]);
        }

        $this->command->info('Support Canned Responses seeded successfully!');
    }

    /**
     * Get the list of canned responses to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getResponses(): array
    {
        return [
            // Greeting responses
            [
                'title' => 'Standard Greeting',
                'shortcut' => 'hi',
                'content' => "Hello {name},\n\nThank you for reaching out to our support team. I'm happy to help you today.\n\nI've reviewed your request and will provide a response shortly.",
                'category' => CannedResponseCategory::GREETING,
                'is_shared' => true,
            ],
            [
                'title' => 'Returning Customer Greeting',
                'shortcut' => 'hi-return',
                'content' => "Hello {name},\n\nWelcome back! I see you've been with us for a while, and we truly appreciate your continued support.\n\nHow can I assist you today?",
                'category' => CannedResponseCategory::GREETING,
                'is_shared' => true,
            ],

            // Technical responses
            [
                'title' => 'Request More Details',
                'shortcut' => 'need-info',
                'content' => "Thank you for reporting this issue. To help me investigate further, could you please provide:\n\n1. Steps to reproduce the issue\n2. Any error messages you're seeing\n3. Your browser and operating system\n4. Screenshots if available\n\nThis information will help me resolve your issue more quickly.",
                'category' => CannedResponseCategory::TECHNICAL,
                'is_shared' => true,
            ],
            [
                'title' => 'Clear Cache Instructions',
                'shortcut' => 'clear-cache',
                'content' => "This issue might be related to cached data. Please try clearing your browser cache:\n\n1. Press Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)\n2. Select 'Cached images and files'\n3. Click 'Clear data'\n4. Refresh the page and try again\n\nLet me know if this resolves your issue.",
                'category' => CannedResponseCategory::TECHNICAL,
                'is_shared' => true,
            ],
            [
                'title' => 'Bug Acknowledged',
                'shortcut' => 'bug-ack',
                'content' => "Thank you for reporting this bug. I've been able to reproduce the issue and have escalated it to our development team.\n\nI'll keep you updated on the progress. In the meantime, here's a workaround you can try: {workaround}",
                'category' => CannedResponseCategory::BUG_REPORT,
                'is_shared' => true,
            ],

            // Billing responses
            [
                'title' => 'Invoice Sent',
                'shortcut' => 'invoice-sent',
                'content' => "I've just sent a copy of your invoice to {email}. Please allow a few minutes for it to arrive.\n\nIf you don't see it, please check your spam folder. Let me know if you have any questions about the charges.",
                'category' => CannedResponseCategory::BILLING,
                'is_shared' => true,
            ],
            [
                'title' => 'Refund Processed',
                'shortcut' => 'refund',
                'content' => "Great news! I've processed your refund of {amount}. It should appear in your account within 5-10 business days, depending on your bank.\n\nIs there anything else I can help you with?",
                'category' => CannedResponseCategory::BILLING,
                'is_shared' => true,
            ],
            [
                'title' => 'Subscription Cancellation',
                'shortcut' => 'cancel-sub',
                'content' => "I've processed your cancellation request. Your subscription will remain active until {end_date}, and you'll have access to all features until then.\n\nWe're sorry to see you go. If you'd like to share any feedback about your experience, we'd really appreciate it.",
                'category' => CannedResponseCategory::BILLING,
                'is_shared' => true,
            ],

            // Account responses
            [
                'title' => 'Password Reset Sent',
                'shortcut' => 'pwd-reset',
                'content' => "I've sent a password reset link to your registered email address. The link will expire in 24 hours.\n\nIf you don't receive the email within a few minutes, please check your spam folder. Let me know if you need any further assistance.",
                'category' => CannedResponseCategory::ACCOUNT,
                'is_shared' => true,
            ],
            [
                'title' => 'Account Unlocked',
                'shortcut' => 'unlock',
                'content' => "I've unlocked your account. You should now be able to log in with your regular credentials.\n\nFor security, I recommend changing your password after logging in. Let me know if you encounter any issues.",
                'category' => CannedResponseCategory::ACCOUNT,
                'is_shared' => true,
            ],

            // Feature request responses
            [
                'title' => 'Feature Request Noted',
                'shortcut' => 'feat-noted',
                'content' => "Thank you for this suggestion! I've logged your feature request with our product team.\n\nWhile I can't promise a timeline, we take customer feedback seriously when planning our roadmap. You can track the status of feature requests on our public roadmap at {roadmap_url}.",
                'category' => CannedResponseCategory::FEATURE_REQUEST,
                'is_shared' => true,
            ],
            [
                'title' => 'Feature Already Planned',
                'shortcut' => 'feat-planned',
                'content' => "Great news! This feature is already on our roadmap. You can track its progress at {roadmap_url}.\n\nWe don't have an exact release date yet, but we'll announce it through our changelog when it's ready.",
                'category' => CannedResponseCategory::FEATURE_REQUEST,
                'is_shared' => true,
            ],

            // Closing responses
            [
                'title' => 'Standard Closing',
                'shortcut' => 'bye',
                'content' => "Is there anything else I can help you with today? If not, I'll go ahead and close this ticket.\n\nFeel free to reach out anytime if you have more questions. Have a great day!",
                'category' => CannedResponseCategory::CLOSING,
                'is_shared' => true,
            ],
            [
                'title' => 'Closing with Survey',
                'shortcut' => 'bye-survey',
                'content' => "I hope I was able to resolve your issue today. If you have a moment, we'd really appreciate your feedback on this support experience.\n\nThank you for being a valued customer. Have a great day!",
                'category' => CannedResponseCategory::CLOSING,
                'is_shared' => true,
            ],
            [
                'title' => 'Follow-up Closing',
                'shortcut' => 'bye-followup',
                'content' => "I'll keep this ticket open and follow up with you in {days} days to make sure everything is working properly.\n\nIn the meantime, don't hesitate to reply to this ticket if you need anything else.",
                'category' => CannedResponseCategory::CLOSING,
                'is_shared' => true,
            ],

            // General responses
            [
                'title' => 'Escalation Notice',
                'shortcut' => 'escalate',
                'content' => "I'm going to escalate this issue to our senior support team for further investigation. They have more expertise in this area and will be better equipped to help you.\n\nYou should hear back within 24 hours. Thank you for your patience.",
                'category' => CannedResponseCategory::GENERAL,
                'is_shared' => true,
            ],
            [
                'title' => 'Apology for Delay',
                'shortcut' => 'sorry-delay',
                'content' => "I sincerely apologize for the delay in getting back to you. We've been experiencing higher than usual ticket volume.\n\nI've prioritized your request and am working on it now. Thank you for your patience and understanding.",
                'category' => CannedResponseCategory::GENERAL,
                'is_shared' => true,
            ],
        ];
    }
}
