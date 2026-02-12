# Task 2.6: Inbox & Engagement Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.6 Inbox & Engagement Services & API
- **Dependencies**: Task 2.1, Task 2.3, Task 2.4, Task 1.8 (Inbox Migrations)

---

## 1. Overview

This task implements inbox management services for handling social media engagement (comments, mentions). It covers listing, filtering, assignment, resolution, and reply functionality.

### Components to Implement
1. **Models** - InboxItem, InboxReply (if not existing)
2. **InboxService** - Core inbox management
3. **InboxReplyService** - Reply management
4. **Controllers** - API endpoints
5. **Data Classes** - Request/response DTOs
6. **Factories** - For testing

---

## 2. Models (Create if missing)

### 2.1 InboxItem
**File**: `app/Models/Inbox/InboxItem.php`

```php
final class InboxItem extends Model
{
    // Relationships: workspace, socialAccount, postTarget, assignedTo, resolvedBy, replies
    // Scopes: forWorkspace, unread, read, resolved, archived, forPlatform, assignedTo
    // Methods: markAsRead, resolve, archive, assign, unassign
}
```

### 2.2 InboxReply
**File**: `app/Models/Inbox/InboxReply.php`

```php
final class InboxReply extends Model
{
    // Relationships: inboxItem, repliedBy
    // Methods: markFailed
}
```

---

## 3. Services

### 3.1 InboxService
**File**: `app/Services/Inbox/InboxService.php`

```php
final class InboxService extends BaseService
{
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator;
    public function get(string $id): InboxItem;
    public function getByWorkspace(Workspace $workspace, string $id): InboxItem;
    public function markAsRead(InboxItem $item): InboxItem;
    public function markAsUnread(InboxItem $item): InboxItem;
    public function resolve(InboxItem $item, User $user): InboxItem;
    public function unresolve(InboxItem $item): InboxItem;
    public function archive(InboxItem $item): InboxItem;
    public function assign(InboxItem $item, User $user): InboxItem;
    public function unassign(InboxItem $item): InboxItem;
    public function getStats(Workspace $workspace): array;
    public function bulkMarkAsRead(Workspace $workspace, array $itemIds): int;
    public function bulkResolve(Workspace $workspace, array $itemIds, User $user): int;
}
```

### 3.2 InboxReplyService
**File**: `app/Services/Inbox/InboxReplyService.php`

```php
final class InboxReplyService extends BaseService
{
    public function listForItem(InboxItem $item): Collection;
    public function create(InboxItem $item, User $user, CreateReplyData $data): InboxReply;
    public function get(string $id): InboxReply;
}
```

---

## 4. Data Classes

### 4.1 Inbox Data
**Directory**: `app/Data/Inbox/`

```php
// InboxItemData.php
final class InboxItemData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $social_account_id,
        public ?string $post_target_id,
        public string $item_type,
        public string $status,
        public string $platform_item_id,
        public ?string $platform_post_id,
        public string $author_name,
        public ?string $author_username,
        public ?string $author_profile_url,
        public ?string $author_avatar_url,
        public string $content_text,
        public string $platform_created_at,
        public ?string $assigned_to_user_id,
        public ?string $assigned_to_name,
        public ?string $assigned_at,
        public ?string $resolved_at,
        public ?string $resolved_by_name,
        public int $reply_count,
        public string $created_at,
        // Related data
        public ?string $platform,
        public ?string $account_name,
    ) {}

    public static function fromModel(InboxItem $item): self;
}

// InboxReplyData.php
final class InboxReplyData extends Data
{
    public function __construct(
        public string $id,
        public string $inbox_item_id,
        public string $replied_by_user_id,
        public string $replied_by_name,
        public string $content_text,
        public ?string $platform_reply_id,
        public string $sent_at,
        public ?string $failed_at,
        public ?string $failure_reason,
    ) {}

    public static function fromModel(InboxReply $reply): self;
}

// CreateReplyData.php
final class CreateReplyData extends Data
{
    public function __construct(
        #[Required, Max(1000)]
        public string $content_text,
    ) {}
}

// InboxStatsData.php
final class InboxStatsData extends Data
{
    public function __construct(
        public int $total,
        public int $unread,
        public int $read,
        public int $resolved,
        public int $archived,
        public int $assigned_to_me,
        public array $by_type,
        public array $by_platform,
    ) {}
}

// BulkActionData.php
final class BulkActionData extends Data
{
    public function __construct(
        #[Required]
        public array $item_ids,
    ) {}
}

// AssignData.php
final class AssignData extends Data
{
    public function __construct(
        #[Required]
        public string $user_id,
    ) {}
}
```

---

## 5. Controllers

### 5.1 InboxController
**File**: `app/Http/Controllers/Api/V1/Inbox/InboxController.php`

Endpoints:
- `GET /workspaces/{workspace}/inbox` - List inbox items (with filters)
- `GET /workspaces/{workspace}/inbox/stats` - Get inbox statistics
- `GET /workspaces/{workspace}/inbox/{id}` - Get single item
- `POST /workspaces/{workspace}/inbox/{id}/read` - Mark as read
- `POST /workspaces/{workspace}/inbox/{id}/unread` - Mark as unread
- `POST /workspaces/{workspace}/inbox/{id}/resolve` - Resolve item
- `POST /workspaces/{workspace}/inbox/{id}/unresolve` - Unresolve item
- `POST /workspaces/{workspace}/inbox/{id}/archive` - Archive item
- `POST /workspaces/{workspace}/inbox/{id}/assign` - Assign to user
- `POST /workspaces/{workspace}/inbox/{id}/unassign` - Remove assignment
- `POST /workspaces/{workspace}/inbox/bulk-read` - Bulk mark as read
- `POST /workspaces/{workspace}/inbox/bulk-resolve` - Bulk resolve

### 5.2 InboxReplyController
**File**: `app/Http/Controllers/Api/V1/Inbox/InboxReplyController.php`

Endpoints:
- `GET /workspaces/{workspace}/inbox/{item}/replies` - List replies
- `POST /workspaces/{workspace}/inbox/{item}/replies` - Create reply

---

## 6. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('workspaces/{workspace}/inbox')->group(function () {
        Route::get('/', [InboxController::class, 'index']);
        Route::get('/stats', [InboxController::class, 'stats']);
        Route::post('/bulk-read', [InboxController::class, 'bulkRead']);
        Route::post('/bulk-resolve', [InboxController::class, 'bulkResolve']);
        Route::get('/{inboxItem}', [InboxController::class, 'show']);
        Route::post('/{inboxItem}/read', [InboxController::class, 'markRead']);
        Route::post('/{inboxItem}/unread', [InboxController::class, 'markUnread']);
        Route::post('/{inboxItem}/resolve', [InboxController::class, 'resolve']);
        Route::post('/{inboxItem}/unresolve', [InboxController::class, 'unresolve']);
        Route::post('/{inboxItem}/archive', [InboxController::class, 'archive']);
        Route::post('/{inboxItem}/assign', [InboxController::class, 'assign']);
        Route::post('/{inboxItem}/unassign', [InboxController::class, 'unassign']);

        // Replies
        Route::get('/{inboxItem}/replies', [InboxReplyController::class, 'index']);
        Route::post('/{inboxItem}/replies', [InboxReplyController::class, 'store']);
    });
});
```

---

## 7. Form Requests

**Directory**: `app/Http/Requests/Inbox/`

- `CreateReplyRequest.php`
- `AssignRequest.php`
- `BulkActionRequest.php`

---

## 8. Filters

List endpoint filters:
- `status` - unread, read, resolved, archived
- `type` - comment, mention
- `platform` - linkedin, facebook, instagram, twitter
- `social_account_id` - specific account
- `assigned_to` - user ID or "me" for current user
- `search` - search in content and author name
- `date_from`, `date_to` - date range

---

## 9. Test Requirements

### Feature Tests
- `tests/Feature/Api/Inbox/InboxTest.php`
- `tests/Feature/Api/Inbox/InboxReplyTest.php`

### Unit Tests
- `tests/Unit/Services/Inbox/InboxServiceTest.php`
- `tests/Unit/Services/Inbox/InboxReplyServiceTest.php`
- `tests/Unit/Models/Inbox/InboxItemTest.php`
- `tests/Unit/Models/Inbox/InboxReplyTest.php`

---

## 10. Implementation Checklist

- [ ] Create InboxItem model (if missing)
- [ ] Create InboxReply model (if missing)
- [ ] Create InboxItemFactory
- [ ] Create InboxReplyFactory
- [ ] Create InboxService
- [ ] Create InboxReplyService
- [ ] Create Inbox Data classes
- [ ] Create InboxController
- [ ] Create InboxReplyController
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 11. Business Rules

### Status Transitions
- unread → read, resolved, archived
- read → unread, resolved, archived
- resolved → read (unresolve)
- archived → (terminal, cannot transition)

### Permission Rules
- Viewer: Can view inbox items
- Editor: Can view, read/unread, reply
- Admin: Can assign, resolve, archive
- Owner: Full access

### Reply Rules
- Only comments (not mentions) can be replied to
- Reply max length: 1000 characters
- Reply is stubbed (doesn't actually post to platform)
