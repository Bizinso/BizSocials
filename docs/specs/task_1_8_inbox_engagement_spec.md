# Task 1.8: Inbox & Engagement Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.8 Inbox & Engagement Migrations
- **Dependencies**: Task 1.7 (Content Migrations) - COMPLETED

---

## 1. Overview

This task implements the inbox management system for handling comments and mentions from connected social accounts. It also includes engagement metrics tracking for published posts.

### Entities to Implement
1. **InboxItem** - Comments and mentions from social platforms
2. **InboxReply** - Responses sent to comments
3. **PostMetricSnapshot** - Engagement metrics for published posts

---

## 2. Enums

### 2.1 InboxItemType Enum
**File**: `app/Enums/Inbox/InboxItemType.php`

```php
enum InboxItemType: string
{
    case COMMENT = 'comment';
    case MENTION = 'mention';

    public function label(): string;
    public function canReply(): bool;  // COMMENT only (platform dependent)
}
```

### 2.2 InboxItemStatus Enum
**File**: `app/Enums/Inbox/InboxItemStatus.php`

```php
enum InboxItemStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';
    case RESOLVED = 'resolved';
    case ARCHIVED = 'archived';

    public function label(): string;
    public function isActive(): bool;  // UNREAD, READ, RESOLVED (not ARCHIVED)
    public function canTransitionTo(InboxItemStatus $status): bool;
}
```

---

## 3. Migrations

### 3.1 Create Inbox Items Table
**File**: `database/migrations/2026_02_06_800001_create_inbox_items_table.php`

```php
Schema::create('inbox_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id');
    $table->uuid('social_account_id');
    $table->uuid('post_target_id')->nullable();  // If comment on our post
    $table->string('item_type', 20);  // InboxItemType
    $table->string('status', 20)->default('unread');  // InboxItemStatus
    $table->string('platform_item_id', 255);  // Platform's comment ID
    $table->string('platform_post_id', 255)->nullable();
    $table->string('author_name');
    $table->string('author_username', 100)->nullable();
    $table->string('author_profile_url', 500)->nullable();
    $table->string('author_avatar_url', 500)->nullable();
    $table->text('content_text');
    $table->timestamp('platform_created_at');
    $table->uuid('assigned_to_user_id')->nullable();
    $table->timestamp('assigned_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->uuid('resolved_by_user_id')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    // Unique constraint
    $table->unique(['social_account_id', 'platform_item_id'], 'inbox_items_unique');

    // Indexes
    $table->index('status');
    $table->index('item_type');
    $table->index('platform_created_at');
    $table->index(['workspace_id', 'status']);

    // Foreign keys
    $table->foreign('workspace_id')
        ->references('id')
        ->on('workspaces')
        ->cascadeOnDelete();

    $table->foreign('social_account_id')
        ->references('id')
        ->on('social_accounts')
        ->cascadeOnDelete();

    $table->foreign('post_target_id')
        ->references('id')
        ->on('post_targets')
        ->nullOnDelete();

    $table->foreign('assigned_to_user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('resolved_by_user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
});
```

### 3.2 Create Inbox Replies Table
**File**: `database/migrations/2026_02_06_800002_create_inbox_replies_table.php`

```php
Schema::create('inbox_replies', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('inbox_item_id');
    $table->uuid('replied_by_user_id');
    $table->text('content_text');  // Max 1000 chars
    $table->string('platform_reply_id', 255)->nullable();
    $table->timestamp('sent_at');
    $table->timestamp('failed_at')->nullable();
    $table->text('failure_reason')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('sent_at');

    // Foreign keys
    $table->foreign('inbox_item_id')
        ->references('id')
        ->on('inbox_items')
        ->cascadeOnDelete();

    $table->foreign('replied_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

### 3.3 Create Post Metric Snapshots Table
**File**: `database/migrations/2026_02_06_800003_create_post_metric_snapshots_table.php`

```php
Schema::create('post_metric_snapshots', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('post_target_id');
    $table->timestamp('captured_at');
    $table->integer('likes_count')->nullable();
    $table->integer('comments_count')->nullable();
    $table->integer('shares_count')->nullable();
    $table->integer('impressions_count')->nullable();
    $table->integer('reach_count')->nullable();
    $table->integer('clicks_count')->nullable();
    $table->decimal('engagement_rate', 8, 4)->nullable();
    $table->json('raw_response')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('captured_at');
    $table->index(['post_target_id', 'captured_at']);

    // Foreign key
    $table->foreign('post_target_id')
        ->references('id')
        ->on('post_targets')
        ->cascadeOnDelete();
});
```

---

## 4. Models

### 4.1 InboxItem Model
**File**: `app/Models/Inbox/InboxItem.php`

Key methods:
- Relationships: `workspace()`, `socialAccount()`, `postTarget()`, `assignedTo()`, `resolvedBy()`, `replies()`
- Scopes: `forWorkspace()`, `unread()`, `active()`, `withStatus()`, `ofType()`, `assignedTo()`, `needsArchiving()`
- Helpers: `isUnread()`, `isResolved()`, `isArchived()`, `canReply()`, `markAsRead()`, `markAsResolved()`, `archive()`, `reopen()`, `assignTo()`, `unassign()`, `getReplyCount()`

### 4.2 InboxReply Model
**File**: `app/Models/Inbox/InboxReply.php`

Key methods:
- Relationships: `inboxItem()`, `repliedBy()`
- Scopes: `forItem()`, `successful()`, `failed()`
- Helpers: `isSent()`, `hasFailed()`, `markAsSent()`, `markAsFailed()`

### 4.3 PostMetricSnapshot Model
**File**: `app/Models/Inbox/PostMetricSnapshot.php`

Key methods:
- Relationships: `postTarget()`
- Scopes: `forPostTarget()`, `inDateRange()`, `latest()`
- Helpers: `getTotalEngagement()`, `calculateEngagementRate()`

---

## 5. Factories & Seeders

### Factories
- `InboxItemFactory` - with states for types, statuses, assigned/resolved
- `InboxReplyFactory` - with states for sent/failed
- `PostMetricSnapshotFactory` - with states for various engagement levels

### Seeders
- `InboxItemSeeder` - Create sample comments/mentions for published posts
- `InboxReplySeeder` - Create sample replies
- `PostMetricSnapshotSeeder` - Create sample metrics
- `InboxSeeder` (Orchestrator)

---

## 6. Test Requirements

Create tests for:
- 2 enum tests (InboxItemType, InboxItemStatus)
- 3 model tests (InboxItem, InboxReply, PostMetricSnapshot)

---

## 7. Implementation Checklist

- [ ] Create InboxItemType enum
- [ ] Create InboxItemStatus enum
- [ ] Create inbox_items migration
- [ ] Create inbox_replies migration
- [ ] Create post_metric_snapshots migration
- [ ] Create InboxItem model
- [ ] Create InboxReply model
- [ ] Create PostMetricSnapshot model
- [ ] Create all factories
- [ ] Create all seeders
- [ ] Create all unit tests
- [ ] Run migrations and seeders
- [ ] All tests pass
