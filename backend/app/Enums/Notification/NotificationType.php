<?php

declare(strict_types=1);

namespace App\Enums\Notification;

/**
 * NotificationType Enum
 *
 * Defines all notification types in the BizSocials platform.
 *
 * Content Management:
 * - POST_SUBMITTED: Post submitted for approval
 * - POST_APPROVED: Post approved by reviewer
 * - POST_REJECTED: Post rejected by reviewer
 * - POST_PUBLISHED: Post successfully published
 * - POST_FAILED: Post publishing failed
 * - POST_SCHEDULED: Post scheduled for future publishing
 *
 * Engagement:
 * - NEW_COMMENT: New comment on content
 * - NEW_MENTION: User mentioned in content or comment
 * - INBOX_ASSIGNED: Inbox item assigned to user
 *
 * Team Management:
 * - INVITATION_RECEIVED: User received invitation
 * - INVITATION_ACCEPTED: Invitation accepted by invitee
 * - MEMBER_ADDED: Member added to workspace
 * - MEMBER_REMOVED: Member removed from workspace
 * - ROLE_CHANGED: User role changed
 *
 * Billing:
 * - SUBSCRIPTION_CREATED: New subscription created
 * - SUBSCRIPTION_RENEWED: Subscription renewed
 * - SUBSCRIPTION_CANCELLED: Subscription cancelled
 * - PAYMENT_FAILED: Payment failed
 * - TRIAL_ENDING: Trial period ending soon
 * - TRIAL_ENDED: Trial period ended
 *
 * Social Accounts:
 * - ACCOUNT_CONNECTED: Social account connected
 * - ACCOUNT_DISCONNECTED: Social account disconnected
 * - ACCOUNT_TOKEN_EXPIRING: Account token expiring soon
 * - ACCOUNT_TOKEN_EXPIRED: Account token expired
 *
 * Support:
 * - TICKET_CREATED: Support ticket created
 * - TICKET_REPLIED: Support ticket received reply
 * - TICKET_RESOLVED: Support ticket resolved
 *
 * Data & Privacy:
 * - DATA_EXPORT_READY: Data export ready for download
 * - DATA_DELETION_SCHEDULED: Data deletion scheduled
 * - DATA_DELETION_COMPLETED: Data deletion completed
 *
 * System:
 * - SYSTEM_ANNOUNCEMENT: System-wide announcement
 * - MAINTENANCE_SCHEDULED: Scheduled maintenance notification
 */
enum NotificationType: string
{
    // Content Management
    case POST_SUBMITTED = 'post_submitted';
    case POST_APPROVED = 'post_approved';
    case POST_REJECTED = 'post_rejected';
    case POST_PUBLISHED = 'post_published';
    case POST_FAILED = 'post_failed';
    case POST_SCHEDULED = 'post_scheduled';

    // Engagement
    case NEW_COMMENT = 'new_comment';
    case NEW_MENTION = 'new_mention';
    case INBOX_ASSIGNED = 'inbox_assigned';

    // Team Management
    case INVITATION_RECEIVED = 'invitation_received';
    case INVITATION_ACCEPTED = 'invitation_accepted';
    case MEMBER_ADDED = 'member_added';
    case MEMBER_REMOVED = 'member_removed';
    case ROLE_CHANGED = 'role_changed';

    // Billing
    case SUBSCRIPTION_CREATED = 'subscription_created';
    case SUBSCRIPTION_RENEWED = 'subscription_renewed';
    case SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    case PAYMENT_FAILED = 'payment_failed';
    case TRIAL_ENDING = 'trial_ending';
    case TRIAL_ENDED = 'trial_ended';

    // Social Accounts
    case ACCOUNT_CONNECTED = 'account_connected';
    case ACCOUNT_DISCONNECTED = 'account_disconnected';
    case ACCOUNT_TOKEN_EXPIRING = 'account_token_expiring';
    case ACCOUNT_TOKEN_EXPIRED = 'account_token_expired';

    // Support
    case TICKET_CREATED = 'ticket_created';
    case TICKET_REPLIED = 'ticket_replied';
    case TICKET_RESOLVED = 'ticket_resolved';

    // Data & Privacy
    case DATA_EXPORT_READY = 'data_export_ready';
    case DATA_DELETION_SCHEDULED = 'data_deletion_scheduled';
    case DATA_DELETION_COMPLETED = 'data_deletion_completed';

    // Analytics & Reports
    case REPORT_READY = 'report_ready';
    case REPORT_FAILED = 'report_failed';

    // Platform (admin-triggered)
    case PLATFORM_SCOPE_CHANGED = 'platform_scope_changed';
    case PLATFORM_REAUTH_REQUIRED = 'platform_reauth_required';
    case PLATFORM_MAINTENANCE = 'platform_maintenance';

    // System
    case SYSTEM_ANNOUNCEMENT = 'system_announcement';
    case MAINTENANCE_SCHEDULED = 'maintenance_scheduled';

    /**
     * Get human-readable label for the notification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::POST_SUBMITTED => 'Post Submitted',
            self::POST_APPROVED => 'Post Approved',
            self::POST_REJECTED => 'Post Rejected',
            self::POST_PUBLISHED => 'Post Published',
            self::POST_FAILED => 'Post Failed',
            self::POST_SCHEDULED => 'Post Scheduled',
            self::NEW_COMMENT => 'New Comment',
            self::NEW_MENTION => 'New Mention',
            self::INBOX_ASSIGNED => 'Inbox Assigned',
            self::INVITATION_RECEIVED => 'Invitation Received',
            self::INVITATION_ACCEPTED => 'Invitation Accepted',
            self::MEMBER_ADDED => 'Member Added',
            self::MEMBER_REMOVED => 'Member Removed',
            self::ROLE_CHANGED => 'Role Changed',
            self::SUBSCRIPTION_CREATED => 'Subscription Created',
            self::SUBSCRIPTION_RENEWED => 'Subscription Renewed',
            self::SUBSCRIPTION_CANCELLED => 'Subscription Cancelled',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::TRIAL_ENDING => 'Trial Ending',
            self::TRIAL_ENDED => 'Trial Ended',
            self::ACCOUNT_CONNECTED => 'Account Connected',
            self::ACCOUNT_DISCONNECTED => 'Account Disconnected',
            self::ACCOUNT_TOKEN_EXPIRING => 'Account Token Expiring',
            self::ACCOUNT_TOKEN_EXPIRED => 'Account Token Expired',
            self::TICKET_CREATED => 'Ticket Created',
            self::TICKET_REPLIED => 'Ticket Replied',
            self::TICKET_RESOLVED => 'Ticket Resolved',
            self::DATA_EXPORT_READY => 'Data Export Ready',
            self::DATA_DELETION_SCHEDULED => 'Data Deletion Scheduled',
            self::DATA_DELETION_COMPLETED => 'Data Deletion Completed',
            self::REPORT_READY => 'Report Ready',
            self::REPORT_FAILED => 'Report Failed',
            self::PLATFORM_SCOPE_CHANGED => 'Platform Scope Changed',
            self::PLATFORM_REAUTH_REQUIRED => 'Platform Re-authorization Required',
            self::PLATFORM_MAINTENANCE => 'Platform Maintenance',
            self::SYSTEM_ANNOUNCEMENT => 'System Announcement',
            self::MAINTENANCE_SCHEDULED => 'Maintenance Scheduled',
        };
    }

    /**
     * Get the category for this notification type.
     */
    public function category(): string
    {
        return match ($this) {
            self::POST_SUBMITTED,
            self::POST_APPROVED,
            self::POST_REJECTED,
            self::POST_PUBLISHED,
            self::POST_FAILED,
            self::POST_SCHEDULED => 'content',

            self::NEW_COMMENT,
            self::NEW_MENTION,
            self::INBOX_ASSIGNED => 'engagement',

            self::INVITATION_RECEIVED,
            self::INVITATION_ACCEPTED,
            self::MEMBER_ADDED,
            self::MEMBER_REMOVED,
            self::ROLE_CHANGED => 'team',

            self::SUBSCRIPTION_CREATED,
            self::SUBSCRIPTION_RENEWED,
            self::SUBSCRIPTION_CANCELLED,
            self::PAYMENT_FAILED,
            self::TRIAL_ENDING,
            self::TRIAL_ENDED => 'billing',

            self::ACCOUNT_CONNECTED,
            self::ACCOUNT_DISCONNECTED,
            self::ACCOUNT_TOKEN_EXPIRING,
            self::ACCOUNT_TOKEN_EXPIRED => 'social',

            self::TICKET_CREATED,
            self::TICKET_REPLIED,
            self::TICKET_RESOLVED => 'support',

            self::DATA_EXPORT_READY,
            self::DATA_DELETION_SCHEDULED,
            self::DATA_DELETION_COMPLETED => 'data',

            self::REPORT_READY,
            self::REPORT_FAILED => 'analytics',

            self::PLATFORM_SCOPE_CHANGED,
            self::PLATFORM_REAUTH_REQUIRED,
            self::PLATFORM_MAINTENANCE => 'platform',

            self::SYSTEM_ANNOUNCEMENT,
            self::MAINTENANCE_SCHEDULED => 'system',
        };
    }

    /**
     * Check if this notification type requires immediate attention.
     */
    public function isUrgent(): bool
    {
        return in_array($this, [
            self::POST_FAILED,
            self::PAYMENT_FAILED,
            self::ACCOUNT_TOKEN_EXPIRED,
            self::TRIAL_ENDED,
            self::DATA_DELETION_SCHEDULED,
            self::PLATFORM_REAUTH_REQUIRED,
        ], true);
    }

    /**
     * Get default icon for this notification type.
     */
    public function defaultIcon(): string
    {
        return match ($this) {
            self::POST_SUBMITTED => 'document-check',
            self::POST_APPROVED => 'check-circle',
            self::POST_REJECTED => 'x-circle',
            self::POST_PUBLISHED => 'globe-alt',
            self::POST_FAILED => 'exclamation-triangle',
            self::POST_SCHEDULED => 'clock',
            self::NEW_COMMENT => 'chat-bubble-left',
            self::NEW_MENTION => 'at-symbol',
            self::INBOX_ASSIGNED => 'inbox',
            self::INVITATION_RECEIVED => 'envelope',
            self::INVITATION_ACCEPTED => 'user-plus',
            self::MEMBER_ADDED => 'user-group',
            self::MEMBER_REMOVED => 'user-minus',
            self::ROLE_CHANGED => 'shield-check',
            self::SUBSCRIPTION_CREATED => 'credit-card',
            self::SUBSCRIPTION_RENEWED => 'arrow-path',
            self::SUBSCRIPTION_CANCELLED => 'x-mark',
            self::PAYMENT_FAILED => 'exclamation-circle',
            self::TRIAL_ENDING => 'clock',
            self::TRIAL_ENDED => 'clock',
            self::ACCOUNT_CONNECTED => 'link',
            self::ACCOUNT_DISCONNECTED => 'link-slash',
            self::ACCOUNT_TOKEN_EXPIRING => 'key',
            self::ACCOUNT_TOKEN_EXPIRED => 'key',
            self::TICKET_CREATED => 'ticket',
            self::TICKET_REPLIED => 'chat-bubble-left-right',
            self::TICKET_RESOLVED => 'check-badge',
            self::DATA_EXPORT_READY => 'arrow-down-tray',
            self::DATA_DELETION_SCHEDULED => 'trash',
            self::DATA_DELETION_COMPLETED => 'trash',
            self::REPORT_READY => 'chart-bar',
            self::REPORT_FAILED => 'chart-bar',
            self::PLATFORM_SCOPE_CHANGED => 'shield-check',
            self::PLATFORM_REAUTH_REQUIRED => 'exclamation-triangle',
            self::PLATFORM_MAINTENANCE => 'wrench',
            self::SYSTEM_ANNOUNCEMENT => 'megaphone',
            self::MAINTENANCE_SCHEDULED => 'wrench',
        };
    }

    /**
     * Get all values as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get notification types by category.
     *
     * @return array<self>
     */
    public static function byCategory(string $category): array
    {
        return array_filter(
            self::cases(),
            fn (self $type): bool => $type->category() === $category
        );
    }
}
