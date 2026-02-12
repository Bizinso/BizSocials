# Knowledge Base Module Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2025-02-06
- **Module**: Knowledge Base & Documentation
- **Support Model**: L2/L3 Support Only (Self-Service First)

---

## 1. Overview

### 1.1 Purpose
The Knowledge Base module provides comprehensive self-service documentation enabling tenants to:
- Understand how the platform works
- Configure their system independently
- Troubleshoot common issues
- Access best practices and tutorials

### 1.2 Design Philosophy
```
┌─────────────────────────────────────────────────────────────────┐
│                    SELF-SERVICE FIRST                          │
├─────────────────────────────────────────────────────────────────┤
│  User Issue → Search KB → Find Solution → Resolved             │
│       ↓                                                         │
│  Not Found → Browse Categories → Find Related → Resolved        │
│       ↓                                                         │
│  Still Stuck → Video Tutorial → Step-by-Step → Resolved         │
│       ↓                                                         │
│  Complex Issue → Submit Ticket → L2/L3 Support                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Data Model

### 2.1 Knowledge Base Categories
```sql
CREATE TABLE kb_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Hierarchy
    parent_id BIGINT UNSIGNED NULL,

    -- Content
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(7) NULL,

    -- Visibility
    is_public BOOLEAN DEFAULT TRUE,
    visibility ENUM('all', 'authenticated', 'specific_plans') DEFAULT 'all',
    allowed_plans JSON NULL,

    -- Ordering
    sort_order INT DEFAULT 0,

    -- Metadata
    article_count INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES kb_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_visibility (is_public, visibility)
);
```

### 2.2 Knowledge Base Articles
```sql
CREATE TABLE kb_articles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,
    category_id BIGINT UNSIGNED NOT NULL,

    -- Content
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    content LONGTEXT NOT NULL,
    content_format ENUM('markdown', 'html', 'rich_text') DEFAULT 'markdown',

    -- Media
    featured_image VARCHAR(500) NULL,
    video_url VARCHAR(500) NULL,
    video_duration INT NULL,

    -- Classification
    article_type ENUM(
        'getting_started',
        'how_to',
        'tutorial',
        'reference',
        'troubleshooting',
        'faq',
        'best_practice',
        'release_note',
        'api_documentation'
    ) NOT NULL,

    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',

    -- Visibility
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    visibility ENUM('all', 'authenticated', 'specific_plans') DEFAULT 'all',
    allowed_plans JSON NULL,

    -- SEO
    meta_title VARCHAR(70) NULL,
    meta_description VARCHAR(160) NULL,
    meta_keywords JSON NULL,

    -- Versioning
    version INT DEFAULT 1,

    -- Author
    author_id BIGINT UNSIGNED NOT NULL,
    last_edited_by BIGINT UNSIGNED NULL,

    -- Statistics
    view_count INT DEFAULT 0,
    helpful_count INT DEFAULT 0,
    not_helpful_count INT DEFAULT 0,

    -- Timestamps
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category_slug (category_id, slug),
    INDEX idx_status (status),
    INDEX idx_type (article_type),
    INDEX idx_featured (is_featured, status),
    FULLTEXT INDEX ft_search (title, excerpt, content)
);
```

### 2.3 Article Tags
```sql
CREATE TABLE kb_tags (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_slug (slug)
);

CREATE TABLE kb_article_tags (
    article_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES kb_tags(id) ON DELETE CASCADE
);
```

### 2.4 Related Articles
```sql
CREATE TABLE kb_article_relations (
    article_id BIGINT UNSIGNED NOT NULL,
    related_article_id BIGINT UNSIGNED NOT NULL,
    relation_type ENUM('related', 'prerequisite', 'next_step') DEFAULT 'related',
    sort_order INT DEFAULT 0,

    PRIMARY KEY (article_id, related_article_id),
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (related_article_id) REFERENCES kb_articles(id) ON DELETE CASCADE
);
```

### 2.5 Article Feedback
```sql
CREATE TABLE kb_article_feedback (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,
    article_id BIGINT UNSIGNED NOT NULL,

    -- Feedback
    is_helpful BOOLEAN NOT NULL,
    feedback_text TEXT NULL,
    feedback_category ENUM(
        'outdated',
        'incomplete',
        'unclear',
        'incorrect',
        'helpful',
        'other'
    ) NULL,

    -- User Info
    user_id BIGINT UNSIGNED NULL,
    tenant_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,

    -- Status
    status ENUM('pending', 'reviewed', 'actioned', 'dismissed') DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    admin_notes TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    INDEX idx_article (article_id),
    INDEX idx_status (status),
    INDEX idx_helpful (is_helpful)
);
```

### 2.6 Article Versions (History)
```sql
CREATE TABLE kb_article_versions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    article_id BIGINT UNSIGNED NOT NULL,
    version INT NOT NULL,

    -- Content Snapshot
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,

    -- Change Info
    change_summary TEXT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_version (article_id, version),
    INDEX idx_article (article_id)
);
```

### 2.7 Search Analytics
```sql
CREATE TABLE kb_search_analytics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Search Info
    search_query VARCHAR(255) NOT NULL,
    search_query_normalized VARCHAR(255) NOT NULL,
    results_count INT NOT NULL,

    -- User Behavior
    clicked_article_id BIGINT UNSIGNED NULL,
    search_successful BOOLEAN NULL,

    -- User Info
    user_id BIGINT UNSIGNED NULL,
    tenant_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(100) NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_query (search_query_normalized),
    INDEX idx_results (results_count),
    INDEX idx_created (created_at)
);
```

---

## 3. Category Structure

### 3.1 Main Categories
```
📚 Knowledge Base
├── 🚀 Getting Started
│   ├── Welcome to BizSocials
│   ├── Quick Start Guide
│   ├── Platform Overview
│   └── First Steps Checklist
│
├── ⚙️ Configuration
│   ├── Organization Setup
│   ├── User Management
│   ├── Workspace Configuration
│   ├── Social Account Connection
│   ├── Billing & Subscription
│   └── Security Settings
│
├── 📱 Social Media Platforms
│   ├── LinkedIn Integration
│   ├── Facebook Integration
│   ├── Instagram Integration
│   ├── Twitter/X Integration
│   ├── YouTube Integration
│   ├── TikTok Integration
│   ├── Pinterest Integration
│   ├── Google Business Profile
│   └── Other Platforms
│
├── 📝 Content Management
│   ├── Creating Posts
│   ├── Media Library
│   ├── Content Calendar
│   ├── Scheduling & Publishing
│   ├── Post Templates
│   └── Bulk Operations
│
├── 📊 Analytics & Reporting
│   ├── Dashboard Overview
│   ├── Post Analytics
│   ├── Account Analytics
│   ├── Custom Reports
│   └── Exporting Data
│
├── 📥 Social Inbox
│   ├── Managing Messages
│   ├── Comments & Mentions
│   ├── Response Templates
│   └── Team Collaboration
│
├── 👥 Team Collaboration
│   ├── Roles & Permissions
│   ├── Approval Workflows
│   ├── Team Communication
│   └── Activity Tracking
│
├── 🔧 Troubleshooting
│   ├── Connection Issues
│   ├── Publishing Errors
│   ├── Account Problems
│   ├── Performance Issues
│   └── Error Messages Guide
│
├── 💡 Best Practices
│   ├── Content Strategy
│   ├── Engagement Tips
│   ├── Platform Guidelines
│   └── Industry Guides
│
├── 🔌 API & Integrations
│   ├── API Documentation
│   ├── Webhooks
│   ├── Third-party Integrations
│   └── Custom Integrations
│
├── ❓ FAQs
│   ├── General Questions
│   ├── Billing FAQs
│   ├── Technical FAQs
│   └── Feature FAQs
│
└── 📋 Release Notes
    ├── Latest Updates
    ├── Feature Releases
    └── Changelog
```

---

## 4. Article Templates

### 4.1 Getting Started Template
```markdown
# [Article Title]

## Overview
Brief description of what this article covers and why it's important.

## Prerequisites
- Requirement 1
- Requirement 2

## Time Required
Approximately X minutes

## Steps

### Step 1: [Action]
Description of the step.

![Screenshot](image_url)

### Step 2: [Action]
Description of the step.

```tip
Pro tip or important note
```

### Step 3: [Action]
Description of the step.

## What's Next
- [Link to next article]
- [Link to related article]

## Need Help?
If you're still having issues, check our [Troubleshooting Guide](link) or [submit a support ticket](link).
```

### 4.2 Troubleshooting Template
```markdown
# Troubleshooting: [Issue Name]

## Symptoms
- Symptom 1
- Symptom 2
- Error message: "Error text"

## Common Causes
| Cause | Likelihood |
|-------|------------|
| Cause 1 | High |
| Cause 2 | Medium |
| Cause 3 | Low |

## Solutions

### Solution 1: [Most Common Fix]
**When to try**: Description of when this applies.

1. Step 1
2. Step 2
3. Step 3

### Solution 2: [Alternative Fix]
**When to try**: Description of when this applies.

1. Step 1
2. Step 2

### Solution 3: [Advanced Fix]
**When to try**: If above solutions don't work.

1. Step 1
2. Step 2

## Prevention
How to prevent this issue in the future.

## Still Having Issues?
If none of these solutions work:
1. Collect the following information:
   - Screenshot of the error
   - Steps to reproduce
   - Browser and OS version
2. [Submit a support ticket](link)
```

### 4.3 How-To Template
```markdown
# How to [Accomplish Task]

## Overview
Brief description of what you'll learn.

## Use Cases
- Use case 1
- Use case 2

## Before You Begin
- [ ] Prerequisite 1
- [ ] Prerequisite 2

## Instructions

### Method 1: Using the UI

#### Step 1: Navigate to [Location]
Description with screenshot.

#### Step 2: [Action]
Description with screenshot.

### Method 2: Using the API (Optional)

```bash
curl -X POST https://api.bizsocials.com/v1/endpoint \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"key": "value"}'
```

## Tips & Best Practices
- Tip 1
- Tip 2

## Related Articles
- [Related Article 1](link)
- [Related Article 2](link)
```

---

## 5. Search Functionality

### 5.1 Search Implementation
```php
<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBSearchAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KBSearchService
{
    private const MIN_QUERY_LENGTH = 2;
    private const MAX_RESULTS = 50;
    private const SNIPPET_LENGTH = 200;

    public function search(string $query, array $filters = []): array
    {
        $query = trim($query);

        if (strlen($query) < self::MIN_QUERY_LENGTH) {
            return [
                'results' => [],
                'total' => 0,
                'suggestions' => $this->getPopularSearches(),
            ];
        }

        $normalizedQuery = $this->normalizeQuery($query);

        // Build search query
        $searchResults = $this->performSearch($normalizedQuery, $filters);

        // Log search analytics
        $this->logSearch($query, $normalizedQuery, $searchResults->count());

        return [
            'results' => $this->formatResults($searchResults, $query),
            'total' => $searchResults->count(),
            'query' => $query,
            'filters_applied' => $filters,
        ];
    }

    private function performSearch(string $query, array $filters): Collection
    {
        $builder = KBArticle::query()
            ->where('status', 'published')
            ->where('is_public', true);

        // Full-text search
        $builder->whereRaw(
            "MATCH(title, excerpt, content) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$query]
        );

        // Apply filters
        if (!empty($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['article_type'])) {
            $builder->where('article_type', $filters['article_type']);
        }

        if (!empty($filters['difficulty_level'])) {
            $builder->where('difficulty_level', $filters['difficulty_level']);
        }

        // Add relevance score
        $builder->selectRaw(
            "*, MATCH(title, excerpt, content) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance",
            [$query]
        );

        return $builder
            ->orderByDesc('relevance')
            ->limit(self::MAX_RESULTS)
            ->get();
    }

    private function formatResults(Collection $results, string $query): array
    {
        return $results->map(function ($article) use ($query) {
            return [
                'id' => $article->uuid,
                'title' => $article->title,
                'excerpt' => $this->highlightSnippet($article->excerpt ?? $article->content, $query),
                'category' => [
                    'id' => $article->category->uuid,
                    'name' => $article->category->name,
                ],
                'article_type' => $article->article_type,
                'difficulty_level' => $article->difficulty_level,
                'url' => route('kb.article', [$article->category->slug, $article->slug]),
                'relevance' => $article->relevance,
                'view_count' => $article->view_count,
                'helpful_percentage' => $this->calculateHelpfulPercentage($article),
            ];
        })->toArray();
    }

    private function highlightSnippet(string $content, string $query): string
    {
        // Strip HTML/Markdown
        $text = strip_tags($content);
        $text = preg_replace('/[#*`\[\]()]/', '', $text);

        // Find query position
        $pos = stripos($text, $query);

        if ($pos !== false) {
            $start = max(0, $pos - 50);
            $snippet = substr($text, $start, self::SNIPPET_LENGTH);

            // Add ellipsis if needed
            if ($start > 0) {
                $snippet = '...' . $snippet;
            }
            if (strlen($text) > $start + self::SNIPPET_LENGTH) {
                $snippet .= '...';
            }

            // Highlight query
            $snippet = preg_replace(
                '/(' . preg_quote($query, '/') . ')/i',
                '<mark>$1</mark>',
                $snippet
            );

            return $snippet;
        }

        return substr($text, 0, self::SNIPPET_LENGTH) . '...';
    }

    private function normalizeQuery(string $query): string
    {
        // Remove special characters
        $query = preg_replace('/[^\w\s]/', ' ', $query);

        // Remove extra spaces
        $query = preg_replace('/\s+/', ' ', $query);

        return strtolower(trim($query));
    }

    private function logSearch(string $query, string $normalized, int $resultCount): void
    {
        KBSearchAnalytics::create([
            'search_query' => $query,
            'search_query_normalized' => $normalized,
            'results_count' => $resultCount,
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()?->tenant_id,
            'session_id' => session()->getId(),
        ]);
    }

    public function getPopularSearches(int $limit = 10): array
    {
        return KBSearchAnalytics::query()
            ->select('search_query_normalized')
            ->selectRaw('COUNT(*) as search_count')
            ->where('results_count', '>', 0)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('search_query_normalized')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->pluck('search_query_normalized')
            ->toArray();
    }

    public function getSuggestions(string $query): array
    {
        return KBArticle::query()
            ->where('status', 'published')
            ->where('title', 'like', "%{$query}%")
            ->limit(5)
            ->pluck('title')
            ->toArray();
    }

    private function calculateHelpfulPercentage(KBArticle $article): ?float
    {
        $total = $article->helpful_count + $article->not_helpful_count;

        if ($total === 0) {
            return null;
        }

        return round(($article->helpful_count / $total) * 100, 1);
    }
}
```

### 5.2 Search API Endpoints
```
GET  /api/v1/kb/search
     ?q={query}
     &category={category_id}
     &type={article_type}
     &difficulty={level}
     &page={page}
     &per_page={per_page}

GET  /api/v1/kb/search/suggestions?q={partial_query}
GET  /api/v1/kb/search/popular
```

---

## 6. Troubleshooting Guides

### 6.1 Error Code Reference
```
┌──────────────────────────────────────────────────────────────────┐
│                    ERROR CODE CATEGORIES                         │
├──────────────────────────────────────────────────────────────────┤
│  CONNECTION ERRORS (CONN-XXX)                                    │
│  ├── CONN-001: OAuth authorization failed                        │
│  ├── CONN-002: Token expired or revoked                          │
│  ├── CONN-003: Rate limit exceeded                               │
│  ├── CONN-004: Platform API unavailable                          │
│  └── CONN-005: Invalid credentials                               │
│                                                                  │
│  PUBLISHING ERRORS (PUB-XXX)                                     │
│  ├── PUB-001: Content exceeds character limit                    │
│  ├── PUB-002: Invalid media format                               │
│  ├── PUB-003: Media file too large                               │
│  ├── PUB-004: Scheduling time in past                            │
│  ├── PUB-005: Account publishing limit reached                   │
│  └── PUB-006: Content policy violation                           │
│                                                                  │
│  ACCOUNT ERRORS (ACC-XXX)                                        │
│  ├── ACC-001: Account disconnected                               │
│  ├── ACC-002: Insufficient permissions                           │
│  ├── ACC-003: Account suspended by platform                      │
│  └── ACC-004: Page/profile not found                             │
│                                                                  │
│  SYSTEM ERRORS (SYS-XXX)                                         │
│  ├── SYS-001: Internal server error                              │
│  ├── SYS-002: Service temporarily unavailable                    │
│  ├── SYS-003: Request timeout                                    │
│  └── SYS-004: Database connection error                          │
└──────────────────────────────────────────────────────────────────┘
```

### 6.2 Troubleshooting Decision Tree
```
                    ┌─────────────────┐
                    │  Issue Type?    │
                    └────────┬────────┘
           ┌─────────────────┼─────────────────┐
           ▼                 ▼                 ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │  Connection  │  │  Publishing  │  │   Account    │
    └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
           │                 │                 │
           ▼                 ▼                 ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │ Check token  │  │ Check content│  │ Verify perms │
    │ expiration   │  │ requirements │  │ on platform  │
    └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
           │                 │                 │
    ┌──────▼───────┐  ┌──────▼───────┐  ┌──────▼───────┐
    │  Expired?    │  │  Compliant?  │  │  Has perms?  │
    └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       Yes │ No          No │ Yes          No │ Yes
           ▼                 ▼                 ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │ Reconnect    │  │ Fix content  │  │ Re-auth with │
    │ account      │  │ & retry      │  │ new perms    │
    └──────────────┘  └──────────────┘  └──────────────┘
```

---

## 7. API Endpoints

### 7.1 Public Endpoints (No Auth Required for Public Articles)
```
# Categories
GET    /api/v1/kb/categories
GET    /api/v1/kb/categories/{slug}

# Articles
GET    /api/v1/kb/articles
GET    /api/v1/kb/articles/featured
GET    /api/v1/kb/articles/{category_slug}/{article_slug}
GET    /api/v1/kb/articles/{id}/related

# Search
GET    /api/v1/kb/search?q={query}
GET    /api/v1/kb/search/suggestions?q={partial}

# Feedback (Auth Optional)
POST   /api/v1/kb/articles/{id}/feedback
```

### 7.2 Super Admin Endpoints
```
# Category Management
POST   /api/v1/admin/kb/categories
PUT    /api/v1/admin/kb/categories/{id}
DELETE /api/v1/admin/kb/categories/{id}
PUT    /api/v1/admin/kb/categories/reorder

# Article Management
GET    /api/v1/admin/kb/articles
POST   /api/v1/admin/kb/articles
PUT    /api/v1/admin/kb/articles/{id}
DELETE /api/v1/admin/kb/articles/{id}
POST   /api/v1/admin/kb/articles/{id}/publish
POST   /api/v1/admin/kb/articles/{id}/unpublish
GET    /api/v1/admin/kb/articles/{id}/versions
POST   /api/v1/admin/kb/articles/{id}/restore/{version}

# Tag Management
GET    /api/v1/admin/kb/tags
POST   /api/v1/admin/kb/tags
DELETE /api/v1/admin/kb/tags/{id}

# Feedback Management
GET    /api/v1/admin/kb/feedback
PUT    /api/v1/admin/kb/feedback/{id}

# Analytics
GET    /api/v1/admin/kb/analytics/overview
GET    /api/v1/admin/kb/analytics/search
GET    /api/v1/admin/kb/analytics/articles/{id}
```

---

## 8. Frontend Components

### 8.1 Knowledge Base Home
```vue
<template>
  <div class="kb-home">
    <!-- Hero Search -->
    <section class="kb-hero">
      <h1>How can we help you?</h1>
      <KBSearchBar
        v-model="searchQuery"
        :suggestions="suggestions"
        @search="handleSearch"
      />
    </section>

    <!-- Quick Links -->
    <section class="kb-quick-links">
      <h2>Popular Topics</h2>
      <div class="quick-links-grid">
        <QuickLinkCard
          v-for="link in quickLinks"
          :key="link.id"
          :icon="link.icon"
          :title="link.title"
          :description="link.description"
          :to="link.url"
        />
      </div>
    </section>

    <!-- Categories -->
    <section class="kb-categories">
      <h2>Browse by Category</h2>
      <div class="categories-grid">
        <CategoryCard
          v-for="category in categories"
          :key="category.id"
          :category="category"
        />
      </div>
    </section>

    <!-- Featured Articles -->
    <section class="kb-featured">
      <h2>Featured Articles</h2>
      <ArticleList :articles="featuredArticles" />
    </section>

    <!-- Still Need Help -->
    <section class="kb-support-cta">
      <h2>Still need help?</h2>
      <p>Our support team is here to assist you.</p>
      <router-link to="/support/tickets/new" class="btn btn-primary">
        Contact Support
      </router-link>
    </section>
  </div>
</template>
```

### 8.2 Article Page
```vue
<template>
  <div class="kb-article">
    <!-- Breadcrumb -->
    <nav class="kb-breadcrumb">
      <router-link to="/kb">Knowledge Base</router-link>
      <span>/</span>
      <router-link :to="`/kb/${category.slug}`">{{ category.name }}</router-link>
      <span>/</span>
      <span>{{ article.title }}</span>
    </nav>

    <!-- Article Header -->
    <header class="article-header">
      <div class="article-meta">
        <span class="article-type">{{ formatArticleType(article.article_type) }}</span>
        <span class="difficulty" :class="article.difficulty_level">
          {{ article.difficulty_level }}
        </span>
      </div>
      <h1>{{ article.title }}</h1>
      <p class="article-excerpt">{{ article.excerpt }}</p>
      <div class="article-info">
        <span>Last updated: {{ formatDate(article.updated_at) }}</span>
        <span>{{ article.view_count }} views</span>
      </div>
    </header>

    <!-- Table of Contents -->
    <aside class="article-toc" v-if="tableOfContents.length">
      <h3>On this page</h3>
      <ul>
        <li v-for="heading in tableOfContents" :key="heading.id">
          <a :href="`#${heading.id}`">{{ heading.text }}</a>
        </li>
      </ul>
    </aside>

    <!-- Article Content -->
    <article class="article-content" v-html="renderedContent"></article>

    <!-- Related Articles -->
    <section class="related-articles" v-if="relatedArticles.length">
      <h3>Related Articles</h3>
      <ArticleList :articles="relatedArticles" layout="compact" />
    </section>

    <!-- Feedback -->
    <section class="article-feedback">
      <h3>Was this article helpful?</h3>
      <div class="feedback-buttons">
        <button @click="submitFeedback(true)" :disabled="feedbackSubmitted">
          👍 Yes
        </button>
        <button @click="submitFeedback(false)" :disabled="feedbackSubmitted">
          👎 No
        </button>
      </div>

      <div v-if="showFeedbackForm" class="feedback-form">
        <textarea
          v-model="feedbackText"
          placeholder="How can we improve this article?"
        ></textarea>
        <button @click="submitDetailedFeedback">Submit Feedback</button>
      </div>
    </section>
  </div>
</template>
```

---

## 9. Content Guidelines

### 9.1 Writing Standards
```
┌─────────────────────────────────────────────────────────────────┐
│                    WRITING GUIDELINES                           │
├─────────────────────────────────────────────────────────────────┤
│  VOICE & TONE                                                   │
│  • Professional but friendly                                    │
│  • Clear and concise                                            │
│  • Action-oriented                                              │
│  • Encouraging                                                  │
│                                                                 │
│  STRUCTURE                                                      │
│  • Start with what the user will achieve                        │
│  • Use numbered steps for procedures                            │
│  • One action per step                                          │
│  • Include screenshots for complex UI actions                   │
│                                                                 │
│  FORMATTING                                                     │
│  • Use headings to break up content (H2, H3)                    │
│  • Use bullet points for lists                                  │
│  • Use tables for comparisons                                   │
│  • Use callout boxes for tips, warnings, notes                  │
│                                                                 │
│  ACCESSIBILITY                                                  │
│  • Add alt text to all images                                   │
│  • Use descriptive link text (not "click here")                 │
│  • Ensure sufficient color contrast                             │
│  • Structure content logically                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 9.2 Callout Types
```markdown
:::tip
Helpful tips and best practices
:::

:::note
Important information to be aware of
:::

:::warning
Cautions and potential issues
:::

:::danger
Critical warnings about destructive actions
:::

:::info
Additional context or background information
:::
```

---

## 10. Analytics & Metrics

### 10.1 Key Metrics Dashboard
```
┌─────────────────────────────────────────────────────────────────┐
│                  KNOWLEDGE BASE METRICS                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CONTENT HEALTH                    SEARCH EFFECTIVENESS         │
│  ┌─────────────────────┐          ┌─────────────────────┐       │
│  │ Total Articles: 245 │          │ Searches/Day: 1,234  │       │
│  │ Published: 220      │          │ Zero Results: 8%     │       │
│  │ Draft: 25           │          │ Avg Results: 12.4    │       │
│  │ Avg Helpful: 87%    │          │ Click Rate: 45%      │       │
│  └─────────────────────┘          └─────────────────────┘       │
│                                                                 │
│  TOP VIEWED ARTICLES              TOP SEARCHES (NO RESULTS)     │
│  ┌─────────────────────────────┐  ┌─────────────────────────┐   │
│  │ 1. Getting Started (15K)    │  │ 1. "bulk upload" (89)   │   │
│  │ 2. Connect LinkedIn (12K)   │  │ 2. "api key" (67)       │   │
│  │ 3. Scheduling Posts (10K)   │  │ 3. "white label" (45)   │   │
│  │ 4. Analytics Guide (8K)     │  │ 4. "mobile app" (34)    │   │
│  │ 5. Team Permissions (7K)    │  │ 5. "sso login" (28)     │   │
│  └─────────────────────────────┘  └─────────────────────────┘   │
│                                                                 │
│  FEEDBACK NEEDING ATTENTION                                     │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ Article: "Connect Instagram" - 15 reports of outdated info  ││
│  │ Article: "API Rate Limits" - 8 reports of unclear content   ││
│  │ Article: "Bulk Scheduling" - 5 reports of missing steps     ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

---

## 11. Video Tutorial System

### 11.1 Video Integration
```sql
CREATE TABLE kb_videos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Video Info
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,

    -- Video Source
    video_provider ENUM('youtube', 'vimeo', 'wistia', 'self_hosted') NOT NULL,
    video_id VARCHAR(100) NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,

    -- Metadata
    duration_seconds INT NOT NULL,
    transcript TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_provider (video_provider)
);

CREATE TABLE kb_article_videos (
    article_id BIGINT UNSIGNED NOT NULL,
    video_id BIGINT UNSIGNED NOT NULL,
    timestamp_seconds INT NULL,
    sort_order INT DEFAULT 0,

    PRIMARY KEY (article_id, video_id),
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES kb_videos(id) ON DELETE CASCADE
);
```

---

## 12. Contextual Help System

### 12.1 In-App Help Integration
```typescript
// Help Tooltip Component
interface HelpContext {
  feature: string;
  section?: string;
  action?: string;
}

interface HelpContent {
  title: string;
  content: string;
  articleUrl?: string;
  videoUrl?: string;
}

// Usage in Vue components
<template>
  <div class="form-group">
    <label>
      Schedule Post
      <HelpTooltip context="scheduling.time_selection" />
    </label>
    <DateTimePicker v-model="scheduledTime" />
  </div>
</template>

// Help content mapping
const helpContentMap: Record<string, HelpContent> = {
  'scheduling.time_selection': {
    title: 'Choosing the Best Time',
    content: 'Select a time when your audience is most active...',
    articleUrl: '/kb/content-management/scheduling-posts',
  },
  'social.connect_linkedin': {
    title: 'Connecting LinkedIn',
    content: 'Click to authorize BizSocials to post on your behalf...',
    articleUrl: '/kb/social-media-platforms/linkedin-integration',
    videoUrl: '/kb/videos/connect-linkedin',
  },
};
```

---

## 13. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-02-06 | System | Initial specification |
