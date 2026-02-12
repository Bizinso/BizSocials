# BizSocials — SDLC Plan (Part 2)

*Continuation of 08_sdlc_comprehensive_plan.md*

---

## 7. Database Standards

### 7.1 Migration Standards

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Create posts table
     *
     * Related entities: PostMedia, PostTarget, ApprovalDecision
     * Indexes: workspace_id, status, scheduled_at, created_by_user_id
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Tenant reference (REQUIRED for all workspace-scoped tables)
            $table->uuid('workspace_id');
            $table->foreign('workspace_id')
                  ->references('id')
                  ->on('workspaces')
                  ->onDelete('cascade');

            // Content
            $table->text('content_text');

            // Status (enum stored as string for flexibility)
            $table->string('status', 20)->default('DRAFT');

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Ownership
            $table->uuid('created_by_user_id');
            $table->foreign('created_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'scheduled_at']);
            $table->index(['workspace_id', 'created_by_user_id']);
            $table->index(['status', 'scheduled_at']); // For scheduler job
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

### 7.2 Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Tables | snake_case, plural | `social_accounts`, `post_targets` |
| Columns | snake_case | `workspace_id`, `created_at` |
| Primary Key | `id` (UUID) | `id` |
| Foreign Key | `{table_singular}_id` | `user_id`, `workspace_id` |
| Boolean | `is_` or `has_` prefix | `is_active`, `has_verified` |
| Timestamps | Laravel conventions | `created_at`, `updated_at` |
| Soft Delete | `deleted_at` | `deleted_at` |
| Indexes | `{table}_{columns}_index` | `posts_workspace_id_status_index` |
| Unique | `{table}_{columns}_unique` | `users_email_unique` |

### 7.3 Index Strategy

```
┌─────────────────────────────────────────────────────────────────┐
│                      INDEXING GUIDELINES                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ALWAYS INDEX:                                                  │
│  ✓ workspace_id (tenant column) - FIRST in composite indexes   │
│  ✓ Foreign keys                                                 │
│  ✓ Status/enum columns used in WHERE clauses                    │
│  ✓ Timestamp columns used for sorting/filtering                 │
│  ✓ Columns in unique constraints                                │
│                                                                 │
│  COMPOSITE INDEX ORDER:                                         │
│  1. workspace_id (always first for tenant queries)              │
│  2. Equality conditions (status = 'X')                          │
│  3. Range conditions (created_at > Y)                           │
│  4. Sort columns (ORDER BY)                                     │
│                                                                 │
│  EXAMPLE:                                                       │
│  Query: WHERE workspace_id = ? AND status = ? ORDER BY created  │
│  Index: (workspace_id, status, created_at)                      │
│                                                                 │
│  AVOID:                                                         │
│  ✗ Indexing low-cardinality columns alone (boolean)             │
│  ✗ Too many single-column indexes (prefer composite)            │
│  ✗ Indexing columns rarely used in WHERE/ORDER                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 7.4 Query Optimization Rules

```php
// GOOD: Eager loading to prevent N+1
$posts = Post::with(['targets.socialAccount', 'media', 'createdBy'])
    ->where('workspace_id', $workspaceId)
    ->where('status', PostStatus::SCHEDULED)
    ->orderBy('scheduled_at')
    ->paginate(20);

// BAD: N+1 query problem
$posts = Post::where('workspace_id', $workspaceId)->get();
foreach ($posts as $post) {
    echo $post->targets; // Separate query for each post!
}

// GOOD: Chunking for large datasets
Post::where('workspace_id', $workspaceId)
    ->where('status', PostStatus::PUBLISHED)
    ->chunk(100, function ($posts) {
        foreach ($posts as $post) {
            // Process
        }
    });

// GOOD: Select only needed columns
$posts = Post::select(['id', 'content_text', 'status', 'scheduled_at'])
    ->where('workspace_id', $workspaceId)
    ->get();

// GOOD: Use exists() instead of count() > 0
if (Post::where('workspace_id', $workspaceId)->where('status', 'DRAFT')->exists()) {
    // ...
}
```

### 7.5 Database Seeding Strategy

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. System data (always seeded)
        $this->call(PlansSeeder::class);

        // 2. Development/testing data (conditional)
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestUsersSeeder::class,
                TestWorkspacesSeeder::class,
                TestSocialAccountsSeeder::class,
                TestPostsSeeder::class,
                TestInboxSeeder::class,
            ]);
        }
    }
}

// Factory example
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'workspace_id' => Workspace::factory(),
            'content_text' => fake()->paragraph(),
            'status' => fake()->randomElement(PostStatus::cases()),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'created_by_user_id' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::DRAFT,
            'scheduled_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+7 days'),
        ]);
    }
}
```

---

## 8. API Development Standards

### 8.1 RESTful Endpoint Design

```
┌─────────────────────────────────────────────────────────────────┐
│                    REST API CONVENTIONS                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  URL STRUCTURE:                                                 │
│  /v1/workspaces/{workspace_id}/{resource}                       │
│  /v1/workspaces/{workspace_id}/{resource}/{id}                  │
│  /v1/workspaces/{workspace_id}/{resource}/{id}/{sub-resource}   │
│                                                                 │
│  HTTP METHODS:                                                  │
│  GET     → Read (list or single)                                │
│  POST    → Create                                               │
│  PATCH   → Partial update                                       │
│  PUT     → Full replace (rarely used)                           │
│  DELETE  → Remove                                               │
│                                                                 │
│  ACTIONS (non-CRUD):                                            │
│  POST /posts/{id}/submit    → Submit for approval               │
│  POST /posts/{id}/approve   → Approve post                      │
│  POST /posts/{id}/reject    → Reject post                       │
│                                                                 │
│  QUERY PARAMETERS:                                              │
│  ?page=1&per_page=20        → Pagination                        │
│  ?sort=created_at&order=desc → Sorting                          │
│  ?filter[status]=DRAFT      → Filtering                         │
│  ?include=targets,media     → Eager loading                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 8.2 Response Format Standards

```json
// Success Response (Single Resource)
{
  "success": true,
  "data": {
    "id": "uuid",
    "type": "post",
    "attributes": { ... }
  }
}

// Success Response (Collection)
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  },
  "links": {
    "first": "/v1/workspaces/{id}/posts?page=1",
    "last": "/v1/workspaces/{id}/posts?page=5",
    "prev": null,
    "next": "/v1/workspaces/{id}/posts?page=2"
  }
}

// Error Response
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "content_text": ["The content text field is required."],
      "targets": ["At least one target is required."]
    }
  }
}

// HTTP Status Codes
// 200 OK           - Successful GET, PATCH
// 201 Created      - Successful POST
// 204 No Content   - Successful DELETE
// 400 Bad Request  - Invalid request body
// 401 Unauthorized - Missing/invalid auth
// 403 Forbidden    - Insufficient permissions
// 404 Not Found    - Resource not found
// 422 Unprocessable - Validation errors
// 429 Too Many     - Rate limited
// 500 Server Error - Unexpected error
```

### 8.3 API Versioning Strategy

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // V1 routes
    Route::middleware(['auth:sanctum', 'workspace'])->group(function () {
        Route::apiResource('workspaces.posts', PostController::class);
        Route::apiResource('workspaces.inbox', InboxController::class);
    });
});

// Future: V2 routes with breaking changes
Route::prefix('v2')->group(function () {
    // V2 routes (when needed)
});
```

### 8.4 Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    ],
];

// config/rate-limiting.php
return [
    'api' => [
        'default' => [
            'limit' => 60,
            'decay' => 60, // per minute
        ],
        'auth' => [
            'limit' => 5,
            'decay' => 60, // 5 login attempts per minute
        ],
        'ai' => [
            'limit' => 20,
            'decay' => 60, // 20 AI requests per minute
        ],
    ],
];

// AppServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('ai', function (Request $request) {
    return Limit::perMinute(20)->by($request->user()->id);
});
```

### 8.5 OpenAPI/Swagger Documentation

```yaml
# openapi.yaml (excerpt)
openapi: 3.0.3
info:
  title: BizSocials API
  version: 1.0.0
  description: B2B Social Media Management Platform API

servers:
  - url: https://api.bizsocials.io/v1
    description: Production
  - url: https://api.staging.bizsocials.io/v1
    description: Staging

paths:
  /workspaces/{workspace_id}/posts:
    get:
      summary: List posts
      tags: [Posts]
      security:
        - bearerAuth: []
      parameters:
        - $ref: '#/components/parameters/WorkspaceId'
        - $ref: '#/components/parameters/Page'
        - $ref: '#/components/parameters/PerPage'
        - name: filter[status]
          in: query
          schema:
            type: string
            enum: [DRAFT, PENDING_APPROVAL, APPROVED, SCHEDULED, PUBLISHING, PUBLISHED, FAILED]
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/PostCollection'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '403':
          $ref: '#/components/responses/Forbidden'

    post:
      summary: Create post
      tags: [Posts]
      security:
        - bearerAuth: []
      parameters:
        - $ref: '#/components/parameters/WorkspaceId'
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreatePostRequest'
      responses:
        '201':
          description: Post created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/PostResource'
        '422':
          $ref: '#/components/responses/ValidationError'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    PostResource:
      type: object
      properties:
        id:
          type: string
          format: uuid
        workspace_id:
          type: string
          format: uuid
        content_text:
          type: string
        status:
          type: string
          enum: [DRAFT, PENDING_APPROVAL, APPROVED, SCHEDULED, PUBLISHING, PUBLISHED, FAILED]
        scheduled_at:
          type: string
          format: date-time
          nullable: true
        created_at:
          type: string
          format: date-time
```

---

## 9. Testing Strategy

### 9.1 Testing Pyramid

```
┌─────────────────────────────────────────────────────────────────┐
│                      TESTING PYRAMID                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                         ▲                                       │
│                        /│\         E2E Tests (10%)              │
│                       / │ \        - Critical user flows        │
│                      /  │  \       - Cypress/Playwright         │
│                     /   │   \                                   │
│                    ─────┼─────                                  │
│                   /     │     \    Integration Tests (30%)      │
│                  /      │      \   - API endpoint tests         │
│                 /       │       \  - Database interactions      │
│                /        │        \ - Job processing             │
│               ──────────┼──────────                             │
│              /          │          \   Unit Tests (60%)         │
│             /           │           \  - Services, Models       │
│            /            │            \ - Validators, Utilities  │
│           /             │             \- Fast, isolated         │
│          ─────────────────────────────                          │
│                                                                 │
│  COVERAGE TARGETS:                                              │
│  - Overall: 80% minimum                                         │
│  - Critical paths: 95%+ (auth, billing, publishing)             │
│  - New code: 80% minimum (enforced in CI)                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 9.2 Backend Testing (PHPUnit)

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Post;
use App\Models\Workspace;
use App\Services\Publishing\PublishingService;
use App\Repositories\Contracts\PostRepositoryInterface;
use Tests\TestCase;
use Mockery;

class PublishingServiceTest extends TestCase
{
    private PublishingService $service;
    private $mockRepository;
    private $mockSocialApiFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(PostRepositoryInterface::class);
        $this->mockSocialApiFactory = Mockery::mock(SocialApiFactory::class);

        $this->service = new PublishingService(
            $this->mockRepository,
            $this->mockSocialApiFactory
        );
    }

    /** @test */
    public function it_publishes_post_to_all_targets(): void
    {
        // Arrange
        $post = Post::factory()
            ->scheduled()
            ->has(PostTarget::factory()->count(2))
            ->make();

        $mockApi = Mockery::mock(SocialApiInterface::class);
        $mockApi->shouldReceive('publish')->twice()->andReturn('external-id-123');

        $this->mockSocialApiFactory
            ->shouldReceive('make')
            ->twice()
            ->andReturn($mockApi);

        $this->mockRepository
            ->shouldReceive('markAsPublished')
            ->once()
            ->with($post);

        // Act
        $this->service->publish($post);

        // Assert
        $this->assertTrue(true); // Mockery verifies expectations
    }

    /** @test */
    public function it_throws_exception_for_non_scheduled_post(): void
    {
        // Arrange
        $post = Post::factory()->draft()->make();

        // Assert
        $this->expectException(PublishingFailedException::class);
        $this->expectExceptionMessage('not in SCHEDULED status');

        // Act
        $this->service->publish($post);
    }
}
```

### 9.3 API Integration Tests

```php
<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Workspace;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Workspace $workspace;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->workspace->members()->attach($this->user, ['role' => 'EDITOR']);

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    /** @test */
    public function user_can_list_posts_in_their_workspace(): void
    {
        // Arrange
        Post::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->withToken($this->token)
            ->getJson("/v1/workspaces/{$this->workspace->id}/posts");

        // Assert
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'content_text', 'status', 'created_at']
                ],
                'meta' => ['current_page', 'total']
            ]);
    }

    /** @test */
    public function user_cannot_access_posts_from_other_workspace(): void
    {
        // Arrange
        $otherWorkspace = Workspace::factory()->create();
        Post::factory()->create(['workspace_id' => $otherWorkspace->id]);

        // Act
        $response = $this->withToken($this->token)
            ->getJson("/v1/workspaces/{$otherWorkspace->id}/posts");

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function user_can_create_post(): void
    {
        // Arrange
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $payload = [
            'content_text' => 'Test post content',
            'targets' => [
                ['social_account_id' => $socialAccount->id]
            ],
        ];

        // Act
        $response = $this->withToken($this->token)
            ->postJson("/v1/workspaces/{$this->workspace->id}/posts", $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.content_text', 'Test post content')
            ->assertJsonPath('data.status', 'DRAFT');

        $this->assertDatabaseHas('posts', [
            'workspace_id' => $this->workspace->id,
            'content_text' => 'Test post content',
        ]);
    }

    /** @test */
    public function editor_can_submit_post_for_approval(): void
    {
        // Arrange
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->withToken($this->token)
            ->postJson("/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.status', 'PENDING_APPROVAL');
    }

    /** @test */
    public function viewer_cannot_create_posts(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        $this->workspace->members()->attach($viewer, ['role' => 'VIEWER']);
        $viewerToken = $viewer->createToken('test')->plainTextToken;

        // Act
        $response = $this->withToken($viewerToken)
            ->postJson("/v1/workspaces/{$this->workspace->id}/posts", [
                'content_text' => 'Test',
            ]);

        // Assert
        $response->assertForbidden();
    }
}
```

### 9.4 Multi-Tenant Isolation Tests

```php
<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function posts_are_isolated_between_workspaces(): void
    {
        // Arrange: Two workspaces with their own posts
        $workspaceA = Workspace::factory()->create(['name' => 'Workspace A']);
        $workspaceB = Workspace::factory()->create(['name' => 'Workspace B']);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $workspaceA->members()->attach($userA, ['role' => 'OWNER']);
        $workspaceB->members()->attach($userB, ['role' => 'OWNER']);

        $postA = Post::factory()->create(['workspace_id' => $workspaceA->id]);
        $postB = Post::factory()->create(['workspace_id' => $workspaceB->id]);

        $tokenA = $userA->createToken('test')->plainTextToken;
        $tokenB = $userB->createToken('test')->plainTextToken;

        // Act & Assert: User A can only see Workspace A posts
        $this->withToken($tokenA)
            ->getJson("/v1/workspaces/{$workspaceA->id}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $postA->id);

        // Act & Assert: User A cannot access Workspace B
        $this->withToken($tokenA)
            ->getJson("/v1/workspaces/{$workspaceB->id}/posts")
            ->assertForbidden();

        // Act & Assert: User A cannot access Post B directly via Workspace A
        $this->withToken($tokenA)
            ->getJson("/v1/workspaces/{$workspaceA->id}/posts/{$postB->id}")
            ->assertNotFound();

        // Act & Assert: User B can only see Workspace B posts
        $this->withToken($tokenB)
            ->getJson("/v1/workspaces/{$workspaceB->id}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $postB->id);
    }

    /** @test */
    public function shared_user_sees_correct_workspace_data(): void
    {
        // Arrange: User belongs to both workspaces
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();

        $sharedUser = User::factory()->create();
        $workspaceA->members()->attach($sharedUser, ['role' => 'EDITOR']);
        $workspaceB->members()->attach($sharedUser, ['role' => 'VIEWER']);

        $postA = Post::factory()->create(['workspace_id' => $workspaceA->id]);
        $postB = Post::factory()->create(['workspace_id' => $workspaceB->id]);

        $token = $sharedUser->createToken('test')->plainTextToken;

        // Assert: See only Workspace A posts when querying A
        $this->withToken($token)
            ->getJson("/v1/workspaces/{$workspaceA->id}/posts")
            ->assertOk()
            ->assertJsonPath('data.0.id', $postA->id);

        // Assert: See only Workspace B posts when querying B
        $this->withToken($token)
            ->getJson("/v1/workspaces/{$workspaceB->id}/posts")
            ->assertOk()
            ->assertJsonPath('data.0.id', $postB->id);
    }
}
```

### 9.5 Frontend Testing (Vitest + Vue Test Utils)

```typescript
// tests/unit/components/PostCard.spec.ts
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import PostCard from '@/components/posts/PostCard.vue'
import type { Post } from '@/types/models/post'

const mockPost: Post = {
  id: 'uuid-123',
  workspace_id: 'workspace-uuid',
  content_text: 'Test post content that is long enough to test truncation behavior in the component',
  status: 'DRAFT',
  scheduled_at: null,
  created_at: '2026-02-01T10:00:00Z',
  targets: [
    { id: 't1', social_account: { platform: 'LINKEDIN' } },
    { id: 't2', social_account: { platform: 'FACEBOOK' } },
  ],
}

describe('PostCard', () => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/posts/:id', name: 'post-detail', component: {} }],
  })

  it('renders post content', () => {
    const wrapper = mount(PostCard, {
      props: { post: mockPost },
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('Test post content')
  })

  it('truncates long content', () => {
    const longPost = {
      ...mockPost,
      content_text: 'A'.repeat(200),
    }

    const wrapper = mount(PostCard, {
      props: { post: longPost },
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('...')
    expect(wrapper.text().length).toBeLessThan(200)
  })

  it('shows platform icons for all targets', () => {
    const wrapper = mount(PostCard, {
      props: { post: mockPost },
      global: { plugins: [router] },
    })

    expect(wrapper.find('.platform-icon--LINKEDIN').exists()).toBe(true)
    expect(wrapper.find('.platform-icon--FACEBOOK').exists()).toBe(true)
  })

  it('emits edit event when edit button clicked', async () => {
    const wrapper = mount(PostCard, {
      props: { post: mockPost },
      global: { plugins: [router] },
    })

    await wrapper.find('[data-testid="edit-button"]').trigger('click')

    expect(wrapper.emitted('edit')).toBeTruthy()
    expect(wrapper.emitted('edit')![0]).toEqual([mockPost])
  })

  it('hides edit/delete buttons for published posts', () => {
    const publishedPost = { ...mockPost, status: 'PUBLISHED' }

    const wrapper = mount(PostCard, {
      props: { post: publishedPost },
      global: { plugins: [router] },
    })

    expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(false)
  })
})
```

### 9.6 E2E Testing (Playwright)

```typescript
// tests/e2e/posts.spec.ts
import { test, expect } from '@playwright/test'

test.describe('Post Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login')
    await page.fill('[data-testid="email-input"]', 'editor@test.com')
    await page.fill('[data-testid="password-input"]', 'password123')
    await page.click('[data-testid="login-button"]')
    await page.waitForURL('/dashboard')
  })

  test('user can create a draft post', async ({ page }) => {
    // Navigate to post creation
    await page.click('[data-testid="create-post-button"]')
    await page.waitForURL('/posts/create')

    // Fill in post content
    await page.fill('[data-testid="post-content"]', 'My test post content')

    // Select target platform
    await page.click('[data-testid="platform-linkedin"]')

    // Save as draft
    await page.click('[data-testid="save-draft-button"]')

    // Verify redirect and success message
    await expect(page.locator('[data-testid="toast-success"]')).toBeVisible()
    await expect(page).toHaveURL(/\/posts\/[a-z0-9-]+/)
  })

  test('user can schedule a post', async ({ page }) => {
    await page.goto('/posts/create')

    await page.fill('[data-testid="post-content"]', 'Scheduled post')
    await page.click('[data-testid="platform-facebook"]')

    // Set schedule date
    await page.click('[data-testid="schedule-toggle"]')
    await page.fill('[data-testid="schedule-date"]', '2026-03-01')
    await page.fill('[data-testid="schedule-time"]', '10:00')

    await page.click('[data-testid="schedule-button"]')

    await expect(page.locator('[data-testid="post-status"]')).toHaveText('Scheduled')
  })

  test('admin can approve a pending post', async ({ page }) => {
    // Login as admin
    await page.goto('/login')
    await page.fill('[data-testid="email-input"]', 'admin@test.com')
    await page.fill('[data-testid="password-input"]', 'password123')
    await page.click('[data-testid="login-button"]')

    // Navigate to pending approvals
    await page.click('[data-testid="approvals-nav"]')

    // Approve first pending post
    await page.click('[data-testid="post-card"]:first-child [data-testid="approve-button"]')

    await expect(page.locator('[data-testid="toast-success"]')).toContainText('approved')
  })
})
```

### 9.7 Test Data Management

```typescript
// tests/e2e/fixtures/test-data.ts
export const testUsers = {
  owner: {
    email: 'owner@test.com',
    password: 'password123',
    role: 'OWNER',
  },
  admin: {
    email: 'admin@test.com',
    password: 'password123',
    role: 'ADMIN',
  },
  editor: {
    email: 'editor@test.com',
    password: 'password123',
    role: 'EDITOR',
  },
  viewer: {
    email: 'viewer@test.com',
    password: 'password123',
    role: 'VIEWER',
  },
}

// Database seeder for E2E tests
// backend/database/seeders/E2ETestSeeder.php
```

---

## 10. Documentation Standards

### 10.1 Documentation Types

```
┌─────────────────────────────────────────────────────────────────┐
│                    DOCUMENTATION MATRIX                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  TYPE              │ AUDIENCE       │ LOCATION                  │
│  ──────────────────┼────────────────┼────────────────────────── │
│  API Documentation │ Frontend devs  │ /docs/api (Swagger UI)    │
│  Code Comments     │ All developers │ Inline in source code     │
│  Architecture Docs │ Tech leads     │ /docs/architecture        │
│  README            │ New developers │ Repository root           │
│  Runbooks          │ DevOps         │ /docs/runbooks            │
│  ADRs              │ Future devs    │ /docs/adr                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 10.2 Code Documentation Standards

**PHP DocBlocks:**

```php
<?php

/**
 * Manages the publishing lifecycle of social media posts.
 *
 * This service handles:
 * - Validation of post readiness
 * - Publishing to multiple social platforms
 * - Error handling and retry logic
 * - Status updates and notifications
 *
 * @see \App\Jobs\PublishPostJob For async publishing
 * @see \App\Events\PostPublished For post-publish events
 */
final class PublishingService
{
    /**
     * Publish a post to all configured target platforms.
     *
     * The post must be in SCHEDULED status with valid targets.
     * Each target is published independently; partial failures
     * are recorded but don't stop other targets.
     *
     * @param Post $post The post to publish (must be SCHEDULED)
     *
     * @throws PublishingFailedException If post is not publishable
     * @throws \App\Exceptions\RateLimitException If API rate limited
     *
     * @return void
     *
     * @example
     * $service->publish($scheduledPost);
     */
    public function publish(Post $post): void
    {
        // Implementation
    }
}
```

**TypeScript/Vue Documentation:**

```typescript
/**
 * Composable for managing authentication state and actions.
 *
 * Provides reactive auth state and methods for login, logout,
 * and token refresh. Automatically handles token expiration.
 *
 * @example
 * ```vue
 * <script setup>
 * const { isAuthenticated, currentUser, login, logout } = useAuth()
 *
 * async function handleLogin(credentials) {
 *   await login(credentials)
 * }
 * </script>
 * ```
 *
 * @returns Auth state and methods
 */
export function useAuth() {
  // Implementation
}
```

### 10.3 Architecture Decision Records (ADRs)

```markdown
# ADR-001: Use UUID for Primary Keys

## Status
Accepted

## Context
We need to decide on a primary key strategy for our multi-tenant SaaS application.
Options considered:
1. Auto-increment integers
2. UUIDs (v4)
3. ULIDs

## Decision
We will use UUIDv4 for all primary keys.

## Rationale
- **Security**: UUIDs prevent enumeration attacks (can't guess next ID)
- **Multi-tenancy**: Safe to expose in URLs without leaking information
- **Distributed systems**: Can generate IDs without database coordination
- **Data migration**: Easier to merge data from different environments

## Consequences
### Positive
- No sequential ID exposure
- Simplified data import/export
- Better for distributed systems

### Negative
- Larger storage size (16 bytes vs 4 bytes)
- Slightly slower index performance
- Less human-readable in debugging

## Implementation
- Use `$table->uuid('id')->primary()` in migrations
- Use `HasUuid` trait on all models
- Generate UUIDs in PHP, not database
```

### 10.4 README Template

```markdown
# BizSocials Backend

B2B Social Media Management Platform - Laravel Backend API

## Quick Start

### Prerequisites
- Docker Desktop
- Node.js 20.x (for frontend)

### Setup
\`\`\`bash
# Clone repository
git clone git@github.com:bizsocials/backend.git
cd backend

# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies and setup
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed

# API is now available at http://localhost:8000
\`\`\`

### Running Tests
\`\`\`bash
# All tests
docker-compose exec app php artisan test

# With coverage
docker-compose exec app php artisan test --coverage

# Specific suite
docker-compose exec app php artisan test --testsuite=Feature
\`\`\`

## Project Structure
\`\`\`
app/
├── Http/Controllers/Api/V1/  # API Controllers
├── Models/                   # Eloquent Models
├── Services/                 # Business Logic
├── Repositories/             # Data Access
└── Jobs/                     # Background Jobs
\`\`\`

## API Documentation
- Swagger UI: http://localhost:8000/api/documentation
- OpenAPI spec: /docs/openapi.yaml

## Key Commands
\`\`\`bash
# Run migrations
php artisan migrate

# Run queue worker
php artisan queue:work

# Run scheduler
php artisan schedule:work

# Generate IDE helpers
php artisan ide-helper:generate
\`\`\`

## Contributing
See [CONTRIBUTING.md](./CONTRIBUTING.md)
```

---

*Document continues in Part 3...*
