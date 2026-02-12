# Task 2.10: Feedback & Roadmap Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.10 Feedback & Roadmap Services & API
- **Dependencies**: Task 2.1, Task 1.9 (Feedback Migrations)

---

## 1. Overview

This task implements feedback collection, public roadmap, and release notes services. It covers user feedback submission, voting, public roadmap display, and changelog.

### Components to Implement
1. **FeedbackService** - Feedback submission and management
2. **RoadmapService** - Public roadmap display
3. **ReleaseNoteService** - Changelog/release notes
4. **Controllers** - Public and admin API endpoints
5. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 FeedbackService
**File**: `app/Services/Feedback/FeedbackService.php`

```php
final class FeedbackService extends BaseService
{
    // Public methods
    public function listPublic(array $filters = []): LengthAwarePaginator;
    public function submit(SubmitFeedbackData $data, ?User $user = null): Feedback;
    public function vote(Feedback $feedback, User $user, VoteType $type): FeedbackVote;
    public function removeVote(Feedback $feedback, User $user): void;
    public function addComment(Feedback $feedback, AddCommentData $data, ?User $user = null): FeedbackComment;
    public function getPopular(int $limit = 10): Collection;

    // Admin methods
    public function listAll(array $filters = []): LengthAwarePaginator;
    public function get(string $id): Feedback;
    public function updateStatus(Feedback $feedback, FeedbackStatus $status): Feedback;
    public function linkToRoadmap(Feedback $feedback, RoadmapItem $item): void;
    public function getStats(): array;
}
```

### 2.2 RoadmapService
**File**: `app/Services/Feedback/RoadmapService.php`

```php
final class RoadmapService extends BaseService
{
    // Public methods
    public function getPublicRoadmap(): Collection;  // Grouped by status
    public function getItem(string $id): RoadmapItem;

    // Admin methods
    public function listAll(array $filters = []): LengthAwarePaginator;
    public function create(CreateRoadmapItemData $data): RoadmapItem;
    public function update(RoadmapItem $item, UpdateRoadmapItemData $data): RoadmapItem;
    public function updateStatus(RoadmapItem $item, RoadmapStatus $status): RoadmapItem;
    public function delete(RoadmapItem $item): void;
}
```

### 2.3 ReleaseNoteService
**File**: `app/Services/Feedback/ReleaseNoteService.php`

```php
final class ReleaseNoteService extends BaseService
{
    // Public methods
    public function listPublished(array $filters = []): LengthAwarePaginator;
    public function getBySlug(string $slug): ReleaseNote;
    public function subscribe(string $email): ChangelogSubscription;
    public function unsubscribe(string $token): void;

    // Admin methods
    public function listAll(array $filters = []): LengthAwarePaginator;
    public function create(CreateReleaseNoteData $data): ReleaseNote;
    public function update(ReleaseNote $note, UpdateReleaseNoteData $data): ReleaseNote;
    public function publish(ReleaseNote $note): ReleaseNote;
    public function unpublish(ReleaseNote $note): ReleaseNote;
    public function delete(ReleaseNote $note): void;
}
```

---

## 3. Data Classes

### 3.1 Feedback Data
**Directory**: `app/Data/Feedback/`

```php
// FeedbackData.php
final class FeedbackData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $type,
        public string $category,
        public string $status,
        public int $vote_count,
        public int $comment_count,
        public ?string $submitter_name,
        public bool $is_anonymous,
        public ?int $user_vote,  // null, 1 (up), -1 (down)
        public string $created_at,
    ) {}

    public static function fromModel(Feedback $feedback, ?User $user = null): self;
}

// SubmitFeedbackData.php
final class SubmitFeedbackData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public string $title,
        #[Required]
        public string $description,
        public FeedbackType $type = FeedbackType::FEATURE_REQUEST,
        public FeedbackCategory $category = FeedbackCategory::GENERAL,
        public ?string $email = null,  // For anonymous
        public bool $is_anonymous = false,
    ) {}
}

// RoadmapItemData.php
final class RoadmapItemData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $category,
        public string $status,
        public ?string $target_quarter,
        public int $feedback_count,
        public int $vote_count,
        public string $created_at,
    ) {}

    public static function fromModel(RoadmapItem $item): self;
}

// CreateRoadmapItemData.php
final class CreateRoadmapItemData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public string $title,
        public ?string $description = null,
        public RoadmapCategory $category = RoadmapCategory::FEATURE,
        public RoadmapStatus $status = RoadmapStatus::PLANNED,
        public ?string $target_quarter = null,
    ) {}
}

// ReleaseNoteData.php
final class ReleaseNoteData extends Data
{
    public function __construct(
        public string $id,
        public string $version,
        public string $slug,
        public string $title,
        public string $content,
        public string $release_type,
        public string $status,
        public ?string $published_at,
        public array $items,  // ReleaseNoteItemData[]
        public string $created_at,
    ) {}

    public static function fromModel(ReleaseNote $note): self;
}

// ReleaseNoteItemData.php
final class ReleaseNoteItemData extends Data
{
    public function __construct(
        public string $id,
        public string $change_type,
        public string $description,
        public int $sort_order,
    ) {}

    public static function fromModel(ReleaseNoteItem $item): self;
}

// CreateReleaseNoteData.php
final class CreateReleaseNoteData extends Data
{
    public function __construct(
        #[Required]
        public string $version,
        #[Required]
        public string $title,
        #[Required]
        public string $content,
        public ReleaseType $release_type = ReleaseType::MINOR,
        public ?array $items = null,  // Change items
    ) {}
}
```

---

## 4. Controllers

### 4.1 Public Controllers (No Auth for read, optional for actions)

**FeedbackController** - `app/Http/Controllers/Api/V1/Feedback/FeedbackController.php`
- `GET /feedback` - List public feedback
- `POST /feedback` - Submit feedback (optional auth)
- `GET /feedback/popular` - Get popular feedback
- `GET /feedback/{id}` - Get single feedback
- `POST /feedback/{id}/vote` - Vote (requires auth)
- `DELETE /feedback/{id}/vote` - Remove vote (requires auth)
- `POST /feedback/{id}/comments` - Add comment (optional auth)

**RoadmapController** - `app/Http/Controllers/Api/V1/Feedback/RoadmapController.php`
- `GET /roadmap` - Get public roadmap (grouped by status)
- `GET /roadmap/{id}` - Get roadmap item

**ReleaseNoteController** - `app/Http/Controllers/Api/V1/Feedback/ReleaseNoteController.php`
- `GET /changelog` - List published release notes
- `GET /changelog/{slug}` - Get release note by slug
- `POST /changelog/subscribe` - Subscribe to changelog
- `POST /changelog/unsubscribe` - Unsubscribe

### 4.2 Admin Controllers

**AdminFeedbackController** - `app/Http/Controllers/Api/V1/Admin/Feedback/AdminFeedbackController.php`
- Full CRUD + status management + stats

**AdminRoadmapController** - `app/Http/Controllers/Api/V1/Admin/Feedback/AdminRoadmapController.php`
- Full CRUD + status management

**AdminReleaseNoteController** - `app/Http/Controllers/Api/V1/Admin/Feedback/AdminReleaseNoteController.php`
- Full CRUD + publish/unpublish

---

## 5. Routes

```php
// Public feedback routes
Route::prefix('feedback')->group(function () {
    Route::get('/', [FeedbackController::class, 'index']);
    Route::post('/', [FeedbackController::class, 'store']);
    Route::get('/popular', [FeedbackController::class, 'popular']);
    Route::get('/{feedback}', [FeedbackController::class, 'show']);

    // Authenticated actions
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{feedback}/vote', [FeedbackController::class, 'vote']);
        Route::delete('/{feedback}/vote', [FeedbackController::class, 'removeVote']);
    });

    Route::post('/{feedback}/comments', [FeedbackController::class, 'addComment']);
});

// Public roadmap
Route::prefix('roadmap')->group(function () {
    Route::get('/', [RoadmapController::class, 'index']);
    Route::get('/{roadmapItem}', [RoadmapController::class, 'show']);
});

// Public changelog
Route::prefix('changelog')->group(function () {
    Route::get('/', [ReleaseNoteController::class, 'index']);
    Route::get('/{slug}', [ReleaseNoteController::class, 'show']);
    Route::post('/subscribe', [ReleaseNoteController::class, 'subscribe']);
    Route::post('/unsubscribe', [ReleaseNoteController::class, 'unsubscribe']);
});

// Admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('feedback', AdminFeedbackController::class);
    Route::put('/feedback/{feedback}/status', [AdminFeedbackController::class, 'updateStatus']);
    Route::post('/feedback/{feedback}/link-roadmap', [AdminFeedbackController::class, 'linkToRoadmap']);
    Route::get('/feedback-stats', [AdminFeedbackController::class, 'stats']);

    Route::apiResource('roadmap', AdminRoadmapController::class);
    Route::put('/roadmap/{roadmapItem}/status', [AdminRoadmapController::class, 'updateStatus']);

    Route::apiResource('release-notes', AdminReleaseNoteController::class);
    Route::post('/release-notes/{releaseNote}/publish', [AdminReleaseNoteController::class, 'publish']);
    Route::post('/release-notes/{releaseNote}/unpublish', [AdminReleaseNoteController::class, 'unpublish']);
});
```

---

## 6. Test Requirements

### Feature Tests
- Public and admin feedback tests
- Public and admin roadmap tests
- Public and admin release note tests

### Unit Tests
- FeedbackService, RoadmapService, ReleaseNoteService

---

## 7. Business Rules

### Feedback Rules
- Anonymous submission allowed with email
- Authenticated users get their vote status
- Status changes tracked
- Popular = most votes

### Roadmap Rules
- Only visible items shown publicly
- Grouped by status (Planned, In Progress, Completed)

### Release Notes Rules
- Only published notes visible
- Slug auto-generated from version
- Subscription requires email confirmation
