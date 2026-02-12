# Task 2.5: Content & Publishing Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.5 Content & Publishing Services & API
- **Dependencies**: Task 2.1, Task 2.3, Task 2.4, Task 1.5 (Content Migrations)

---

## 1. Overview

This task implements content management and publishing services for social media posts. It covers post CRUD, scheduling, approval workflows, media management, and publishing operations.

### Components to Implement
1. **PostService** - Post CRUD and workflow management
2. **PostMediaService** - Media attachment management
3. **PostTargetService** - Social account target management
4. **ApprovalService** - Post approval workflow
5. **PublishingService** - Publishing orchestration (stub)
6. **Controllers** - API endpoints
7. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 PostService
**File**: `app/Services/Content/PostService.php`

```php
final class PostService extends BaseService
{
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator;
    public function create(Workspace $workspace, User $author, CreatePostData $data): Post;
    public function get(string $postId): Post;
    public function getByWorkspace(Workspace $workspace, string $postId): Post;
    public function update(Post $post, UpdatePostData $data): Post;
    public function delete(Post $post): void;
    public function submit(Post $post): Post;
    public function schedule(Post $post, \DateTimeInterface $scheduledAt, ?string $timezone = null): Post;
    public function cancel(Post $post): Post;
    public function duplicate(Post $post, User $user): Post;
}
```

### 2.2 PostMediaService
**File**: `app/Services/Content/PostMediaService.php`

```php
final class PostMediaService extends BaseService
{
    public function listForPost(Post $post): Collection;
    public function attach(Post $post, AttachMediaData $data): PostMedia;
    public function updateOrder(Post $post, array $mediaOrder): void;
    public function remove(PostMedia $media): void;
    public function removeAll(Post $post): void;
}
```

### 2.3 PostTargetService
**File**: `app/Services/Content/PostTargetService.php`

```php
final class PostTargetService extends BaseService
{
    public function listForPost(Post $post): Collection;
    public function setTargets(Post $post, array $socialAccountIds): Collection;
    public function addTarget(Post $post, SocialAccount $account): PostTarget;
    public function removeTarget(PostTarget $target): void;
    public function updateTargetStatus(PostTarget $target, PostTargetStatus $status, ?array $response = null): PostTarget;
}
```

### 2.4 ApprovalService
**File**: `app/Services/Content/ApprovalService.php`

```php
final class ApprovalService extends BaseService
{
    public function getPendingForWorkspace(Workspace $workspace): Collection;
    public function approve(Post $post, User $approver, ?string $comment = null): ApprovalDecision;
    public function reject(Post $post, User $approver, string $reason, ?string $comment = null): ApprovalDecision;
    public function getDecisionHistory(Post $post): Collection;
    public function canUserApprove(User $user, Post $post): bool;
}
```

### 2.5 PublishingService (Stub)
**File**: `app/Services/Content/PublishingService.php`

```php
final class PublishingService extends BaseService
{
    public function publishNow(Post $post): void;  // Marks post for immediate publishing
    public function publishScheduled(): void;  // Job-callable method for scheduled posts
    public function retryFailed(Post $post): void;  // Retry failed publishing
    public function processTarget(PostTarget $target): void;  // Stub - process single target
}
```

---

## 3. Data Classes

### 3.1 Post Data
**Directory**: `app/Data/Content/`

```php
// PostData.php
final class PostData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $author_id,
        public ?string $author_name,
        public ?string $content_text,
        public ?array $content_variations,
        public string $status,
        public string $post_type,
        public ?string $scheduled_at,
        public ?string $scheduled_timezone,
        public ?string $published_at,
        public ?array $hashtags,
        public ?array $mentions,
        public ?string $link_url,
        public ?array $link_preview,
        public ?string $first_comment,
        public ?string $rejection_reason,
        public int $target_count,
        public int $media_count,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Post $post): self;
}

// PostDetailData.php (with related data)
final class PostDetailData extends Data
{
    public function __construct(
        public PostData $post,
        public array $targets,  // PostTargetData[]
        public array $media,    // PostMediaData[]
    ) {}

    public static function fromModel(Post $post): self;
}

// CreatePostData.php
final class CreatePostData extends Data
{
    public function __construct(
        public ?string $content_text = null,
        public ?array $content_variations = null,
        #[Required]
        public PostType $post_type = PostType::STANDARD,
        public ?string $scheduled_at = null,
        public ?string $scheduled_timezone = null,
        public ?array $hashtags = null,
        public ?array $mentions = null,
        public ?string $link_url = null,
        public ?string $first_comment = null,
        public ?array $social_account_ids = null,  // Target accounts
    ) {}
}

// UpdatePostData.php
final class UpdatePostData extends Data
{
    public function __construct(
        public ?string $content_text = null,
        public ?array $content_variations = null,
        public ?array $hashtags = null,
        public ?array $mentions = null,
        public ?string $link_url = null,
        public ?string $first_comment = null,
    ) {}
}

// SchedulePostData.php
final class SchedulePostData extends Data
{
    public function __construct(
        #[Required]
        public string $scheduled_at,
        public ?string $timezone = null,
    ) {}
}

// PostTargetData.php
final class PostTargetData extends Data
{
    public function __construct(
        public string $id,
        public string $post_id,
        public string $social_account_id,
        public string $platform,
        public string $account_name,
        public string $status,
        public ?string $platform_post_id,
        public ?string $platform_post_url,
        public ?string $published_at,
        public ?string $error_message,
    ) {}

    public static function fromModel(PostTarget $target): self;
}

// PostMediaData.php
final class PostMediaData extends Data
{
    public function __construct(
        public string $id,
        public string $post_id,
        public string $media_type,
        public string $file_path,
        public ?string $file_url,
        public ?string $thumbnail_url,
        public ?string $original_filename,
        public ?int $file_size,
        public ?string $mime_type,
        public int $sort_order,
        public ?array $metadata,
        public string $processing_status,
    ) {}

    public static function fromModel(PostMedia $media): self;
}

// AttachMediaData.php
final class AttachMediaData extends Data
{
    public function __construct(
        #[Required]
        public MediaType $media_type,
        #[Required]
        public string $file_path,
        public ?string $file_url = null,
        public ?string $thumbnail_url = null,
        public ?string $original_filename = null,
        public ?int $file_size = null,
        public ?string $mime_type = null,
        public int $sort_order = 0,
        public ?array $metadata = null,
    ) {}
}

// ApprovalDecisionData.php
final class ApprovalDecisionData extends Data
{
    public function __construct(
        public string $id,
        public string $post_id,
        public string $decided_by_user_id,
        public ?string $decided_by_name,
        public string $decision,
        public ?string $reason,
        public ?string $comment,
        public bool $is_active,
        public string $decided_at,
    ) {}

    public static function fromModel(ApprovalDecision $decision): self;
}

// ApprovePostData.php
final class ApprovePostData extends Data
{
    public function __construct(
        public ?string $comment = null,
    ) {}
}

// RejectPostData.php
final class RejectPostData extends Data
{
    public function __construct(
        #[Required]
        public string $reason,
        public ?string $comment = null,
    ) {}
}
```

---

## 4. Controllers

### 4.1 PostController
**File**: `app/Http/Controllers/Api/V1/Content/PostController.php`

Endpoints:
- `GET /workspaces/{workspace}/posts` - List posts
- `POST /workspaces/{workspace}/posts` - Create post
- `GET /workspaces/{workspace}/posts/{id}` - Get post with details
- `PUT /workspaces/{workspace}/posts/{id}` - Update post
- `DELETE /workspaces/{workspace}/posts/{id}` - Delete post
- `POST /workspaces/{workspace}/posts/{id}/submit` - Submit for approval
- `POST /workspaces/{workspace}/posts/{id}/schedule` - Schedule post
- `POST /workspaces/{workspace}/posts/{id}/publish` - Publish immediately
- `POST /workspaces/{workspace}/posts/{id}/cancel` - Cancel post
- `POST /workspaces/{workspace}/posts/{id}/duplicate` - Duplicate post

### 4.2 PostMediaController
**File**: `app/Http/Controllers/Api/V1/Content/PostMediaController.php`

Endpoints:
- `GET /workspaces/{workspace}/posts/{post}/media` - List media
- `POST /workspaces/{workspace}/posts/{post}/media` - Attach media
- `PUT /workspaces/{workspace}/posts/{post}/media/order` - Update order
- `DELETE /workspaces/{workspace}/posts/{post}/media/{media}` - Remove media

### 4.3 PostTargetController
**File**: `app/Http/Controllers/Api/V1/Content/PostTargetController.php`

Endpoints:
- `GET /workspaces/{workspace}/posts/{post}/targets` - List targets
- `PUT /workspaces/{workspace}/posts/{post}/targets` - Set targets (replace all)
- `DELETE /workspaces/{workspace}/posts/{post}/targets/{target}` - Remove target

### 4.4 ApprovalController
**File**: `app/Http/Controllers/Api/V1/Content/ApprovalController.php`

Endpoints:
- `GET /workspaces/{workspace}/approvals` - List pending approvals
- `POST /workspaces/{workspace}/posts/{post}/approve` - Approve post
- `POST /workspaces/{workspace}/posts/{post}/reject` - Reject post
- `GET /workspaces/{workspace}/posts/{post}/approval-history` - Get history

---

## 5. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('workspaces/{workspace}')->group(function () {
        // Posts
        Route::apiResource('posts', PostController::class);
        Route::post('/posts/{post}/submit', [PostController::class, 'submit']);
        Route::post('/posts/{post}/schedule', [PostController::class, 'schedule']);
        Route::post('/posts/{post}/publish', [PostController::class, 'publish']);
        Route::post('/posts/{post}/cancel', [PostController::class, 'cancel']);
        Route::post('/posts/{post}/duplicate', [PostController::class, 'duplicate']);

        // Post Media
        Route::get('/posts/{post}/media', [PostMediaController::class, 'index']);
        Route::post('/posts/{post}/media', [PostMediaController::class, 'store']);
        Route::put('/posts/{post}/media/order', [PostMediaController::class, 'updateOrder']);
        Route::delete('/posts/{post}/media/{media}', [PostMediaController::class, 'destroy']);

        // Post Targets
        Route::get('/posts/{post}/targets', [PostTargetController::class, 'index']);
        Route::put('/posts/{post}/targets', [PostTargetController::class, 'update']);
        Route::delete('/posts/{post}/targets/{target}', [PostTargetController::class, 'destroy']);

        // Approvals
        Route::get('/approvals', [ApprovalController::class, 'index']);
        Route::post('/posts/{post}/approve', [ApprovalController::class, 'approve']);
        Route::post('/posts/{post}/reject', [ApprovalController::class, 'reject']);
        Route::get('/posts/{post}/approval-history', [ApprovalController::class, 'history']);
    });
});
```

---

## 6. Form Requests

**Directory**: `app/Http/Requests/Content/`

- `CreatePostRequest.php`
- `UpdatePostRequest.php`
- `SchedulePostRequest.php`
- `AttachMediaRequest.php`
- `UpdateMediaOrderRequest.php`
- `SetTargetsRequest.php`
- `ApprovePostRequest.php`
- `RejectPostRequest.php`

---

## 7. Test Requirements

### Feature Tests
- `tests/Feature/Api/Content/PostTest.php`
- `tests/Feature/Api/Content/PostMediaTest.php`
- `tests/Feature/Api/Content/PostTargetTest.php`
- `tests/Feature/Api/Content/ApprovalTest.php`

### Unit Tests
- `tests/Unit/Services/Content/PostServiceTest.php`
- `tests/Unit/Services/Content/PostMediaServiceTest.php`
- `tests/Unit/Services/Content/PostTargetServiceTest.php`
- `tests/Unit/Services/Content/ApprovalServiceTest.php`
- `tests/Unit/Services/Content/PublishingServiceTest.php`

---

## 8. Implementation Checklist

- [ ] Create PostService
- [ ] Create PostMediaService
- [ ] Create PostTargetService
- [ ] Create ApprovalService
- [ ] Create PublishingService (stub)
- [ ] Create Post Data classes
- [ ] Create PostController
- [ ] Create PostMediaController
- [ ] Create PostTargetController
- [ ] Create ApprovalController
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 9. Business Rules

### Post Status Transitions
- DRAFT → SUBMITTED (submit for approval)
- DRAFT → CANCELLED (cancel draft)
- SUBMITTED → APPROVED (approve)
- SUBMITTED → REJECTED (reject)
- APPROVED → SCHEDULED (schedule)
- APPROVED → PUBLISHING (publish now)
- REJECTED → DRAFT (revise and resubmit)
- SCHEDULED → PUBLISHING (time reached)
- SCHEDULED → CANCELLED (cancel scheduled)
- PUBLISHING → PUBLISHED (all targets success)
- PUBLISHING → FAILED (any target failed)
- FAILED → PUBLISHING (retry)

### Permission Rules
- Viewer: Can view posts
- Editor: Can create/edit own posts, submit for approval
- Admin: Can approve/reject posts, edit any post
- Owner: Full access

### Content Rules
- Post must have either content_text or media
- Targets must be connected accounts in the same workspace
- Cannot edit PUBLISHED or PUBLISHING posts
- Cannot delete PUBLISHED posts
