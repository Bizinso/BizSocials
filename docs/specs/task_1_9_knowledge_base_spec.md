# Task 1.9: Knowledge Base Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.9 Knowledge Base Migrations
- **Dependencies**: Task 1.1 (Platform Admin) - COMPLETED

---

## 1. Overview

This task implements the Knowledge Base system for self-service documentation. The KB is a platform-level resource managed by SuperAdmins, providing help articles, tutorials, and troubleshooting guides for all tenants.

### Entities to Implement
1. **KBCategory** - Categories for organizing articles (hierarchical)
2. **KBArticle** - Knowledge base articles with versioning
3. **KBTag** - Tags for article classification
4. **KBArticleTag** - Many-to-many pivot for article tags
5. **KBArticleRelation** - Related article links (prerequisite, next_step, related)
6. **KBArticleFeedback** - User feedback on articles
7. **KBArticleVersion** - Article version history
8. **KBSearchAnalytic** - Search analytics tracking

---

## 2. Enums

### 2.1 KBArticleType Enum
**File**: `app/Enums/KnowledgeBase/KBArticleType.php`

```php
enum KBArticleType: string
{
    case GETTING_STARTED = 'getting_started';
    case HOW_TO = 'how_to';
    case TUTORIAL = 'tutorial';
    case REFERENCE = 'reference';
    case TROUBLESHOOTING = 'troubleshooting';
    case FAQ = 'faq';
    case BEST_PRACTICE = 'best_practice';
    case RELEASE_NOTE = 'release_note';
    case API_DOCUMENTATION = 'api_documentation';

    public function label(): string;
    public function icon(): string;
    public function description(): string;
}
```

### 2.2 KBArticleStatus Enum
**File**: `app/Enums/KnowledgeBase/KBArticleStatus.php`

```php
enum KBArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string;
    public function isVisible(): bool;  // PUBLISHED only
    public function canTransitionTo(KBArticleStatus $status): bool;
}
```

**Valid Transitions**:
- DRAFT → PUBLISHED
- DRAFT → ARCHIVED
- PUBLISHED → DRAFT
- PUBLISHED → ARCHIVED
- ARCHIVED → DRAFT
- ARCHIVED → PUBLISHED

### 2.3 KBDifficultyLevel Enum
**File**: `app/Enums/KnowledgeBase/KBDifficultyLevel.php`

```php
enum KBDifficultyLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public function label(): string;
    public function sortOrder(): int;  // 1, 2, 3
}
```

### 2.4 KBVisibility Enum
**File**: `app/Enums/KnowledgeBase/KBVisibility.php`

```php
enum KBVisibility: string
{
    case ALL = 'all';                      // Public, visible to everyone
    case AUTHENTICATED = 'authenticated';   // Only logged-in users
    case SPECIFIC_PLANS = 'specific_plans'; // Only certain plan subscribers

    public function label(): string;
    public function isPublic(): bool;  // ALL only
    public function requiresAuth(): bool;  // AUTHENTICATED, SPECIFIC_PLANS
}
```

### 2.5 KBContentFormat Enum
**File**: `app/Enums/KnowledgeBase/KBContentFormat.php`

```php
enum KBContentFormat: string
{
    case MARKDOWN = 'markdown';
    case HTML = 'html';
    case RICH_TEXT = 'rich_text';

    public function label(): string;
}
```

### 2.6 KBFeedbackCategory Enum
**File**: `app/Enums/KnowledgeBase/KBFeedbackCategory.php`

```php
enum KBFeedbackCategory: string
{
    case OUTDATED = 'outdated';
    case INCOMPLETE = 'incomplete';
    case UNCLEAR = 'unclear';
    case INCORRECT = 'incorrect';
    case HELPFUL = 'helpful';
    case OTHER = 'other';

    public function label(): string;
    public function isPositive(): bool;  // HELPFUL only
    public function isNegative(): bool;  // OUTDATED, INCOMPLETE, UNCLEAR, INCORRECT
}
```

### 2.7 KBFeedbackStatus Enum
**File**: `app/Enums/KnowledgeBase/KBFeedbackStatus.php`

```php
enum KBFeedbackStatus: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case ACTIONED = 'actioned';
    case DISMISSED = 'dismissed';

    public function label(): string;
    public function isOpen(): bool;  // PENDING only
    public function isClosed(): bool;  // REVIEWED, ACTIONED, DISMISSED
}
```

### 2.8 KBRelationType Enum
**File**: `app/Enums/KnowledgeBase/KBRelationType.php`

```php
enum KBRelationType: string
{
    case RELATED = 'related';
    case PREREQUISITE = 'prerequisite';
    case NEXT_STEP = 'next_step';

    public function label(): string;
    public function inverseLabel(): string;  // For reverse relationship display
}
```

---

## 3. Migrations

### 3.1 Create KB Categories Table
**File**: `database/migrations/2026_02_06_900001_create_kb_categories_table.php`

```php
Schema::create('kb_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('parent_id')->nullable();  // For hierarchy
    $table->string('name', 100);
    $table->string('slug', 100)->unique();
    $table->text('description')->nullable();
    $table->string('icon', 50)->nullable();
    $table->string('color', 7)->nullable();  // Hex color
    $table->boolean('is_public')->default(true);
    $table->string('visibility', 20)->default('all');  // KBVisibility
    $table->json('allowed_plans')->nullable();  // Plan IDs for SPECIFIC_PLANS
    $table->integer('sort_order')->default(0);
    $table->integer('article_count')->default(0);  // Cached count
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('parent_id');
    $table->index('sort_order');
    $table->index(['is_public', 'visibility']);

    // Foreign key (self-referencing)
    $table->foreign('parent_id')
        ->references('id')
        ->on('kb_categories')
        ->nullOnDelete();
});
```

### 3.2 Create KB Articles Table
**File**: `database/migrations/2026_02_06_900002_create_kb_articles_table.php`

```php
Schema::create('kb_articles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('category_id');
    $table->string('title', 255);
    $table->string('slug', 255);
    $table->text('excerpt')->nullable();
    $table->longText('content');
    $table->string('content_format', 20)->default('markdown');  // KBContentFormat
    $table->string('featured_image', 500)->nullable();
    $table->string('video_url', 500)->nullable();
    $table->integer('video_duration')->nullable();  // Seconds
    $table->string('article_type', 30);  // KBArticleType
    $table->string('difficulty_level', 20)->default('beginner');  // KBDifficultyLevel
    $table->string('status', 20)->default('draft');  // KBArticleStatus
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_public')->default(true);
    $table->string('visibility', 20)->default('all');  // KBVisibility
    $table->json('allowed_plans')->nullable();
    $table->string('meta_title', 70)->nullable();  // SEO
    $table->string('meta_description', 160)->nullable();  // SEO
    $table->json('meta_keywords')->nullable();  // SEO
    $table->integer('version')->default(1);
    $table->uuid('author_id');  // SuperAdminUser
    $table->uuid('last_edited_by')->nullable();  // SuperAdminUser
    $table->integer('view_count')->default(0);
    $table->integer('helpful_count')->default(0);
    $table->integer('not_helpful_count')->default(0);
    $table->timestamp('published_at')->nullable();
    $table->timestamps();

    // Unique constraint
    $table->unique(['category_id', 'slug'], 'kb_articles_category_slug_unique');

    // Indexes
    $table->index('status');
    $table->index('article_type');
    $table->index('difficulty_level');
    $table->index(['is_featured', 'status']);
    $table->index('published_at');
    $table->index('view_count');

    // Foreign keys
    $table->foreign('category_id')
        ->references('id')
        ->on('kb_categories')
        ->cascadeOnDelete();

    $table->foreign('author_id')
        ->references('id')
        ->on('super_admin_users')
        ->cascadeOnDelete();

    $table->foreign('last_edited_by')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.3 Create KB Tags Table
**File**: `database/migrations/2026_02_06_900003_create_kb_tags_table.php`

```php
Schema::create('kb_tags', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 50);
    $table->string('slug', 50)->unique();
    $table->integer('usage_count')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('usage_count');
});
```

### 3.4 Create KB Article Tags Pivot Table
**File**: `database/migrations/2026_02_06_900004_create_kb_article_tags_table.php`

```php
Schema::create('kb_article_tags', function (Blueprint $table) {
    $table->uuid('article_id');
    $table->uuid('tag_id');
    $table->timestamps();

    // Primary key
    $table->primary(['article_id', 'tag_id']);

    // Foreign keys
    $table->foreign('article_id')
        ->references('id')
        ->on('kb_articles')
        ->cascadeOnDelete();

    $table->foreign('tag_id')
        ->references('id')
        ->on('kb_tags')
        ->cascadeOnDelete();
});
```

### 3.5 Create KB Article Relations Table
**File**: `database/migrations/2026_02_06_900005_create_kb_article_relations_table.php`

```php
Schema::create('kb_article_relations', function (Blueprint $table) {
    $table->uuid('article_id');
    $table->uuid('related_article_id');
    $table->string('relation_type', 20)->default('related');  // KBRelationType
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    // Primary key
    $table->primary(['article_id', 'related_article_id']);

    // Indexes
    $table->index('relation_type');

    // Foreign keys
    $table->foreign('article_id')
        ->references('id')
        ->on('kb_articles')
        ->cascadeOnDelete();

    $table->foreign('related_article_id')
        ->references('id')
        ->on('kb_articles')
        ->cascadeOnDelete();
});
```

### 3.6 Create KB Article Feedback Table
**File**: `database/migrations/2026_02_06_900006_create_kb_article_feedback_table.php`

```php
Schema::create('kb_article_feedback', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('article_id');
    $table->boolean('is_helpful');
    $table->text('feedback_text')->nullable();
    $table->string('feedback_category', 20)->nullable();  // KBFeedbackCategory
    $table->uuid('user_id')->nullable();  // User who gave feedback
    $table->uuid('tenant_id')->nullable();  // Tenant context
    $table->string('session_id', 100)->nullable();  // For anonymous tracking
    $table->string('ip_address', 45)->nullable();
    $table->string('status', 20)->default('pending');  // KBFeedbackStatus
    $table->uuid('reviewed_by')->nullable();  // SuperAdminUser
    $table->timestamp('reviewed_at')->nullable();
    $table->text('admin_notes')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('article_id');
    $table->index('status');
    $table->index('is_helpful');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('article_id')
        ->references('id')
        ->on('kb_articles')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('reviewed_by')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.7 Create KB Article Versions Table
**File**: `database/migrations/2026_02_06_900007_create_kb_article_versions_table.php`

```php
Schema::create('kb_article_versions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('article_id');
    $table->integer('version');
    $table->string('title', 255);
    $table->longText('content');
    $table->text('change_summary')->nullable();
    $table->uuid('changed_by');  // SuperAdminUser
    $table->timestamps();

    // Unique constraint
    $table->unique(['article_id', 'version'], 'kb_article_versions_unique');

    // Indexes
    $table->index('article_id');

    // Foreign keys
    $table->foreign('article_id')
        ->references('id')
        ->on('kb_articles')
        ->cascadeOnDelete();

    $table->foreign('changed_by')
        ->references('id')
        ->on('super_admin_users')
        ->cascadeOnDelete();
});
```

### 3.8 Create KB Search Analytics Table
**File**: `database/migrations/2026_02_06_900008_create_kb_search_analytics_table.php`

```php
Schema::create('kb_search_analytics', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('search_query', 255);
    $table->string('search_query_normalized', 255);
    $table->integer('results_count');
    $table->uuid('clicked_article_id')->nullable();
    $table->boolean('search_successful')->nullable();
    $table->uuid('user_id')->nullable();
    $table->uuid('tenant_id')->nullable();
    $table->string('session_id', 100)->nullable();
    $table->timestamps();

    // Indexes
    $table->index('search_query_normalized');
    $table->index('results_count');
    $table->index('created_at');
    $table->index(['user_id', 'created_at']);

    // Foreign keys
    $table->foreign('clicked_article_id')
        ->references('id')
        ->on('kb_articles')
        ->nullOnDelete();

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

### 4.1 KBCategory Model
**File**: `app/Models/KnowledgeBase/KBCategory.php`

Key methods:
- Relationships: `parent()`, `children()`, `articles()`
- Scopes: `published()`, `topLevel()`, `withPublishedArticles()`, `ordered()`
- Helpers: `isTopLevel()`, `hasChildren()`, `hasArticles()`, `getPath()`, `getDepth()`, `incrementArticleCount()`, `decrementArticleCount()`, `getFullSlugPath()`

### 4.2 KBArticle Model
**File**: `app/Models/KnowledgeBase/KBArticle.php`

Key methods:
- Relationships: `category()`, `author()`, `lastEditedBy()`, `tags()`, `relatedArticles()`, `prerequisiteArticles()`, `nextStepArticles()`, `feedback()`, `versions()`
- Scopes: `published()`, `draft()`, `archived()`, `featured()`, `forCategory()`, `ofType()`, `withDifficulty()`, `searchable()`, `popular()`
- Helpers: `isPublished()`, `isDraft()`, `isArchived()`, `publish()`, `unpublish()`, `archive()`, `incrementViewCount()`, `recordHelpfulVote()`, `recordNotHelpfulVote()`, `getHelpfulPercentage()`, `createVersion()`, `restoreVersion()`, `getUrl()`, `attachTag()`, `detachTag()`, `syncTags()`, `addRelation()`, `removeRelation()`

### 4.3 KBTag Model
**File**: `app/Models/KnowledgeBase/KBTag.php`

Key methods:
- Relationships: `articles()`
- Scopes: `popular()`, `ordered()`, `search()`
- Helpers: `incrementUsageCount()`, `decrementUsageCount()`, `recalculateUsageCount()`

### 4.4 KBArticleTag Model (Pivot)
**File**: `app/Models/KnowledgeBase/KBArticleTag.php`

Key methods:
- Relationships: `article()`, `tag()`

### 4.5 KBArticleRelation Model (Pivot)
**File**: `app/Models/KnowledgeBase/KBArticleRelation.php`

Key methods:
- Relationships: `article()`, `relatedArticle()`
- Scopes: `ofType()`, `prerequisites()`, `nextSteps()`, `related()`
- Helpers: `isPrerequisite()`, `isNextStep()`, `isRelated()`

### 4.6 KBArticleFeedback Model
**File**: `app/Models/KnowledgeBase/KBArticleFeedback.php`

Key methods:
- Relationships: `article()`, `user()`, `tenant()`, `reviewedBy()`
- Scopes: `pending()`, `reviewed()`, `helpful()`, `notHelpful()`, `forArticle()`, `withCategory()`
- Helpers: `markAsReviewed()`, `markAsActioned()`, `dismiss()`, `isPending()`, `isPositive()`

### 4.7 KBArticleVersion Model
**File**: `app/Models/KnowledgeBase/KBArticleVersion.php`

Key methods:
- Relationships: `article()`, `changedBy()`
- Scopes: `forArticle()`, `latestFirst()`
- Helpers: `isLatest()`, `getDiff()`

### 4.8 KBSearchAnalytic Model
**File**: `app/Models/KnowledgeBase/KBSearchAnalytic.php`

Key methods:
- Relationships: `clickedArticle()`, `user()`, `tenant()`
- Scopes: `successful()`, `noResults()`, `inDateRange()`, `forUser()`
- Helpers: `markAsSuccessful()`, `recordClick()`

---

## 5. Factories & Seeders

### Factories
- `KBCategoryFactory` - with states for topLevel(), childOf(), public(), private(), withIcon()
- `KBArticleFactory` - with states for draft(), published(), archived(), featured(), ofType(), byAuthor()
- `KBTagFactory` - with states for popular(), withUsageCount()
- `KBArticleFeedbackFactory` - with states for helpful(), notHelpful(), pending(), reviewed()
- `KBArticleVersionFactory` - with states for forArticle(), byAuthor()
- `KBSearchAnalyticFactory` - with states for successful(), noResults(), withClick()

### Seeders
- `KBCategorySeeder` - Create main KB categories (Getting Started, Configuration, Social Platforms, Content Management, Analytics, Troubleshooting, FAQs, etc.)
- `KBArticleSeeder` - Create sample articles across categories
- `KBTagSeeder` - Create common tags
- `KBArticleFeedbackSeeder` - Create sample feedback
- `KnowledgeBaseSeeder` (Orchestrator)

---

## 6. Test Requirements

Create tests for:
- 8 enum tests (KBArticleType, KBArticleStatus, KBDifficultyLevel, KBVisibility, KBContentFormat, KBFeedbackCategory, KBFeedbackStatus, KBRelationType)
- 8 model tests (KBCategory, KBArticle, KBTag, KBArticleTag, KBArticleRelation, KBArticleFeedback, KBArticleVersion, KBSearchAnalytic)

---

## 7. Implementation Checklist

- [ ] Create KBArticleType enum
- [ ] Create KBArticleStatus enum
- [ ] Create KBDifficultyLevel enum
- [ ] Create KBVisibility enum
- [ ] Create KBContentFormat enum
- [ ] Create KBFeedbackCategory enum
- [ ] Create KBFeedbackStatus enum
- [ ] Create KBRelationType enum
- [ ] Create kb_categories migration
- [ ] Create kb_articles migration
- [ ] Create kb_tags migration
- [ ] Create kb_article_tags migration
- [ ] Create kb_article_relations migration
- [ ] Create kb_article_feedback migration
- [ ] Create kb_article_versions migration
- [ ] Create kb_search_analytics migration
- [ ] Create KBCategory model
- [ ] Create KBArticle model
- [ ] Create KBTag model
- [ ] Create KBArticleTag model
- [ ] Create KBArticleRelation model
- [ ] Create KBArticleFeedback model
- [ ] Create KBArticleVersion model
- [ ] Create KBSearchAnalytic model
- [ ] Create all factories
- [ ] Create all seeders
- [ ] Create all unit tests
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 8. Notes

1. **Platform-Level Resource**: The Knowledge Base is managed by SuperAdmins, not tenants. Articles reference `super_admin_users` for authorship.

2. **Hierarchical Categories**: Categories support parent/child relationships for nested navigation. Use `parent_id` for hierarchy.

3. **Article Versioning**: Every significant edit should create a version record for history tracking.

4. **Visibility Control**: Articles and categories can be restricted to authenticated users or specific plan subscribers.

5. **Search Analytics**: Track all searches to identify content gaps (zero-result searches) and popular topics.

6. **Feedback Loop**: User feedback helps identify outdated or unclear content for improvement.

7. **Cached Counts**: `article_count` on categories and `usage_count` on tags are cached for performance. Increment/decrement on article changes.

8. **SEO Fields**: Include meta_title, meta_description, and meta_keywords for search engine optimization.
