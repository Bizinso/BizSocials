<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Enums\Support\SupportChannel;
use App\Enums\Support\SupportCommentType;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Models\Support\SupportTicketTag;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for SupportTicket.
 *
 * Creates sample support tickets with comments.
 */
final class SupportTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Support Tickets...');

        $tenant = Tenant::first();
        $user = User::first();
        $admin = SuperAdminUser::first();

        if (!$tenant || !$user) {
            $this->command->warn('No tenant or user found. Skipping ticket seeding.');
            return;
        }

        $tickets = $this->getTickets();

        foreach ($tickets as $ticketData) {
            $comments = $ticketData['comments'] ?? [];
            $tagSlugs = $ticketData['tags'] ?? [];
            unset($ticketData['comments'], $ticketData['tags']);

            // Find category by name if specified
            if (isset($ticketData['category_name'])) {
                $category = SupportCategory::where('name', $ticketData['category_name'])->first();
                $ticketData['category_id'] = $category?->id;
                unset($ticketData['category_name']);
            }

            $ticket = SupportTicket::create([
                ...$ticketData,
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'requester_email' => $user->email,
                'requester_name' => $user->name,
                'assigned_to' => $ticketData['status'] !== SupportTicketStatus::NEW ? $admin?->id : null,
            ]);

            // Attach tags
            if (!empty($tagSlugs)) {
                $tags = SupportTicketTag::whereIn('slug', $tagSlugs)->get();
                foreach ($tags as $tag) {
                    $ticket->tags()->attach($tag->id);
                    $tag->incrementUsageCount();
                }
            }

            // Create comments
            foreach ($comments as $commentData) {
                $isAdmin = $commentData['is_admin'] ?? false;
                unset($commentData['is_admin']);
                SupportTicketComment::create([
                    ...$commentData,
                    'ticket_id' => $ticket->id,
                    'admin_id' => $isAdmin ? $admin?->id : null,
                    'user_id' => !$isAdmin ? $user->id : null,
                    'author_name' => $isAdmin ? $admin?->name : $user->name,
                    'author_email' => $isAdmin ? $admin?->email : $user->email,
                ]);
                $ticket->increment('comment_count');
            }

            // Update category ticket count
            if ($ticket->category_id) {
                $ticket->category->incrementTicketCount();
            }
        }

        // Create some random tickets with factory
        SupportTicket::factory()
            ->count(5)
            ->forTenant($tenant)
            ->forUser($user)
            ->create();

        $this->command->info('Support Tickets seeded successfully!');
    }

    /**
     * Get the list of tickets to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTickets(): array
    {
        return [
            [
                'subject' => 'Cannot connect my Instagram account',
                'description' => "I'm trying to connect my Instagram business account but keep getting an authentication error. I've tried multiple times and checked that my Instagram account is properly set up as a business account.\n\nError message: 'Authentication failed. Please try again.'",
                'ticket_type' => SupportTicketType::PROBLEM,
                'priority' => SupportTicketPriority::HIGH,
                'status' => SupportTicketStatus::IN_PROGRESS,
                'channel' => SupportChannel::WEB_FORM,
                'category_name' => 'Integration Issues',
                'tags' => ['integration', 'high-priority'],
                'comments' => [
                    [
                        'content' => "Thank you for reaching out. I understand you're having trouble connecting your Instagram account.\n\nCould you please confirm:\n1. Is your Instagram account a Business or Creator account?\n2. Is your Instagram account linked to a Facebook Page?\n\nThis will help me troubleshoot the issue.",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => true,
                    ],
                    [
                        'content' => "Yes, it's a business account and it's linked to my company's Facebook page. I'm the admin on both accounts.",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => false,
                    ],
                ],
            ],
            [
                'subject' => 'Request for bulk scheduling feature',
                'description' => "We manage social media for multiple clients and it would be incredibly helpful to have a bulk scheduling feature. Ideally, we'd like to upload a CSV file with post content, images, and scheduled times.\n\nThis would save our team hours of manual work each week.",
                'ticket_type' => SupportTicketType::FEATURE_REQUEST,
                'priority' => SupportTicketPriority::MEDIUM,
                'status' => SupportTicketStatus::RESOLVED,
                'channel' => SupportChannel::IN_APP,
                'category_name' => 'Feature Requests',
                'tags' => ['feature-request'],
                'comments' => [
                    [
                        'content' => "Thank you for this excellent suggestion! Bulk scheduling is actually on our roadmap and we're planning to release it in Q2.\n\nI've added your use case to our product requirements. You can track the progress on our public roadmap.",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => true,
                    ],
                ],
            ],
            [
                'subject' => 'Invoice not received for January',
                'description' => "I haven't received my invoice for January. We need it for our accounting records. Could you please resend it to our billing email: accounting@example.com?",
                'ticket_type' => SupportTicketType::BILLING,
                'priority' => SupportTicketPriority::LOW,
                'status' => SupportTicketStatus::CLOSED,
                'channel' => SupportChannel::EMAIL,
                'category_name' => 'Invoice Questions',
                'tags' => ['billing', 'quick-win'],
                'comments' => [
                    [
                        'content' => "I've resent your January invoice to accounting@example.com. Please allow a few minutes for it to arrive.\n\nI also noticed your billing email wasn't set to this address in your account. Would you like me to update it so future invoices go directly to your accounting team?",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => true,
                    ],
                    [
                        'content' => "Yes please, that would be great! Thank you for the quick response.",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => false,
                    ],
                    [
                        'content' => "Done! I've updated your billing email. All future invoices will be sent to accounting@example.com.\n\nIs there anything else I can help you with?",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => true,
                    ],
                ],
            ],
            [
                'subject' => 'App crashes when uploading large images',
                'description' => "The web app crashes whenever I try to upload images larger than 5MB. The page freezes and I have to refresh. This has been happening for the past week.\n\nBrowser: Chrome 120\nOS: Windows 11",
                'ticket_type' => SupportTicketType::BUG_REPORT,
                'priority' => SupportTicketPriority::URGENT,
                'status' => SupportTicketStatus::OPEN,
                'channel' => SupportChannel::WEB_FORM,
                'category_name' => 'Bug Reports',
                'tags' => ['bug', 'urgent', 'needs-investigation'],
                'comments' => [
                    [
                        'content' => "Checking with dev team about recent image upload changes.",
                        'comment_type' => SupportCommentType::NOTE,
                        'is_internal' => true,
                        'is_admin' => true,
                    ],
                ],
            ],
            [
                'subject' => 'How to reset my password?',
                'description' => "I forgot my password and the reset email isn't arriving. Can you help me regain access to my account?",
                'ticket_type' => SupportTicketType::QUESTION,
                'priority' => SupportTicketPriority::MEDIUM,
                'status' => SupportTicketStatus::WAITING_CUSTOMER,
                'channel' => SupportChannel::CHAT,
                'category_name' => 'Password & Security',
                'tags' => ['account'],
                'comments' => [
                    [
                        'content' => "I've manually triggered a password reset email. Please check both your inbox and spam folder.\n\nThe reset link will expire in 24 hours. Let me know once you've received it.",
                        'comment_type' => SupportCommentType::REPLY,
                        'is_internal' => false,
                        'is_admin' => true,
                    ],
                ],
            ],
        ];
    }
}
