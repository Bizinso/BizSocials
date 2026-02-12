# Task 2.8: Knowledge Base Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.8 Knowledge Base Services & API
- **Dependencies**: Task 2.1, Task 1.10 (Knowledge Base Migrations)

---

## 1. Overview

This task implements the public knowledge base (help center) services and API. The KB is a public resource for users with optional plan-based visibility for some articles.

### Components to Implement
1. **KBArticleService** - Article management and retrieval
2. **KBCategoryService** - Category management
3. **KBSearchService** - Search functionality
4. **KBFeedbackService** - Article feedback
5. **Controllers** - Public and admin API endpoints
6. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 KBArticleService
**File**: `app/Services/KnowledgeBase/KBArticleService.php`

```php
final class KBArticleService extends BaseService
{
    // Public methods
    public function listPublished(array $filters = []): LengthAwarePaginator;
    public function getBySlug(string $slug): KBArticle;
    public function getFeatured(int $limit = 5): Collection;
    public function getPopular(int $limit = 10): Collection;
    public function getRelated(KBArticle $article, int $limit = 5): Collection;
    public function incrementViewCount(KBArticle $article): void;

    // Admin methods
    public function list(array $filters = []): LengthAwarePaginator;
    public function create(SuperAdminUser $author, CreateArticleData $data): KBArticle;
    public function update(KBArticle $article, SuperAdminUser $editor, UpdateArticleData $data): KBArticle;
    public function publish(KBArticle $article): KBArticle;
    public function unpublish(KBArticle $article): KBArticle;
    public function archive(KBArticle $article): KBArticle;
    public function delete(KBArticle $article): void;
}
```

### 2.2 KBCategoryService
**File**: `app/Services/KnowledgeBase/KBCategoryService.php`

```php
final class KBCategoryService extends BaseService
{
    public function listWithArticleCount(): Collection;
    public function getBySlug(string $slug): KBCategory;
    public function getTree(): Collection;

    // Admin methods
    public function create(CreateCategoryData $data): KBCategory;
    public function update(KBCategory $category, UpdateCategoryData $data): KBCategory;
    public function updateOrder(array $order): void;
    public function delete(KBCategory $category): void;
}
```

### 2.3 KBSearchService
**File**: `app/Services/KnowledgeBase/KBSearchService.php`

```php
final class KBSearchService extends BaseService
{
    public function search(string $query, array $filters = []): LengthAwarePaginator;
    public function suggest(string $query, int $limit = 5): Collection;
    public function logSearch(string $query, int $resultCount, ?string $userId = null): void;
    public function getPopularSearches(int $limit = 10): Collection;
}
```

### 2.4 KBFeedbackService
**File**: `app/Services/KnowledgeBase/KBFeedbackService.php`

```php
final class KBFeedbackService extends BaseService
{
    public function submitFeedback(KBArticle $article, SubmitFeedbackData $data): KBArticleFeedback;
    public function listForArticle(KBArticle $article): Collection;

    // Admin methods
    public function listPending(): LengthAwarePaginator;
    public function resolve(KBArticleFeedback $feedback, SuperAdminUser $admin): KBArticleFeedback;
}
```

---

## 3. Data Classes

### 3.1 Knowledge Base Data
**Directory**: `app/Data/KnowledgeBase/`

```php
// KBArticleData.php
final class KBArticleData extends Data
{
    public function __construct(
        public string $id,
        public string $category_id,
        public string $category_name,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $content,
        public string $content_format,
        public ?string $featured_image,
        public string $article_type,
        public string $difficulty_level,
        public string $status,
        public bool $is_featured,
        public int $view_count,
        public int $helpful_count,
        public int $not_helpful_count,
        public float $helpfulness_score,
        public ?string $meta_title,
        public ?string $meta_description,
        public ?string $published_at,
        public string $created_at,
        public string $updated_at,
        public array $tags,
    ) {}

    public static function fromModel(KBArticle $article): self;
}

// KBArticleSummaryData.php (for lists)
final class KBArticleSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $category_name,
        public string $article_type,
        public string $difficulty_level,
        public int $view_count,
        public bool $is_featured,
        public ?string $published_at,
    ) {}

    public static function fromModel(KBArticle $article): self;
}

// KBCategoryData.php
final class KBCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $icon,
        public int $sort_order,
        public int $article_count,
        public ?string $parent_id,
    ) {}

    public static function fromModel(KBCategory $category): self;
}

// CreateArticleData.php
final class CreateArticleData extends Data
{
    public function __construct(
        #[Required]
        public string $category_id,
        #[Required, Max(200)]
        public string $title,
        #[Required]
        public string $content,
        public ?string $excerpt = null,
        public KBContentFormat $content_format = KBContentFormat::MARKDOWN,
        public KBArticleType $article_type = KBArticleType::GUIDE,
        public KBDifficultyLevel $difficulty_level = KBDifficultyLevel::BEGINNER,
        public bool $is_featured = false,
        public ?array $tag_ids = null,
    ) {}
}

// UpdateArticleData.php
final class UpdateArticleData extends Data
{
    public function __construct(
        public ?string $category_id = null,
        public ?string $title = null,
        public ?string $content = null,
        public ?string $excerpt = null,
        public ?KBArticleType $article_type = null,
        public ?KBDifficultyLevel $difficulty_level = null,
        public ?bool $is_featured = null,
        public ?array $tag_ids = null,
    ) {}
}

// CreateCategoryData.php
final class CreateCategoryData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $parent_id = null,
    ) {}
}

// UpdateCategoryData.php
final class UpdateCategoryData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $icon = null,
    ) {}
}

// SubmitFeedbackData.php
final class SubmitFeedbackData extends Data
{
    public function __construct(
        #[Required]
        public bool $is_helpful,
        public ?string $category = null,
        public ?string $comment = null,
        public ?string $email = null,
    ) {}
}

// KBSearchResultData.php
final class KBSearchResultData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $category_name,
        public float $relevance_score,
    ) {}
}
```

---

## 4. Controllers

### 4.1 Public Controllers (No Auth Required)

**KBArticleController** - `app/Http/Controllers/Api/V1/KB/KBArticleController.php`
- `GET /kb/articles` - List published articles
- `GET /kb/articles/featured` - Get featured articles
- `GET /kb/articles/popular` - Get popular articles
- `GET /kb/articles/{slug}` - Get article by slug

**KBCategoryController** - `app/Http/Controllers/Api/V1/KB/KBCategoryController.php`
- `GET /kb/categories` - List categories with counts
- `GET /kb/categories/{slug}` - Get category with articles

**KBSearchController** - `app/Http/Controllers/Api/V1/KB/KBSearchController.php`
- `GET /kb/search` - Search articles
- `GET /kb/search/suggest` - Get search suggestions
- `GET /kb/search/popular` - Get popular searches

**KBFeedbackController** - `app/Http/Controllers/Api/V1/KB/KBFeedbackController.php`
- `POST /kb/articles/{article}/feedback` - Submit feedback

### 4.2 Admin Controllers (Require SuperAdmin Auth)

**AdminKBArticleController** - `app/Http/Controllers/Api/V1/Admin/KB/AdminKBArticleController.php`
- Full CRUD + publish/unpublish/archive

**AdminKBCategoryController** - `app/Http/Controllers/Api/V1/Admin/KB/AdminKBCategoryController.php`
- Full CRUD + order management

**AdminKBFeedbackController** - `app/Http/Controllers/Api/V1/Admin/KB/AdminKBFeedbackController.php`
- List and resolve feedback

---

## 5. Routes

```php
// Public KB routes (no auth)
Route::prefix('kb')->group(function () {
    Route::get('/articles', [KBArticleController::class, 'index']);
    Route::get('/articles/featured', [KBArticleController::class, 'featured']);
    Route::get('/articles/popular', [KBArticleController::class, 'popular']);
    Route::get('/articles/{slug}', [KBArticleController::class, 'show']);
    Route::post('/articles/{article}/feedback', [KBFeedbackController::class, 'store']);

    Route::get('/categories', [KBCategoryController::class, 'index']);
    Route::get('/categories/{slug}', [KBCategoryController::class, 'show']);

    Route::get('/search', [KBSearchController::class, 'search']);
    Route::get('/search/suggest', [KBSearchController::class, 'suggest']);
    Route::get('/search/popular', [KBSearchController::class, 'popular']);
});

// Admin KB routes (require admin auth)
Route::prefix('admin/kb')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('articles', AdminKBArticleController::class);
    Route::post('/articles/{article}/publish', [AdminKBArticleController::class, 'publish']);
    Route::post('/articles/{article}/unpublish', [AdminKBArticleController::class, 'unpublish']);
    Route::post('/articles/{article}/archive', [AdminKBArticleController::class, 'archive']);

    Route::apiResource('categories', AdminKBCategoryController::class);
    Route::put('/categories/order', [AdminKBCategoryController::class, 'updateOrder']);

    Route::get('/feedback', [AdminKBFeedbackController::class, 'index']);
    Route::post('/feedback/{feedback}/resolve', [AdminKBFeedbackController::class, 'resolve']);
});
```

---

## 6. Test Requirements

### Feature Tests
- `tests/Feature/Api/KB/KBArticleTest.php` - Public article endpoints
- `tests/Feature/Api/KB/KBCategoryTest.php` - Public category endpoints
- `tests/Feature/Api/KB/KBSearchTest.php` - Search endpoints
- `tests/Feature/Api/KB/KBFeedbackTest.php` - Feedback endpoints
- `tests/Feature/Api/Admin/KB/AdminKBArticleTest.php` - Admin article management
- `tests/Feature/Api/Admin/KB/AdminKBCategoryTest.php` - Admin category management

### Unit Tests
- `tests/Unit/Services/KnowledgeBase/KBArticleServiceTest.php`
- `tests/Unit/Services/KnowledgeBase/KBCategoryServiceTest.php`
- `tests/Unit/Services/KnowledgeBase/KBSearchServiceTest.php`
- `tests/Unit/Services/KnowledgeBase/KBFeedbackServiceTest.php`

---

## 7. Implementation Checklist

- [ ] Create KBArticleService
- [ ] Create KBCategoryService
- [ ] Create KBSearchService
- [ ] Create KBFeedbackService
- [ ] Create KB Data classes
- [ ] Create public controllers
- [ ] Create admin controllers
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 8. Business Rules

### Article Rules
- Only published articles visible to public
- View count increments on each view
- Slugs must be unique
- Articles can have multiple tags

### Search Rules
- Search queries are logged for analytics
- Results ranked by relevance
- Suggestions based on popular searches

### Feedback Rules
- Anonymous feedback allowed
- Admin can resolve feedback
