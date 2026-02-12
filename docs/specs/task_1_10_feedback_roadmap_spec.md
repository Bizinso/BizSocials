# Task 1.10: Feedback & Roadmap Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.10 Feedback & Roadmap Migrations
- **Dependencies**: Task 1.3 (User & Auth), Task 1.2 (Tenant Management), Task 1.1 (Platform Admin)

---

## 1. Overview

This task implements the Feedback & Roadmap system for collecting user feedback, managing product roadmap, and publishing release notes. This is a platform-level feature managed by SuperAdmins.

### Entities to Implement
1. **Feedback** - User feedback submissions
2. **FeedbackVote** - Votes on feedback items
3. **FeedbackComment** - Comments on feedback
4. **FeedbackTag** - Tags for categorizing feedback
5. **FeedbackTagAssignment** - Pivot table for feedback-tag M:N
6. **RoadmapItem** - Public roadmap items
7. **RoadmapFeedbackLink** - Pivot linking roadmap to feedback
8. **ReleaseNote** - Release notes/changelog entries
9. **ReleaseNoteItem** - Individual items within a release
10. **ChangelogSubscription** - Email subscriptions for updates

---

## 2. Enums

### 2.1 FeedbackType Enum
**File**: `app/Enums/Feedback/FeedbackType.php`

```php
enum FeedbackType: string
{
    case FEATURE_REQUEST = 'feature_request';
    case IMPROVEMENT = 'improvement';
    case BUG_REPORT = 'bug_report';
    case INTEGRATION_REQUEST = 'integration_request';
    case UX_FEEDBACK = 'ux_feedback';
    case DOCUMENTATION = 'documentation';
    case PRICING_FEEDBACK = 'pricing_feedback';
    case OTHER = 'other';

    public function label(): string;
    public function icon(): string;
    public function color(): string;
}
```

### 2.2 FeedbackCategory Enum
**File**: `app/Enums/Feedback/FeedbackCategory.php`

```php
enum FeedbackCategory: string
{
    case PUBLISHING = 'publishing';
    case SCHEDULING = 'scheduling';
    case ANALYTICS = 'analytics';
    case INBOX = 'inbox';
    case TEAM_COLLABORATION = 'team_collaboration';
    case INTEGRATIONS = 'integrations';
    case MOBILE_APP = 'mobile_app';
    case API = 'api';
    case BILLING = 'billing';
    case ONBOARDING = 'onboarding';
    case GENERAL = 'general';

    public function label(): string;
}
```

### 2.3 FeedbackStatus Enum
**File**: `app/Enums/Feedback/FeedbackStatus.php`

```php
enum FeedbackStatus: string
{
    case NEW = 'new';
    case UNDER_REVIEW = 'under_review';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case SHIPPED = 'shipped';
    case DECLINED = 'declined';
    case DUPLICATE = 'duplicate';
    case ARCHIVED = 'archived';

    public function label(): string;
    public function isOpen(): bool;  // NEW, UNDER_REVIEW
    public function isActive(): bool;  // PLANNED, IN_PROGRESS
    public function isClosed(): bool;  // SHIPPED, DECLINED, DUPLICATE, ARCHIVED
    public function canTransitionTo(FeedbackStatus $status): bool;
}
```

### 2.4 UserPriority Enum
**File**: `app/Enums/Feedback/UserPriority.php`

```php
enum UserPriority: string
{
    case NICE_TO_HAVE = 'nice_to_have';
    case IMPORTANT = 'important';
    case CRITICAL = 'critical';

    public function label(): string;
    public function weight(): int;  // 1, 2, 3
}
```

### 2.5 AdminPriority Enum
**File**: `app/Enums/Feedback/AdminPriority.php`

```php
enum AdminPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string;
    public function weight(): int;  // 1, 2, 3, 4
}
```

### 2.6 EffortEstimate Enum
**File**: `app/Enums/Feedback/EffortEstimate.php`

```php
enum EffortEstimate: string
{
    case XS = 'xs';
    case S = 's';
    case M = 'm';
    case L = 'l';
    case XL = 'xl';

    public function label(): string;
    public function description(): string;  // Hours/days estimate
}
```

### 2.7 FeedbackSource Enum
**File**: `app/Enums/Feedback/FeedbackSource.php`

```php
enum FeedbackSource: string
{
    case PORTAL = 'portal';
    case WIDGET = 'widget';
    case EMAIL = 'email';
    case SUPPORT_TICKET = 'support_ticket';
    case INTERNAL = 'internal';

    public function label(): string;
}
```

### 2.8 VoteType Enum
**File**: `app/Enums/Feedback/VoteType.php`

```php
enum VoteType: string
{
    case UPVOTE = 'upvote';
    case DOWNVOTE = 'downvote';

    public function label(): string;
    public function value(): int;  // +1 or -1
}
```

### 2.9 RoadmapCategory Enum
**File**: `app/Enums/Feedback/RoadmapCategory.php`

```php
enum RoadmapCategory: string
{
    case PUBLISHING = 'publishing';
    case SCHEDULING = 'scheduling';
    case ANALYTICS = 'analytics';
    case INBOX = 'inbox';
    case TEAM_COLLABORATION = 'team_collaboration';
    case INTEGRATIONS = 'integrations';
    case MOBILE_APP = 'mobile_app';
    case API = 'api';
    case PLATFORM = 'platform';
    case SECURITY = 'security';
    case PERFORMANCE = 'performance';

    public function label(): string;
    public function color(): string;
}
```

### 2.10 RoadmapStatus Enum
**File**: `app/Enums/Feedback/RoadmapStatus.php`

```php
enum RoadmapStatus: string
{
    case CONSIDERING = 'considering';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case BETA = 'beta';
    case SHIPPED = 'shipped';
    case CANCELLED = 'cancelled';

    public function label(): string;
    public function isActive(): bool;  // PLANNED, IN_PROGRESS, BETA
    public function isPublic(): bool;  // Not CANCELLED
    public function canTransitionTo(RoadmapStatus $status): bool;
}
```

### 2.11 ReleaseType Enum
**File**: `app/Enums/Feedback/ReleaseType.php`

```php
enum ReleaseType: string
{
    case MAJOR = 'major';
    case MINOR = 'minor';
    case PATCH = 'patch';
    case HOTFIX = 'hotfix';
    case BETA = 'beta';
    case ALPHA = 'alpha';

    public function label(): string;
    public function badge(): string;
}
```

### 2.12 ReleaseNoteStatus Enum
**File**: `app/Enums/Feedback/ReleaseNoteStatus.php`

```php
enum ReleaseNoteStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';

    public function label(): string;
    public function isVisible(): bool;  // PUBLISHED only
}
```

### 2.13 ChangeType Enum
**File**: `app/Enums/Feedback/ChangeType.php`

```php
enum ChangeType: string
{
    case NEW_FEATURE = 'new_feature';
    case IMPROVEMENT = 'improvement';
    case BUG_FIX = 'bug_fix';
    case SECURITY = 'security';
    case PERFORMANCE = 'performance';
    case DEPRECATION = 'deprecation';
    case BREAKING_CHANGE = 'breaking_change';

    public function label(): string;
    public function icon(): string;
    public function color(): string;
}
```

---

## 3. Migrations

### 3.1 Create Feedback Table
**File**: `database/migrations/2026_02_06_1000001_create_feedback_table.php`

```php
Schema::create('feedback', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    $table->uuid('user_id')->nullable();
    $table->string('submitter_email', 255)->nullable();
    $table->string('submitter_name', 100)->nullable();
    $table->string('title', 255);
    $table->text('description');
    $table->string('feedback_type', 30);  // FeedbackType
    $table->string('category', 30)->nullable();  // FeedbackCategory
    $table->string('user_priority', 20)->default('important');  // UserPriority
    $table->text('business_impact')->nullable();
    $table->string('admin_priority', 20)->nullable();  // AdminPriority
    $table->string('effort_estimate', 5)->nullable();  // EffortEstimate
    $table->string('status', 20)->default('new');  // FeedbackStatus
    $table->text('status_reason')->nullable();
    $table->integer('vote_count')->default(0);
    $table->uuid('roadmap_item_id')->nullable();
    $table->uuid('duplicate_of_id')->nullable();
    $table->string('source', 20)->default('portal');  // FeedbackSource
    $table->json('browser_info')->nullable();
    $table->string('page_url', 500)->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->uuid('reviewed_by')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('status');
    $table->index('feedback_type');
    $table->index('category');
    $table->index(['vote_count', 'id'], 'feedback_votes_idx');
    $table->index('tenant_id');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('duplicate_of_id')
        ->references('id')
        ->on('feedback')
        ->nullOnDelete();

    $table->foreign('reviewed_by')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.2 Create Feedback Votes Table
**File**: `database/migrations/2026_02_06_1000002_create_feedback_votes_table.php`

```php
Schema::create('feedback_votes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('feedback_id');
    $table->uuid('user_id')->nullable();
    $table->uuid('tenant_id')->nullable();
    $table->string('voter_email', 255)->nullable();
    $table->string('session_id', 100)->nullable();
    $table->string('vote_type', 10)->default('upvote');  // VoteType
    $table->timestamps();

    // Unique constraints
    $table->unique(['feedback_id', 'user_id'], 'feedback_votes_user_unique');
    $table->unique(['feedback_id', 'session_id'], 'feedback_votes_session_unique');

    // Indexes
    $table->index('feedback_id');

    // Foreign keys
    $table->foreign('feedback_id')
        ->references('id')
        ->on('feedback')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();
});
```

### 3.3 Create Feedback Comments Table
**File**: `database/migrations/2026_02_06_1000003_create_feedback_comments_table.php`

```php
Schema::create('feedback_comments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('feedback_id');
    $table->uuid('user_id')->nullable();
    $table->uuid('admin_id')->nullable();
    $table->string('commenter_name', 100)->nullable();
    $table->text('content');
    $table->boolean('is_internal')->default(false);
    $table->boolean('is_official_response')->default(false);
    $table->timestamps();

    // Indexes
    $table->index('feedback_id');

    // Foreign keys
    $table->foreign('feedback_id')
        ->references('id')
        ->on('feedback')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('admin_id')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.4 Create Feedback Tags Table
**File**: `database/migrations/2026_02_06_1000004_create_feedback_tags_table.php`

```php
Schema::create('feedback_tags', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 50);
    $table->string('slug', 50)->unique();
    $table->string('color', 7)->default('#6B7280');
    $table->text('description')->nullable();
    $table->integer('usage_count')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('usage_count');
});
```

### 3.5 Create Feedback Tag Assignments Table
**File**: `database/migrations/2026_02_06_1000005_create_feedback_tag_assignments_table.php`

```php
Schema::create('feedback_tag_assignments', function (Blueprint $table) {
    $table->uuid('feedback_id');
    $table->uuid('tag_id');
    $table->timestamps();

    // Primary key
    $table->primary(['feedback_id', 'tag_id']);

    // Foreign keys
    $table->foreign('feedback_id')
        ->references('id')
        ->on('feedback')
        ->cascadeOnDelete();

    $table->foreign('tag_id')
        ->references('id')
        ->on('feedback_tags')
        ->cascadeOnDelete();
});
```

### 3.6 Create Roadmap Items Table
**File**: `database/migrations/2026_02_06_1000006_create_roadmap_items_table.php`

```php
Schema::create('roadmap_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('title', 255);
    $table->text('description')->nullable();
    $table->longText('detailed_description')->nullable();
    $table->string('category', 30);  // RoadmapCategory
    $table->string('status', 20)->default('considering');  // RoadmapStatus
    $table->string('quarter', 10)->nullable();  // Q1 2026, etc.
    $table->date('target_date')->nullable();
    $table->date('shipped_date')->nullable();
    $table->string('priority', 20)->default('medium');  // AdminPriority (reused)
    $table->integer('progress_percentage')->default(0);
    $table->boolean('is_public')->default(true);
    $table->integer('linked_feedback_count')->default(0);
    $table->integer('total_votes')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('status');
    $table->index('quarter');
    $table->index('category');
    $table->index(['is_public', 'status']);
});
```

### 3.7 Create Roadmap Feedback Links Table
**File**: `database/migrations/2026_02_06_1000007_create_roadmap_feedback_links_table.php`

```php
Schema::create('roadmap_feedback_links', function (Blueprint $table) {
    $table->uuid('roadmap_item_id');
    $table->uuid('feedback_id');
    $table->timestamps();

    // Primary key
    $table->primary(['roadmap_item_id', 'feedback_id']);

    // Foreign keys
    $table->foreign('roadmap_item_id')
        ->references('id')
        ->on('roadmap_items')
        ->cascadeOnDelete();

    $table->foreign('feedback_id')
        ->references('id')
        ->on('feedback')
        ->cascadeOnDelete();
});
```

### 3.8 Add Roadmap FK to Feedback Table
**File**: `database/migrations/2026_02_06_1000008_add_roadmap_fk_to_feedback_table.php`

```php
Schema::table('feedback', function (Blueprint $table) {
    $table->foreign('roadmap_item_id')
        ->references('id')
        ->on('roadmap_items')
        ->nullOnDelete();
});
```

### 3.9 Create Release Notes Table
**File**: `database/migrations/2026_02_06_1000009_create_release_notes_table.php`

```php
Schema::create('release_notes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('version', 20);
    $table->string('version_name', 100)->nullable();
    $table->string('title', 255);
    $table->text('summary')->nullable();
    $table->longText('content');
    $table->string('content_format', 10)->default('markdown');  // markdown, html
    $table->string('release_type', 10);  // ReleaseType
    $table->string('status', 20)->default('draft');  // ReleaseNoteStatus
    $table->boolean('is_public')->default(true);
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('version');
    $table->index('status');
    $table->index('published_at');
});
```

### 3.10 Create Release Note Items Table
**File**: `database/migrations/2026_02_06_1000010_create_release_note_items_table.php`

```php
Schema::create('release_note_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('release_note_id');
    $table->string('title', 255);
    $table->text('description')->nullable();
    $table->string('change_type', 20);  // ChangeType
    $table->uuid('roadmap_item_id')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('release_note_id');

    // Foreign keys
    $table->foreign('release_note_id')
        ->references('id')
        ->on('release_notes')
        ->cascadeOnDelete();

    $table->foreign('roadmap_item_id')
        ->references('id')
        ->on('roadmap_items')
        ->nullOnDelete();
});
```

### 3.11 Create Changelog Subscriptions Table
**File**: `database/migrations/2026_02_06_1000011_create_changelog_subscriptions_table.php`

```php
Schema::create('changelog_subscriptions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email', 255);
    $table->uuid('user_id')->nullable();
    $table->uuid('tenant_id')->nullable();
    $table->boolean('notify_major')->default(true);
    $table->boolean('notify_minor')->default(true);
    $table->boolean('notify_patch')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamp('unsubscribed_at')->nullable();
    $table->timestamps();

    // Unique constraint
    $table->unique('email');

    // Indexes
    $table->index('is_active');

    // Foreign keys
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();
});
```

---

## 4. Models

### 4.1 Feedback Model
**File**: `app/Models/Feedback/Feedback.php`

Key methods:
- Relationships: `tenant()`, `user()`, `duplicateOf()`, `duplicates()`, `reviewedBy()`, `votes()`, `comments()`, `tags()`, `roadmapItem()`
- Scopes: `new()`, `underReview()`, `planned()`, `shipped()`, `open()`, `closed()`, `byType()`, `byCategory()`, `topVoted()`, `forTenant()`, `search()`
- Helpers: `isNew()`, `isOpen()`, `isClosed()`, `upvote()`, `hasVotedBy()`, `markAsReviewed()`, `linkToRoadmap()`, `markAsDuplicate()`, `incrementVoteCount()`, `decrementVoteCount()`

### 4.2 FeedbackVote Model
**File**: `app/Models/Feedback/FeedbackVote.php`

Key methods:
- Relationships: `feedback()`, `user()`, `tenant()`
- Scopes: `forFeedback()`, `byUser()`, `upvotes()`, `downvotes()`
- Helpers: `isUpvote()`, `isDownvote()`

### 4.3 FeedbackComment Model
**File**: `app/Models/Feedback/FeedbackComment.php`

Key methods:
- Relationships: `feedback()`, `user()`, `admin()`
- Scopes: `forFeedback()`, `public()`, `internal()`, `official()`
- Helpers: `isInternal()`, `isOfficial()`, `getAuthorName()`

### 4.4 FeedbackTag Model
**File**: `app/Models/Feedback/FeedbackTag.php`

Key methods:
- Relationships: `feedback()`
- Scopes: `popular()`, `ordered()`, `search()`
- Helpers: `incrementUsageCount()`, `decrementUsageCount()`

### 4.5 FeedbackTagAssignment Model (Pivot)
**File**: `app/Models/Feedback/FeedbackTagAssignment.php`

Key methods:
- Relationships: `feedback()`, `tag()`

### 4.6 RoadmapItem Model
**File**: `app/Models/Feedback/RoadmapItem.php`

Key methods:
- Relationships: `linkedFeedback()`, `releaseNoteItems()`
- Scopes: `public()`, `byStatus()`, `byCategory()`, `byQuarter()`, `active()`, `shipped()`
- Helpers: `isPublic()`, `isActive()`, `isShipped()`, `updateProgress()`, `markAsShipped()`, `linkFeedback()`, `unlinkFeedback()`, `recalculateCounts()`

### 4.7 RoadmapFeedbackLink Model (Pivot)
**File**: `app/Models/Feedback/RoadmapFeedbackLink.php`

Key methods:
- Relationships: `roadmapItem()`, `feedback()`

### 4.8 ReleaseNote Model
**File**: `app/Models/Feedback/ReleaseNote.php`

Key methods:
- Relationships: `items()`
- Scopes: `published()`, `draft()`, `scheduled()`, `byType()`, `recent()`
- Helpers: `isPublished()`, `isDraft()`, `isScheduled()`, `publish()`, `schedule()`, `addItem()`

### 4.9 ReleaseNoteItem Model
**File**: `app/Models/Feedback/ReleaseNoteItem.php`

Key methods:
- Relationships: `releaseNote()`, `roadmapItem()`
- Scopes: `forRelease()`, `byType()`, `ordered()`

### 4.10 ChangelogSubscription Model
**File**: `app/Models/Feedback/ChangelogSubscription.php`

Key methods:
- Relationships: `user()`, `tenant()`
- Scopes: `active()`, `forEmail()`, `notifyFor()`
- Helpers: `isActive()`, `unsubscribe()`, `resubscribe()`, `shouldNotifyFor()`

---

## 5. Factories & Seeders

### Factories
- `FeedbackFactory` - with states for types, statuses, priorities
- `FeedbackVoteFactory` - with states for upvote/downvote
- `FeedbackCommentFactory` - with states for internal, official
- `FeedbackTagFactory` - with states for popular
- `RoadmapItemFactory` - with states for statuses, categories
- `ReleaseNoteFactory` - with states for types, statuses
- `ReleaseNoteItemFactory` - with states for change types
- `ChangelogSubscriptionFactory` - with states for active/inactive

### Seeders
- `FeedbackSeeder` - Create sample feedback items
- `FeedbackTagSeeder` - Create common tags
- `RoadmapItemSeeder` - Create sample roadmap items
- `ReleaseNoteSeeder` - Create sample release notes
- `FeedbackRoadmapSeeder` (Orchestrator)

---

## 6. Test Requirements

Create tests for:
- 13 enum tests
- 10 model tests

---

## 7. Implementation Checklist

- [ ] Create FeedbackType enum
- [ ] Create FeedbackCategory enum
- [ ] Create FeedbackStatus enum
- [ ] Create UserPriority enum
- [ ] Create AdminPriority enum
- [ ] Create EffortEstimate enum
- [ ] Create FeedbackSource enum
- [ ] Create VoteType enum
- [ ] Create RoadmapCategory enum
- [ ] Create RoadmapStatus enum
- [ ] Create ReleaseType enum
- [ ] Create ReleaseNoteStatus enum
- [ ] Create ChangeType enum
- [ ] Create feedback migration
- [ ] Create feedback_votes migration
- [ ] Create feedback_comments migration
- [ ] Create feedback_tags migration
- [ ] Create feedback_tag_assignments migration
- [ ] Create roadmap_items migration
- [ ] Create roadmap_feedback_links migration
- [ ] Add roadmap FK to feedback
- [ ] Create release_notes migration
- [ ] Create release_note_items migration
- [ ] Create changelog_subscriptions migration
- [ ] Create all 10 models
- [ ] Create all factories
- [ ] Create all seeders
- [ ] Create all unit tests
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 8. Notes

1. **Platform-Level Feature**: Feedback and roadmap are managed by SuperAdmins, but tenants/users can submit and vote.

2. **Vote Deduplication**: Votes are unique per user OR per session to prevent duplicate voting.

3. **Duplicate Handling**: When feedback is marked as duplicate, votes can be optionally transferred to the original.

4. **Roadmap Links**: Multiple feedback items can be linked to a single roadmap item. Counts are cached.

5. **Release Notes Workflow**: Draft → Scheduled → Published. Publishing triggers notifications.

6. **Changelog Subscriptions**: Users can subscribe with granular preferences (major/minor/patch).
