# Task 1.7: Content Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.7 Content Migrations
- **Dependencies**: Task 1.6 (Social Account Migrations) - COMPLETED

---

## 1. Overview

This task implements the content management entities for social media posts. Posts are the core content unit, with targets for multi-platform publishing, media attachments, and approval workflow.

### Entities to Implement
1. **Post** - Social media post content
2. **PostTarget** - Links post to social accounts
3. **PostMedia** - Media attachments (images, videos)
4. **ApprovalDecision** - Approval/rejection records

**Note**: Post templates are deferred beyond Phase-1.

---

## 2. Enums

### 2.1 PostStatus Enum
**File**: `app/Enums/Content/PostStatus.php`

```php
enum PostStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SCHEDULED = 'scheduled';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string;
    public function canEdit(): bool;        // DRAFT, REJECTED
    public function canDelete(): bool;      // Not PUBLISHED
    public function canPublish(): bool;     // APPROVED, SCHEDULED
    public function isTerminal(): bool;     // PUBLISHED, CANCELLED
    public function canTransitionTo(PostStatus $status): bool;
}
```

### 2.2 PostType Enum
**File**: `app/Enums/Content/PostType.php`

```php
enum PostType: string
{
    case STANDARD = 'standard';
    case REEL = 'reel';
    case STORY = 'story';
    case THREAD = 'thread';
    case ARTICLE = 'article';

    public function label(): string;
    public function supportedPlatforms(): array;
}
```

### 2.3 MediaType Enum
**File**: `app/Enums/Content/MediaType.php`

```php
enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case GIF = 'gif';
    case DOCUMENT = 'document';

    public function label(): string;
    public function maxFileSize(): int;      // In bytes
    public function allowedMimeTypes(): array;
}
```

### 2.4 MediaProcessingStatus Enum
**File**: `app/Enums/Content/MediaProcessingStatus.php`

```php
enum MediaProcessingStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string;
    public function isReady(): bool;  // COMPLETED only
}
```

### 2.5 PostTargetStatus Enum
**File**: `app/Enums/Content/PostTargetStatus.php`

```php
enum PostTargetStatus: string
{
    case PENDING = 'pending';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    public function label(): string;
    public function isPublished(): bool;
    public function hasFailed(): bool;
}
```

### 2.6 ApprovalDecisionType Enum
**File**: `app/Enums/Content/ApprovalDecisionType.php`

```php
enum ApprovalDecisionType: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string;
}
```

---

## 3. Migrations

### 3.1 Create Posts Table
**File**: `database/migrations/2026_02_06_700001_create_posts_table.php`

```php
Schema::create('posts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id');
    $table->uuid('created_by_user_id');
    $table->text('content_text')->nullable();
    $table->json('content_variations')->nullable();  // Platform-specific
    $table->string('status', 20)->default('draft');  // PostStatus
    $table->string('post_type', 20)->default('standard');  // PostType
    $table->timestamp('scheduled_at')->nullable();
    $table->string('scheduled_timezone', 50)->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamp('submitted_at')->nullable();
    $table->json('hashtags')->nullable();
    $table->json('mentions')->nullable();
    $table->string('link_url', 500)->nullable();
    $table->json('link_preview')->nullable();
    $table->text('first_comment')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('status');
    $table->index('scheduled_at');
    $table->index('published_at');
    $table->index('created_at');
    $table->index(['workspace_id', 'status']);
    $table->index(['workspace_id', 'scheduled_at']);

    // Foreign keys
    $table->foreign('workspace_id')
        ->references('id')
        ->on('workspaces')
        ->cascadeOnDelete();

    $table->foreign('created_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

### 3.2 Create Post Targets Table
**File**: `database/migrations/2026_02_06_700002_create_post_targets_table.php`

```php
Schema::create('post_targets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('post_id');
    $table->uuid('social_account_id');
    $table->string('platform_code', 20);  // Denormalized from social_account
    $table->text('content_override')->nullable();
    $table->string('status', 20)->default('pending');  // PostTargetStatus
    $table->string('external_post_id', 255)->nullable();
    $table->string('external_post_url', 500)->nullable();
    $table->timestamp('published_at')->nullable();
    $table->string('error_code', 100)->nullable();
    $table->text('error_message')->nullable();
    $table->integer('retry_count')->default(0);
    $table->json('metrics')->nullable();
    $table->timestamps();

    // Unique constraint
    $table->unique(['post_id', 'social_account_id']);

    // Indexes
    $table->index('status');
    $table->index('platform_code');

    // Foreign keys
    $table->foreign('post_id')
        ->references('id')
        ->on('posts')
        ->cascadeOnDelete();

    $table->foreign('social_account_id')
        ->references('id')
        ->on('social_accounts')
        ->cascadeOnDelete();
});
```

### 3.3 Create Post Media Table
**File**: `database/migrations/2026_02_06_700003_create_post_media_table.php`

```php
Schema::create('post_media', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('post_id');
    $table->string('type', 20);  // MediaType
    $table->string('file_name', 255);
    $table->bigInteger('file_size');  // Bytes
    $table->string('mime_type', 100);
    $table->string('storage_path', 500);
    $table->string('cdn_url', 500)->nullable();
    $table->string('thumbnail_url', 500)->nullable();
    $table->json('dimensions')->nullable();  // {width, height}
    $table->integer('duration_seconds')->nullable();  // For videos
    $table->string('alt_text', 500)->nullable();
    $table->integer('sort_order')->default(0);
    $table->string('processing_status', 20)->default('pending');
    $table->json('metadata')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('processing_status');
    $table->index(['post_id', 'sort_order']);

    // Foreign key
    $table->foreign('post_id')
        ->references('id')
        ->on('posts')
        ->cascadeOnDelete();
});
```

### 3.4 Create Approval Decisions Table
**File**: `database/migrations/2026_02_06_700004_create_approval_decisions_table.php`

```php
Schema::create('approval_decisions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('post_id');
    $table->uuid('decided_by_user_id');
    $table->string('decision', 20);  // ApprovalDecisionType
    $table->text('comment')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('decided_at');
    $table->timestamps();

    // Indexes
    $table->index(['post_id', 'is_active']);

    // Foreign keys
    $table->foreign('post_id')
        ->references('id')
        ->on('posts')
        ->cascadeOnDelete();

    $table->foreign('decided_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

---

## 4. Models

### 4.1 Post Model
**File**: `app/Models/Content/Post.php`

Key methods:
- Relationships: `workspace()`, `author()`, `targets()`, `media()`, `approvalDecisions()`, `activeApprovalDecision()`
- Scopes: `forWorkspace()`, `withStatus()`, `scheduled()`, `published()`, `draft()`, `requiresApproval()`
- Helpers: `canEdit()`, `canDelete()`, `canPublish()`, `hasTargets()`, `getTargetCount()`, `submit()`, `approve()`, `reject()`, `schedule()`, `markPublishing()`, `markPublished()`, `markFailed()`, `cancel()`

### 4.2 PostTarget Model
**File**: `app/Models/Content/PostTarget.php`

Key methods:
- Relationships: `post()`, `socialAccount()`
- Scopes: `forPost()`, `forPlatform()`, `pending()`, `published()`, `failed()`
- Helpers: `isPublished()`, `hasFailed()`, `markPublishing()`, `markPublished()`, `markFailed()`, `incrementRetry()`, `getContent()`

### 4.3 PostMedia Model
**File**: `app/Models/Content/PostMedia.php`

Key methods:
- Relationships: `post()`
- Scopes: `forPost()`, `images()`, `videos()`, `ready()`
- Helpers: `isReady()`, `isProcessing()`, `hasFailed()`, `markProcessing()`, `markCompleted()`, `markFailed()`, `getUrl()`, `getDimensions()`

### 4.4 ApprovalDecision Model
**File**: `app/Models/Content/ApprovalDecision.php`

Key methods:
- Relationships: `post()`, `decidedBy()`
- Scopes: `active()`, `forPost()`
- Helpers: `isApproved()`, `isRejected()`, `deactivate()`

---

## 5. Factories

Create factories for all 4 models with appropriate state methods:
- `PostFactory`: draft(), submitted(), approved(), rejected(), scheduled(), publishing(), published(), failed(), cancelled(), forWorkspace(), byUser(), scheduledFor(), withContent()
- `PostTargetFactory`: pending(), publishing(), published(), failed(), forPost(), forSocialAccount()
- `PostMediaFactory`: image(), video(), gif(), pending(), processing(), completed(), failed(), forPost()
- `ApprovalDecisionFactory`: approved(), rejected(), active(), inactive(), forPost(), byUser()

---

## 6. Seeders

### 6.1 PostSeeder
Create sample posts at various statuses for workspaces with social accounts.

### 6.2 ContentSeeder (Orchestrator)
Call PostSeeder and create related targets, media, and approvals.

---

## 7. Test Requirements

Create comprehensive tests for all 6 enums and 4 models. Test every method, relationship, scope, and constraint.

---

## 8. Implementation Checklist

- [ ] Create PostStatus enum
- [ ] Create PostType enum
- [ ] Create MediaType enum
- [ ] Create MediaProcessingStatus enum
- [ ] Create PostTargetStatus enum
- [ ] Create ApprovalDecisionType enum
- [ ] Create posts migration
- [ ] Create post_targets migration
- [ ] Create post_media migration
- [ ] Create approval_decisions migration
- [ ] Create Post model
- [ ] Create PostTarget model
- [ ] Create PostMedia model
- [ ] Create ApprovalDecision model
- [ ] Create all factories
- [ ] Create all seeders
- [ ] Create all unit tests
- [ ] Run migrations and seeders
- [ ] All tests pass
