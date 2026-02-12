<?php

declare(strict_types=1);

namespace App\Enums\Analytics;

enum ActivityType: string
{
    // Content
    case POST_CREATED = 'post_created';
    case POST_EDITED = 'post_edited';
    case POST_DELETED = 'post_deleted';
    case POST_SCHEDULED = 'post_scheduled';
    case POST_PUBLISHED = 'post_published';
    case POST_CANCELLED = 'post_cancelled';
    case MEDIA_UPLOADED = 'media_uploaded';
    case MEDIA_DELETED = 'media_deleted';

    // Engagement
    case INBOX_VIEWED = 'inbox_viewed';
    case REPLY_SENT = 'reply_sent';
    case COMMENT_LIKED = 'comment_liked';
    case ITEM_ARCHIVED = 'item_archived';

    // Analytics
    case DASHBOARD_VIEWED = 'dashboard_viewed';
    case REPORT_GENERATED = 'report_generated';
    case REPORT_EXPORTED = 'report_exported';
    case REPORT_DOWNLOADED = 'report_downloaded';

    // Settings
    case ACCOUNT_CONNECTED = 'account_connected';
    case ACCOUNT_DISCONNECTED = 'account_disconnected';
    case SETTINGS_CHANGED = 'settings_changed';
    case TEAM_MEMBER_INVITED = 'team_member_invited';
    case TEAM_MEMBER_REMOVED = 'team_member_removed';

    // AI Features
    case AI_CAPTION_GENERATED = 'ai_caption_generated';
    case AI_HASHTAG_SUGGESTED = 'ai_hashtag_suggested';
    case AI_BEST_TIME_CHECKED = 'ai_best_time_checked';
    case AI_CONTENT_IMPROVED = 'ai_content_improved';

    // Auth
    case USER_LOGIN = 'user_login';
    case USER_LOGOUT = 'user_logout';

    public function category(): ActivityCategory
    {
        return match ($this) {
            self::POST_CREATED, self::POST_EDITED, self::POST_DELETED,
            self::MEDIA_UPLOADED, self::MEDIA_DELETED => ActivityCategory::CONTENT_CREATION,

            self::POST_SCHEDULED, self::POST_PUBLISHED,
            self::POST_CANCELLED => ActivityCategory::PUBLISHING,

            self::INBOX_VIEWED, self::REPLY_SENT,
            self::COMMENT_LIKED, self::ITEM_ARCHIVED => ActivityCategory::ENGAGEMENT,

            self::DASHBOARD_VIEWED, self::REPORT_GENERATED,
            self::REPORT_EXPORTED, self::REPORT_DOWNLOADED => ActivityCategory::ANALYTICS,

            self::ACCOUNT_CONNECTED, self::ACCOUNT_DISCONNECTED,
            self::SETTINGS_CHANGED, self::TEAM_MEMBER_INVITED,
            self::TEAM_MEMBER_REMOVED => ActivityCategory::SETTINGS,

            self::AI_CAPTION_GENERATED, self::AI_HASHTAG_SUGGESTED,
            self::AI_BEST_TIME_CHECKED, self::AI_CONTENT_IMPROVED => ActivityCategory::AI_FEATURES,

            self::USER_LOGIN, self::USER_LOGOUT => ActivityCategory::AUTHENTICATION,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::POST_CREATED => 'Post Created',
            self::POST_EDITED => 'Post Edited',
            self::POST_DELETED => 'Post Deleted',
            self::POST_SCHEDULED => 'Post Scheduled',
            self::POST_PUBLISHED => 'Post Published',
            self::POST_CANCELLED => 'Post Cancelled',
            self::MEDIA_UPLOADED => 'Media Uploaded',
            self::MEDIA_DELETED => 'Media Deleted',
            self::INBOX_VIEWED => 'Inbox Viewed',
            self::REPLY_SENT => 'Reply Sent',
            self::COMMENT_LIKED => 'Comment Liked',
            self::ITEM_ARCHIVED => 'Item Archived',
            self::DASHBOARD_VIEWED => 'Dashboard Viewed',
            self::REPORT_GENERATED => 'Report Generated',
            self::REPORT_EXPORTED => 'Report Exported',
            self::REPORT_DOWNLOADED => 'Report Downloaded',
            self::ACCOUNT_CONNECTED => 'Account Connected',
            self::ACCOUNT_DISCONNECTED => 'Account Disconnected',
            self::SETTINGS_CHANGED => 'Settings Changed',
            self::TEAM_MEMBER_INVITED => 'Team Member Invited',
            self::TEAM_MEMBER_REMOVED => 'Team Member Removed',
            self::AI_CAPTION_GENERATED => 'AI Caption Generated',
            self::AI_HASHTAG_SUGGESTED => 'AI Hashtag Suggested',
            self::AI_BEST_TIME_CHECKED => 'AI Best Time Checked',
            self::AI_CONTENT_IMPROVED => 'AI Content Improved',
            self::USER_LOGIN => 'User Login',
            self::USER_LOGOUT => 'User Logout',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::POST_CREATED => 'User created a new post',
            self::POST_EDITED => 'User edited an existing post',
            self::POST_DELETED => 'User deleted a post',
            self::POST_SCHEDULED => 'User scheduled a post for publishing',
            self::POST_PUBLISHED => 'Post was published to social media',
            self::POST_CANCELLED => 'User cancelled a scheduled post',
            self::MEDIA_UPLOADED => 'User uploaded media files',
            self::MEDIA_DELETED => 'User deleted media files',
            self::INBOX_VIEWED => 'User viewed the social inbox',
            self::REPLY_SENT => 'User sent a reply to a message',
            self::COMMENT_LIKED => 'User liked a comment',
            self::ITEM_ARCHIVED => 'User archived an inbox item',
            self::DASHBOARD_VIEWED => 'User viewed the analytics dashboard',
            self::REPORT_GENERATED => 'User generated an analytics report',
            self::REPORT_EXPORTED => 'User exported an analytics report',
            self::REPORT_DOWNLOADED => 'User downloaded a report file',
            self::ACCOUNT_CONNECTED => 'User connected a social account',
            self::ACCOUNT_DISCONNECTED => 'User disconnected a social account',
            self::SETTINGS_CHANGED => 'User changed settings',
            self::TEAM_MEMBER_INVITED => 'User invited a team member',
            self::TEAM_MEMBER_REMOVED => 'User removed a team member',
            self::AI_CAPTION_GENERATED => 'User generated an AI caption',
            self::AI_HASHTAG_SUGGESTED => 'User received AI hashtag suggestions',
            self::AI_BEST_TIME_CHECKED => 'User checked AI best posting time',
            self::AI_CONTENT_IMPROVED => 'User improved content using AI',
            self::USER_LOGIN => 'User logged in',
            self::USER_LOGOUT => 'User logged out',
        };
    }

    public function isContentActivity(): bool
    {
        return $this->category() === ActivityCategory::CONTENT_CREATION;
    }

    public function isAiActivity(): bool
    {
        return $this->category() === ActivityCategory::AI_FEATURES;
    }
}
