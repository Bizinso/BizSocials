<?php

declare(strict_types=1);

use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Calendar API', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Add user to workspace using the proper method
        $this->workspace->addMember($this->user, WorkspaceRole::EDITOR);
        
        $this->actingAs($this->user);
    });

    describe('GET /api/v1/workspaces/{workspace}/calendar', function () {
        it('retrieves calendar posts for date range', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            // Create posts within range
            Post::factory()->count(3)->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            // Create post outside range
            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addMonths(2),
                'status' => 'scheduled',
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'workspace_id',
                            'created_by_user_id',
                            'content_text',
                            'status',
                            'scheduled_at',
                        ],
                    ],
                    'meta' => [
                        'start_date',
                        'end_date',
                        'filters',
                        'view',
                    ],
                ])
                ->assertJsonCount(3, 'data');
        });

        it('filters posts by platform', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $facebookPost = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);
            PostTarget::factory()->create([
                'post_id' => $facebookPost->id,
                'platform_code' => 'facebook',
            ]);

            $twitterPost = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'scheduled',
            ]);
            PostTarget::factory()->create([
                'post_id' => $twitterPost->id,
                'platform_code' => 'twitter',
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}&platforms[]=facebook");

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data');

            expect($response->json('data.0.id'))->toBe($facebookPost->id);
        });

        it('filters posts by status', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            Post::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'published',
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}&status[]=scheduled");

            $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
        });

        it('returns grouped view by date', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $date1 = Carbon::now()->addDays(5);
            $date2 = Carbon::now()->addDays(10);

            Post::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => $date1,
                'status' => 'scheduled',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => $date2,
                'status' => 'scheduled',
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}&view=grouped");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        $date1->format('Y-m-d'),
                        $date2->format('Y-m-d'),
                    ],
                ]);
        });

        it('returns stats view', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            Post::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'published',
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date={$startDate->toDateString()}&end_date={$endDate->toDateString()}&view=stats");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total_posts',
                        'scheduled',
                        'published',
                        'failed',
                        'by_platform',
                        'by_date',
                    ],
                ]);

            expect($response->json('data.total_posts'))->toBe(3)
                ->and($response->json('data.scheduled'))->toBe(2)
                ->and($response->json('data.published'))->toBe(1);
        });

        it('validates required parameters', function () {
            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar");

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['start_date', 'end_date']);
        });

        it('validates date format', function () {
            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date=invalid-date&end_date=invalid-date");

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['start_date', 'end_date']);
        });

        it('validates end_date is after start_date', function () {
            $startDate = Carbon::now()->toDateString();
            $endDate = Carbon::now()->subDays(5)->toDateString();
            
            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/calendar?start_date={$startDate}&end_date={$endDate}");

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['end_date']);
        });
    });

    describe('PUT /api/v1/workspaces/{workspace}/calendar/posts/{post}/reschedule', function () {
        it('reschedules a post', function () {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $newScheduledAt = Carbon::now()->addDays(10);

            $response = $this->putJson(
                "/api/v1/workspaces/{$this->workspace->id}/calendar/posts/{$post->id}/reschedule",
                [
                    'scheduled_at' => $newScheduledAt->toIso8601String(),
                ]
            );

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'scheduled_at',
                        'status',
                    ],
                    'message',
                ]);

            expect($response->json('data.id'))->toBe($post->id);

            // Verify in database
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'scheduled_at' => $newScheduledAt,
            ]);
        });

        it('reschedules post with timezone', function () {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $newScheduledAt = Carbon::now()->addDays(10);
            $timezone = 'America/New_York';

            $response = $this->putJson(
                "/api/v1/workspaces/{$this->workspace->id}/calendar/posts/{$post->id}/reschedule",
                [
                    'scheduled_at' => $newScheduledAt->toIso8601String(),
                    'timezone' => $timezone,
                ]
            );

            $response->assertStatus(200);

            expect($response->json('data.scheduled_timezone'))->toBe($timezone);
        });

        it('validates scheduled_at is required', function () {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $response = $this->putJson(
                "/api/v1/workspaces/{$this->workspace->id}/calendar/posts/{$post->id}/reschedule",
                []
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['scheduled_at']);
        });

        it('validates scheduled_at is in the future', function () {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $response = $this->putJson(
                "/api/v1/workspaces/{$this->workspace->id}/calendar/posts/{$post->id}/reschedule",
                [
                    'scheduled_at' => Carbon::now()->subDays(1)->toIso8601String(),
                ]
            );

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['scheduled_at']);
        });

        it('returns 404 for post not in workspace', function () {
            $otherWorkspace = Workspace::factory()->create();
            $post = Post::factory()->create([
                'workspace_id' => $otherWorkspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $response = $this->putJson(
                "/api/v1/workspaces/{$this->workspace->id}/calendar/posts/{$post->id}/reschedule",
                [
                    'scheduled_at' => Carbon::now()->addDays(10)->toIso8601String(),
                ]
            );

            $response->assertStatus(404);
        });
    });
});
