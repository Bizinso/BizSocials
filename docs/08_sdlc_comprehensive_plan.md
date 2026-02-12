# BizSocials — Comprehensive SDLC Plan

**Version:** 1.0
**Date:** February 2026
**Purpose:** Complete Software Development Lifecycle for Production-Ready SaaS Platform
**Classification:** Development Standards & Operations

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Development Methodology](#2-development-methodology)
3. [Environment Strategy](#3-environment-strategy)
4. [Git Workflow & Branching](#4-git-workflow--branching)
5. [Backend Development Standards](#5-backend-development-standards)
6. [Frontend Development Standards](#6-frontend-development-standards)
7. [Database Standards](#7-database-standards)
8. [API Development Standards](#8-api-development-standards)
9. [Testing Strategy](#9-testing-strategy)
10. [Documentation Standards](#10-documentation-standards)
11. [CI/CD Pipeline](#11-cicd-pipeline)
12. [Docker & Containerization](#12-docker--containerization)
13. [Application Performance Monitoring](#13-application-performance-monitoring)
14. [Error Tracking & Alerting](#14-error-tracking--alerting)
15. [Security Practices](#15-security-practices)
16. [Code Review Process](#16-code-review-process)
17. [Release Management](#17-release-management)

---

## 1. Executive Summary

This document defines the complete SDLC for BizSocials, ensuring:
- **Quality**: Test-driven development with 80%+ code coverage
- **Reliability**: Automated CI/CD with zero-downtime deployments
- **Observability**: Full APM, logging, and alerting
- **Security**: OWASP compliance, automated security scanning
- **Scalability**: Containerized architecture ready for horizontal scaling

### Technology Stack Confirmation

| Layer | Technology | Version |
|-------|------------|---------|
| Backend | Laravel | 11.x |
| Frontend | Vue 3 + TypeScript | 3.4+ |
| Database | MySQL | 8.0+ |
| Cache | Redis | 7.x |
| Queue | Laravel Queue (Redis) | - |
| Search | MySQL Full-Text (Phase-1) | - |
| Storage | S3-compatible | - |
| Containerization | Docker + Docker Compose | - |
| Orchestration | Kubernetes (Production) | 1.28+ |
| CI/CD | GitHub Actions | - |
| APM | New Relic / Datadog | - |
| Error Tracking | Sentry | - |
| Logging | ELK Stack / CloudWatch | - |

---

## 2. Development Methodology

### 2.1 Agile Scrum Framework

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           2-WEEK SPRINT CYCLE                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Day 1          Days 2-9           Day 10          Days 11-12    Day 13-14 │
│  ┌─────┐       ┌─────────┐        ┌─────┐         ┌─────────┐   ┌────────┐ │
│  │Sprint│      │Development│       │ Code │        │   QA    │   │Release │ │
│  │Plan  │ ───► │  + Tests │  ───► │Freeze│  ───►  │  + Fix  │ ─►│  Prep  │ │
│  └─────┘       └─────────┘        └─────┘         └─────────┘   └────────┘ │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Sprint Ceremonies

| Ceremony | Duration | Frequency | Participants |
|----------|----------|-----------|--------------|
| Sprint Planning | 2 hours | Start of sprint | Full team |
| Daily Standup | 15 min | Daily | Full team |
| Backlog Refinement | 1 hour | Mid-sprint | PO + Tech Lead |
| Sprint Review | 1 hour | End of sprint | Full team + Stakeholders |
| Retrospective | 1 hour | End of sprint | Full team |

### 2.3 Definition of Done (DoD)

A feature is "Done" when ALL criteria are met:

```
┌─────────────────────────────────────────────────────────────────┐
│                     DEFINITION OF DONE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CODE                                                           │
│  ☐ Feature code complete and follows coding standards           │
│  ☐ All acceptance criteria met                                  │
│  ☐ No TODO comments left in production code                     │
│                                                                 │
│  TESTING                                                        │
│  ☐ Unit tests written (min 80% coverage for new code)           │
│  ☐ Integration tests for API endpoints                          │
│  ☐ E2E tests for critical user flows                            │
│  ☐ All tests passing in CI pipeline                             │
│                                                                 │
│  DOCUMENTATION                                                  │
│  ☐ API documentation updated (OpenAPI/Swagger)                  │
│  ☐ Code comments for complex logic                              │
│  ☐ README updated if setup changes                              │
│                                                                 │
│  REVIEW                                                         │
│  ☐ Code review approved by at least 1 senior developer          │
│  ☐ Security review for auth/data handling changes               │
│                                                                 │
│  DEPLOYMENT                                                     │
│  ☐ Feature works in staging environment                         │
│  ☐ Database migrations tested                                   │
│  ☐ No regression in existing features                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 2.4 Story Point Estimation

| Points | Complexity | Example |
|:------:|------------|---------|
| 1 | Trivial | Fix typo, update config |
| 2 | Simple | Add validation, small UI change |
| 3 | Moderate | New API endpoint, form component |
| 5 | Complex | New feature with multiple components |
| 8 | Very Complex | Cross-cutting feature, major refactor |
| 13 | Epic-level | Should be broken down |

---

## 3. Environment Strategy

### 3.1 Environment Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ENVIRONMENT PIPELINE                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   LOCAL          DEV            STAGING         PRODUCTION                  │
│   ┌─────┐       ┌─────┐        ┌─────┐         ┌─────┐                     │
│   │ Dev │ ───►  │Shared│  ───► │ Pre │   ───►  │Live │                     │
│   │ Box │       │ Dev  │       │Prod │         │Users│                     │
│   └─────┘       └─────┘        └─────┘         └─────┘                     │
│                                                                             │
│   Purpose:      Purpose:       Purpose:        Purpose:                     │
│   Individual    Integration    Final QA        Production                   │
│   development   testing        UAT             traffic                      │
│                                                                             │
│   Data:         Data:          Data:           Data:                        │
│   Seeded        Seeded +       Sanitized       Real                         │
│   test data     shared test    prod copy       customer                     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Environment Configuration

| Environment | URL Pattern | Database | Purpose |
|-------------|-------------|----------|---------|
| Local | localhost:8000 | Local MySQL | Developer machine |
| Dev | dev.bizsocials.io | Shared dev DB | Integration testing |
| Staging | staging.bizsocials.io | Staging DB | UAT, pre-production |
| Production | app.bizsocials.io | Production DB | Live customers |

### 3.3 Environment Variables Strategy

```
┌─────────────────────────────────────────────────────────────────┐
│                 ENVIRONMENT VARIABLE CATEGORIES                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CATEGORY 1: Non-Sensitive (Committed to repo)                  │
│  ├── .env.example          Template with all keys               │
│  ├── .env.testing          Test environment defaults            │
│  └── config/*.php          Laravel config files                 │
│                                                                 │
│  CATEGORY 2: Environment-Specific (CI/CD secrets)               │
│  ├── APP_ENV               local/dev/staging/production         │
│  ├── APP_DEBUG             true/false                           │
│  ├── APP_URL               Environment URL                      │
│  └── LOG_LEVEL             debug/info/warning/error             │
│                                                                 │
│  CATEGORY 3: Secrets (Vault/Secret Manager)                     │
│  ├── DB_PASSWORD           Database credentials                 │
│  ├── REDIS_PASSWORD        Cache credentials                    │
│  ├── JWT_SECRET            Token signing key                    │
│  ├── STRIPE_SECRET_KEY     Payment processing                   │
│  ├── OPENAI_API_KEY        AI service                           │
│  ├── SOCIAL_*_SECRET       OAuth credentials                    │
│  └── SENTRY_DSN            Error tracking                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 3.4 Local Development Setup

Prerequisites checklist for developers:

```
┌─────────────────────────────────────────────────────────────────┐
│                 LOCAL DEVELOPMENT REQUIREMENTS                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  REQUIRED SOFTWARE                                              │
│  ☐ Docker Desktop (latest)                                      │
│  ☐ Node.js 20.x LTS                                             │
│  ☐ PHP 8.3+ (for IDE support)                                   │
│  ☐ Composer 2.x                                                 │
│  ☐ Git 2.x                                                      │
│                                                                 │
│  RECOMMENDED IDE                                                │
│  ☐ VS Code or PhpStorm                                          │
│  ☐ Extensions: PHP Intelephense, Volar, ESLint, Prettier        │
│                                                                 │
│  INITIAL SETUP COMMANDS                                         │
│  $ git clone <repo>                                             │
│  $ cp .env.example .env                                         │
│  $ docker-compose up -d                                         │
│  $ docker-compose exec app composer install                     │
│  $ docker-compose exec app php artisan key:generate             │
│  $ docker-compose exec app php artisan migrate --seed           │
│  $ cd frontend && npm install && npm run dev                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Git Workflow & Branching

### 4.1 Branch Strategy (GitFlow Simplified)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           BRANCH STRUCTURE                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  main ─────────────────────────────────────────────────────────────────►    │
│    │                                        │                               │
│    │ (hotfix)                               │ (release merge)               │
│    ▼                                        ▲                               │
│  hotfix/critical-bug ──────────────────────►│                               │
│                                             │                               │
│  develop ──────────────────────────────────►┼─────────────────────────────► │
│    │         │         │                    │                               │
│    │         │         │                    │                               │
│    ▼         ▼         ▼                    │                               │
│  feature/  feature/  feature/    release/v1.0                               │
│  auth      posts     inbox          │                                       │
│    │         │         │            │                                       │
│    └─────────┴─────────┴────────────┘                                       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Branch Naming Convention

| Branch Type | Pattern | Example |
|-------------|---------|---------|
| Feature | `feature/<ticket>-<short-desc>` | `feature/BIZ-123-post-scheduling` |
| Bugfix | `bugfix/<ticket>-<short-desc>` | `bugfix/BIZ-456-calendar-timezone` |
| Hotfix | `hotfix/<ticket>-<short-desc>` | `hotfix/BIZ-789-login-error` |
| Release | `release/v<version>` | `release/v1.0.0` |

### 4.3 Commit Message Convention

Follow Conventional Commits specification:

```
<type>(<scope>): <subject>

[optional body]

[optional footer]
```

**Types:**

| Type | Description | Example |
|------|-------------|---------|
| `feat` | New feature | `feat(posts): add drag-drop calendar` |
| `fix` | Bug fix | `fix(auth): resolve token refresh race condition` |
| `docs` | Documentation | `docs(api): update authentication endpoints` |
| `style` | Code style (no logic change) | `style(inbox): fix indentation` |
| `refactor` | Code refactoring | `refactor(posts): extract publishing service` |
| `test` | Adding tests | `test(inbox): add reply integration tests` |
| `chore` | Maintenance | `chore(deps): update laravel to 11.5` |
| `perf` | Performance | `perf(analytics): optimize metrics query` |

### 4.4 Pull Request Template

```markdown
## Description
<!-- What does this PR do? -->

## Type of Change
- [ ] Feature
- [ ] Bug fix
- [ ] Refactor
- [ ] Documentation
- [ ] Other: ___

## Related Issues
<!-- Link to Jira/GitHub issues -->
Closes #123

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No new warnings in CI

## Testing Instructions
<!-- How to test this change -->

## Screenshots (if applicable)
<!-- Add screenshots for UI changes -->
```

### 4.5 Branch Protection Rules

| Branch | Rules |
|--------|-------|
| `main` | Require PR, 2 approvals, passing CI, no force push |
| `develop` | Require PR, 1 approval, passing CI |
| `release/*` | Require PR from develop, 2 approvals |

---

## 5. Backend Development Standards

### 5.1 Laravel Project Structure

```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/           # Artisan commands
│   ├── Exceptions/
│   │   └── Handler.php         # Global exception handling
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/         # Versioned API controllers
│   │   │           ├── Auth/
│   │   │           ├── Workspaces/
│   │   │           ├── Posts/
│   │   │           ├── Inbox/
│   │   │           └── Analytics/
│   │   ├── Middleware/
│   │   │   ├── WorkspaceContext.php
│   │   │   ├── CheckRole.php
│   │   │   └── RateLimiting.php
│   │   ├── Requests/           # Form request validation
│   │   └── Resources/          # API resources (transformers)
│   ├── Models/
│   │   ├── Concerns/           # Shared traits
│   │   │   ├── BelongsToWorkspace.php
│   │   │   └── HasUuid.php
│   │   ├── User.php
│   │   ├── Workspace.php
│   │   ├── Post.php
│   │   └── ...
│   ├── Policies/               # Authorization policies
│   ├── Providers/
│   ├── Repositories/           # Data access layer
│   │   ├── Contracts/          # Repository interfaces
│   │   └── Eloquent/           # Eloquent implementations
│   ├── Services/               # Business logic
│   │   ├── Auth/
│   │   ├── Publishing/
│   │   ├── Inbox/
│   │   └── Analytics/
│   └── Jobs/                   # Background jobs
│       ├── Publishing/
│       ├── Inbox/
│       └── Metrics/
├── config/
├── database/
│   ├── factories/              # Model factories for testing
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php                 # API routes
├── tests/
│   ├── Feature/                # Integration tests
│   │   ├── Api/
│   │   └── Jobs/
│   └── Unit/                   # Unit tests
│       ├── Models/
│       ├── Services/
│       └── Repositories/
└── storage/
```

### 5.2 Coding Standards

**PSR-12 + Laravel Conventions:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Publishing;

use App\Models\Post;
use App\Models\PostTarget;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Exceptions\Publishing\PublishingFailedException;
use Illuminate\Support\Facades\Log;

/**
 * Handles post publishing to social platforms.
 */
final class PublishingService
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly SocialApiFactory $socialApiFactory,
    ) {}

    /**
     * Publish a post to all its targets.
     *
     * @throws PublishingFailedException
     */
    public function publish(Post $post): void
    {
        // Validate post is ready for publishing
        $this->validatePublishable($post);

        foreach ($post->targets as $target) {
            $this->publishToTarget($post, $target);
        }

        $this->postRepository->markAsPublished($post);
    }

    private function validatePublishable(Post $post): void
    {
        if ($post->status !== PostStatus::SCHEDULED) {
            throw new PublishingFailedException(
                "Post {$post->id} is not in SCHEDULED status"
            );
        }
    }

    private function publishToTarget(Post $post, PostTarget $target): void
    {
        try {
            $api = $this->socialApiFactory->make($target->socialAccount);
            $externalId = $api->publish($post, $target);

            $target->update([
                'external_post_id' => $externalId,
                'published_at' => now(),
                'status' => TargetStatus::PUBLISHED,
            ]);

            Log::info('Post published', [
                'workspace_id' => $post->workspace_id,
                'post_id' => $post->id,
                'target_id' => $target->id,
                'platform' => $target->socialAccount->platform,
            ]);
        } catch (\Exception $e) {
            $target->update([
                'status' => TargetStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Publishing failed', [
                'workspace_id' => $post->workspace_id,
                'post_id' => $post->id,
                'target_id' => $target->id,
                'error' => $e->getMessage(),
            ]);

            throw new PublishingFailedException(
                "Failed to publish to {$target->socialAccount->platform}",
                previous: $e
            );
        }
    }
}
```

### 5.3 Workspace Tenancy Trait

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Workspace;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a workspace.
 * Automatically scopes all queries to current workspace.
 */
trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope());

        static::creating(function ($model) {
            if (! $model->workspace_id && app()->has('workspace')) {
                $model->workspace_id = app('workspace')->id;
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope query to specific workspace (bypasses global scope).
     */
    public function scopeForWorkspace($query, string $workspaceId)
    {
        return $query->withoutGlobalScope(WorkspaceScope::class)
                     ->where('workspace_id', $workspaceId);
    }
}
```

### 5.4 Repository Pattern

```php
<?php

// Contract (Interface)
namespace App\Repositories\Contracts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    public function findById(string $id): ?Post;

    public function findByIdOrFail(string $id): Post;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): Post;

    public function update(Post $post, array $data): Post;

    public function delete(Post $post): void;

    public function getScheduledForPublishing(): Collection;

    public function markAsPublished(Post $post): void;
}

// Implementation
namespace App\Repositories\Eloquent;

use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;

final class EloquentPostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly Post $model,
    ) {}

    public function findById(string $id): ?Post
    {
        return $this->model->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by_user_id', $filters['created_by']);
        }

        if (isset($filters['date_from'])) {
            $query->where('scheduled_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('scheduled_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    // ... other methods
}
```

### 5.5 Form Request Validation

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Posts;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Post::class);
    }

    public function rules(): array
    {
        return [
            'content_text' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.social_account_id' => [
                'required',
                'uuid',
                Rule::exists('social_accounts', 'id')
                    ->where('workspace_id', $this->workspace->id),
            ],
            'targets.*.platform_specific_text' => ['nullable', 'string', 'max:5000'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*.file' => ['required', 'file', 'mimes:jpg,png,gif,mp4', 'max:50000'],
            'media.*.alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'targets.required' => 'At least one target platform is required.',
            'targets.*.social_account_id.exists' => 'Selected social account not found in this workspace.',
            'scheduled_at.after' => 'Scheduled time must be in the future.',
        ];
    }
}
```

### 5.6 API Resource (Transformer)

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'content_text' => $this->content_text,
            'status' => $this->status->value,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_by' => new UserSummaryResource($this->whenLoaded('createdBy')),
            'targets' => PostTargetResource::collection($this->whenLoaded('targets')),
            'media' => PostMediaResource::collection($this->whenLoaded('media')),
            'approval' => new ApprovalDecisionResource($this->whenLoaded('activeApproval')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

---

## 6. Frontend Development Standards

### 6.1 Vue 3 Project Structure

```
frontend/
├── public/
│   └── favicon.ico
├── src/
│   ├── assets/
│   │   ├── styles/
│   │   │   ├── _variables.scss
│   │   │   ├── _mixins.scss
│   │   │   └── main.scss
│   │   └── images/
│   ├── components/
│   │   ├── common/              # Shared components
│   │   │   ├── AppButton.vue
│   │   │   ├── AppModal.vue
│   │   │   ├── AppDropdown.vue
│   │   │   └── AppPagination.vue
│   │   ├── layout/
│   │   │   ├── AppHeader.vue
│   │   │   ├── AppSidebar.vue
│   │   │   └── WorkspaceSwitcher.vue
│   │   ├── posts/
│   │   │   ├── PostComposer.vue
│   │   │   ├── PostCard.vue
│   │   │   ├── PostCalendar.vue
│   │   │   └── PostApprovalBadge.vue
│   │   ├── inbox/
│   │   │   ├── InboxList.vue
│   │   │   ├── InboxItem.vue
│   │   │   └── ReplyComposer.vue
│   │   └── analytics/
│   │       ├── MetricsCard.vue
│   │       └── EngagementChart.vue
│   ├── composables/             # Reusable composition functions
│   │   ├── useAuth.ts
│   │   ├── useWorkspace.ts
│   │   ├── usePagination.ts
│   │   └── useToast.ts
│   ├── layouts/
│   │   ├── AuthLayout.vue
│   │   ├── DashboardLayout.vue
│   │   └── OnboardingLayout.vue
│   ├── pages/                   # Route-based pages
│   │   ├── auth/
│   │   │   ├── LoginPage.vue
│   │   │   └── RegisterPage.vue
│   │   ├── dashboard/
│   │   │   └── DashboardPage.vue
│   │   ├── posts/
│   │   │   ├── PostsListPage.vue
│   │   │   ├── PostCreatePage.vue
│   │   │   └── CalendarPage.vue
│   │   ├── inbox/
│   │   │   └── InboxPage.vue
│   │   └── settings/
│   │       ├── WorkspaceSettingsPage.vue
│   │       └── ProfileSettingsPage.vue
│   ├── router/
│   │   ├── index.ts
│   │   ├── guards.ts
│   │   └── routes.ts
│   ├── services/                # API service layer
│   │   ├── api.ts               # Axios instance
│   │   ├── auth.service.ts
│   │   ├── posts.service.ts
│   │   ├── inbox.service.ts
│   │   └── analytics.service.ts
│   ├── stores/                  # Pinia stores
│   │   ├── auth.store.ts
│   │   ├── workspace.store.ts
│   │   ├── posts.store.ts
│   │   └── notifications.store.ts
│   ├── types/                   # TypeScript types
│   │   ├── models/
│   │   │   ├── user.ts
│   │   │   ├── workspace.ts
│   │   │   ├── post.ts
│   │   │   └── inbox.ts
│   │   ├── api/
│   │   │   ├── requests.ts
│   │   │   └── responses.ts
│   │   └── index.ts
│   ├── utils/
│   │   ├── date.ts
│   │   ├── validation.ts
│   │   └── formatters.ts
│   ├── App.vue
│   └── main.ts
├── tests/
│   ├── unit/
│   │   ├── components/
│   │   └── composables/
│   └── e2e/
│       ├── auth.spec.ts
│       ├── posts.spec.ts
│       └── inbox.spec.ts
├── .eslintrc.cjs
├── .prettierrc
├── tsconfig.json
├── vite.config.ts
└── package.json
```

### 6.2 Component Standards

**Single File Component Template:**

```vue
<script setup lang="ts">
/**
 * PostCard Component
 *
 * Displays a post preview with status badge and quick actions.
 *
 * @example
 * <PostCard :post="post" @edit="handleEdit" @delete="handleDelete" />
 */
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { Post } from '@/types/models/post'
import { formatRelativeDate } from '@/utils/date'
import AppButton from '@/components/common/AppButton.vue'
import PostStatusBadge from './PostStatusBadge.vue'

// Props
interface Props {
  post: Post
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true,
})

// Emits
const emit = defineEmits<{
  edit: [post: Post]
  delete: [post: Post]
}>()

// Composables
const router = useRouter()

// Computed
const truncatedContent = computed(() => {
  if (props.post.content_text.length > 150) {
    return props.post.content_text.substring(0, 150) + '...'
  }
  return props.post.content_text
})

const platformIcons = computed(() => {
  return props.post.targets.map(t => t.social_account.platform)
})

// Methods
const handleViewClick = () => {
  router.push({ name: 'post-detail', params: { id: props.post.id } })
}

const handleEditClick = () => {
  emit('edit', props.post)
}

const handleDeleteClick = () => {
  emit('delete', props.post)
}
</script>

<template>
  <article class="post-card" data-testid="post-card">
    <header class="post-card__header">
      <PostStatusBadge :status="post.status" />
      <span class="post-card__date">
        {{ formatRelativeDate(post.scheduled_at || post.created_at) }}
      </span>
    </header>

    <div class="post-card__content">
      <p>{{ truncatedContent }}</p>
    </div>

    <footer class="post-card__footer">
      <div class="post-card__platforms">
        <span
          v-for="platform in platformIcons"
          :key="platform"
          :class="`platform-icon platform-icon--${platform}`"
          :title="platform"
        />
      </div>

      <div v-if="showActions" class="post-card__actions">
        <AppButton
          variant="ghost"
          size="sm"
          @click="handleViewClick"
        >
          View
        </AppButton>
        <AppButton
          v-if="post.status === 'DRAFT'"
          variant="ghost"
          size="sm"
          @click="handleEditClick"
        >
          Edit
        </AppButton>
        <AppButton
          v-if="post.status === 'DRAFT'"
          variant="ghost"
          size="sm"
          color="danger"
          @click="handleDeleteClick"
        >
          Delete
        </AppButton>
      </div>
    </footer>
  </article>
</template>

<style scoped lang="scss">
.post-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-4);
  transition: box-shadow 0.2s ease;

  &:hover {
    box-shadow: var(--shadow-sm);
  }

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-3);
  }

  &__date {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
  }

  &__content {
    margin-bottom: var(--space-4);
    line-height: 1.5;
  }

  &__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__platforms {
    display: flex;
    gap: var(--space-2);
  }

  &__actions {
    display: flex;
    gap: var(--space-2);
  }
}
</style>
```

### 6.3 Composables Pattern

```typescript
// src/composables/useAuth.ts
import { computed, readonly } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import type { LoginCredentials, RegisterData } from '@/types/api/requests'

export function useAuth() {
  const router = useRouter()
  const authStore = useAuthStore()

  // Computed
  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const currentUser = computed(() => authStore.user)
  const isLoading = computed(() => authStore.isLoading)

  // Methods
  async function login(credentials: LoginCredentials): Promise<void> {
    await authStore.login(credentials)
    router.push({ name: 'dashboard' })
  }

  async function register(data: RegisterData): Promise<void> {
    await authStore.register(data)
    router.push({ name: 'onboarding' })
  }

  async function logout(): Promise<void> {
    await authStore.logout()
    router.push({ name: 'login' })
  }

  async function refreshToken(): Promise<void> {
    await authStore.refreshToken()
  }

  return {
    // State (readonly)
    isAuthenticated: readonly(isAuthenticated),
    currentUser: readonly(currentUser),
    isLoading: readonly(isLoading),

    // Methods
    login,
    register,
    logout,
    refreshToken,
  }
}
```

### 6.4 API Service Layer

```typescript
// src/services/api.ts
import axios, { type AxiosInstance, type AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth.store'
import { useToast } from '@/composables/useToast'

const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor - add auth token
api.interceptors.request.use((config) => {
  const authStore = useAuthStore()

  if (authStore.accessToken) {
    config.headers.Authorization = `Bearer ${authStore.accessToken}`
  }

  // Add workspace context if available
  if (authStore.currentWorkspaceId) {
    config.headers['X-Workspace-ID'] = authStore.currentWorkspaceId
  }

  return config
})

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const authStore = useAuthStore()
    const toast = useToast()
    const originalRequest = error.config

    // Handle 401 - try token refresh
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

      try {
        await authStore.refreshToken()
        originalRequest.headers.Authorization = `Bearer ${authStore.accessToken}`
        return api(originalRequest)
      } catch (refreshError) {
        authStore.logout()
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    // Handle other errors
    const message = error.response?.data?.message || 'An unexpected error occurred'

    if (error.response?.status === 403) {
      toast.error('You do not have permission to perform this action')
    } else if (error.response?.status === 404) {
      toast.error('The requested resource was not found')
    } else if (error.response?.status >= 500) {
      toast.error('Server error. Please try again later.')
    }

    return Promise.reject(error)
  }
)

export default api

// src/services/posts.service.ts
import api from './api'
import type { Post } from '@/types/models/post'
import type { CreatePostRequest, UpdatePostRequest } from '@/types/api/requests'
import type { PaginatedResponse } from '@/types/api/responses'

export const postsService = {
  async list(workspaceId: string, params?: Record<string, any>): Promise<PaginatedResponse<Post>> {
    const response = await api.get(`/v1/workspaces/${workspaceId}/posts`, { params })
    return response.data
  },

  async get(workspaceId: string, postId: string): Promise<Post> {
    const response = await api.get(`/v1/workspaces/${workspaceId}/posts/${postId}`)
    return response.data.data
  },

  async create(workspaceId: string, data: CreatePostRequest): Promise<Post> {
    const response = await api.post(`/v1/workspaces/${workspaceId}/posts`, data)
    return response.data.data
  },

  async update(workspaceId: string, postId: string, data: UpdatePostRequest): Promise<Post> {
    const response = await api.patch(`/v1/workspaces/${workspaceId}/posts/${postId}`, data)
    return response.data.data
  },

  async delete(workspaceId: string, postId: string): Promise<void> {
    await api.delete(`/v1/workspaces/${workspaceId}/posts/${postId}`)
  },

  async submitForApproval(workspaceId: string, postId: string): Promise<Post> {
    const response = await api.post(`/v1/workspaces/${workspaceId}/posts/${postId}/submit`)
    return response.data.data
  },

  async approve(workspaceId: string, postId: string, comment?: string): Promise<Post> {
    const response = await api.post(`/v1/workspaces/${workspaceId}/posts/${postId}/approve`, { comment })
    return response.data.data
  },

  async reject(workspaceId: string, postId: string, comment: string): Promise<Post> {
    const response = await api.post(`/v1/workspaces/${workspaceId}/posts/${postId}/reject`, { comment })
    return response.data.data
  },
}
```

### 6.5 Pinia Store Pattern

```typescript
// src/stores/posts.store.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { postsService } from '@/services/posts.service'
import type { Post } from '@/types/models/post'
import type { CreatePostRequest, UpdatePostRequest } from '@/types/api/requests'

export const usePostsStore = defineStore('posts', () => {
  // State
  const posts = ref<Post[]>([])
  const currentPost = ref<Post | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    total: 0,
  })

  // Getters
  const draftPosts = computed(() =>
    posts.value.filter(p => p.status === 'DRAFT')
  )

  const scheduledPosts = computed(() =>
    posts.value.filter(p => p.status === 'SCHEDULED')
  )

  const pendingApprovalPosts = computed(() =>
    posts.value.filter(p => p.status === 'PENDING_APPROVAL')
  )

  // Actions
  async function fetchPosts(workspaceId: string, params?: Record<string, any>) {
    isLoading.value = true
    error.value = null

    try {
      const response = await postsService.list(workspaceId, params)
      posts.value = response.data
      pagination.value = {
        currentPage: response.meta.current_page,
        lastPage: response.meta.last_page,
        perPage: response.meta.per_page,
        total: response.meta.total,
      }
    } catch (e: any) {
      error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPost(workspaceId: string, postId: string) {
    isLoading.value = true
    error.value = null

    try {
      currentPost.value = await postsService.get(workspaceId, postId)
    } catch (e: any) {
      error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function createPost(workspaceId: string, data: CreatePostRequest) {
    isLoading.value = true
    error.value = null

    try {
      const newPost = await postsService.create(workspaceId, data)
      posts.value.unshift(newPost)
      return newPost
    } catch (e: any) {
      error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function updatePost(workspaceId: string, postId: string, data: UpdatePostRequest) {
    isLoading.value = true
    error.value = null

    try {
      const updatedPost = await postsService.update(workspaceId, postId, data)
      const index = posts.value.findIndex(p => p.id === postId)
      if (index !== -1) {
        posts.value[index] = updatedPost
      }
      if (currentPost.value?.id === postId) {
        currentPost.value = updatedPost
      }
      return updatedPost
    } catch (e: any) {
      error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function deletePost(workspaceId: string, postId: string) {
    isLoading.value = true
    error.value = null

    try {
      await postsService.delete(workspaceId, postId)
      posts.value = posts.value.filter(p => p.id !== postId)
      if (currentPost.value?.id === postId) {
        currentPost.value = null
      }
    } catch (e: any) {
      error.value = e.message
      throw e
    } finally {
      isLoading.value = false
    }
  }

  function clearPosts() {
    posts.value = []
    currentPost.value = null
    error.value = null
  }

  return {
    // State
    posts,
    currentPost,
    isLoading,
    error,
    pagination,

    // Getters
    draftPosts,
    scheduledPosts,
    pendingApprovalPosts,

    // Actions
    fetchPosts,
    fetchPost,
    createPost,
    updatePost,
    deletePost,
    clearPosts,
  }
})
```

---

*Document continues in next section...*
