<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->user, WorkspaceRole::ADMIN);
});

describe('GET /api/v1/workspaces/{workspace}/analytics/dashboard', function () {
    it('returns dashboard metrics for workspace', function () {
        // Create analytics aggregates for the workspace
        AnalyticsAggregate::factory()->count(5)->workspaceTotals()->daily()->create([
            'workspace_id' => $this->workspace->id,
            'date' => Carbon::now()->subDays(rand(1, 30)),
            'impressions' => rand(1000, 5000),
            'reach' => rand(500, 2000),
            'engagements' => rand(100, 500),
            'likes' => rand(50, 200),
            'comments' => rand(10, 50),
            'shares' => rand(5, 30),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'impressions',
                    'reach',
                    'engagements',
                    'likes',
                    'comments',
                    'shares',
                    'posts_published',
                    'followers_total',
                    'followers_gained',
                    'engagement_rate',
                    'impressions_change',
                    'reach_change',
                    'engagement_change',
                    'followers_change',
                    'period',
                    'start_date',
                    'end_date',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard metrics retrieved successfully',
            ]);

        // Verify metrics are numeric
        expect($response->json('data.impressions'))->toBeInt();
        expect($response->json('data.reach'))->toBeInt();
        expect($response->json('data.engagements'))->toBeInt();
        expect($response->json('data.engagement_rate'))->toBeFloat();
    });

    it('accepts period parameter', function () {
        AnalyticsAggregate::factory()->workspaceTotals()->daily()->create([
            'workspace_id' => $this->workspace->id,
            'date' => Carbon::now()->subDays(5),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard?period=7d");

        $response->assertOk()
            ->assertJsonPath('data.period', '7d');
    });

    it('defaults to 30d period when not specified', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard");

        $response->assertOk()
            ->assertJsonPath('data.period', '30d');
    });

    it('denies access for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard");

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard");

        $response->assertUnauthorized();
    });

    it('returns zero metrics when no data exists', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard");

        $response->assertOk();
        expect($response->json('data.impressions'))->toBe(0);
        expect($response->json('data.reach'))->toBe(0);
        expect($response->json('data.engagements'))->toBe(0);
    });

    it('calculates engagement rate correctly', function () {
        AnalyticsAggregate::factory()->workspaceTotals()->daily()->create([
            'workspace_id' => $this->workspace->id,
            'date' => Carbon::now()->subDays(1),
            'reach' => 1000,
            'engagements' => 100,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/dashboard");

        $response->assertOk();
        // Engagement rate should be (100 / 1000) * 100 = 10.0
        // The API returns it as a number, could be int or float
        expect($response->json('data.engagement_rate'))->toBeNumeric();
        expect((float) $response->json('data.engagement_rate'))->toBe(10.0);
    });
});

describe('GET /api/v1/workspaces/{workspace}/analytics/metrics', function () {
    it('returns detailed metrics for workspace', function () {
        AnalyticsAggregate::factory()->count(3)->workspaceTotals()->daily()->create([
            'workspace_id' => $this->workspace->id,
            'date' => Carbon::now()->subDays(rand(1, 30)),
            'impressions' => rand(1000, 5000),
            'reach' => rand(500, 2000),
            'engagements' => rand(100, 500),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'period' => [
                        'start',
                        'end',
                        'days',
                    ],
                    'metrics' => [
                        'impressions',
                        'reach',
                        'engagements',
                        'likes',
                        'comments',
                        'shares',
                        'engagement_rate',
                    ],
                    'comparison',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Metrics retrieved successfully',
            ]);
    });

    it('accepts period parameter', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics?period=90d");

        $response->assertOk();
        expect($response->json('data.period.days'))->toBeGreaterThanOrEqual(90);
    });

    it('includes comparison by default', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'comparison',
                ],
            ]);
    });

    it('excludes comparison when include_comparison is false', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics?include_comparison=false");

        $response->assertOk();
        expect($response->json('data'))->not->toHaveKey('comparison');
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics");

        $response->assertUnauthorized();
    });

    it('returns metrics from database aggregates', function () {
        // Create specific analytics data
        AnalyticsAggregate::factory()->workspaceTotals()->daily()->create([
            'workspace_id' => $this->workspace->id,
            'date' => Carbon::now()->subDays(1),
            'impressions' => 5000,
            'reach' => 2000,
            'engagements' => 300,
            'likes' => 150,
            'comments' => 50,
            'shares' => 25,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics");

        $response->assertOk();
        
        // Verify the metrics come from database
        $metrics = $response->json('data.metrics');
        expect($metrics['impressions'])->toBeGreaterThan(0);
        expect($metrics['reach'])->toBeGreaterThan(0);
        expect($metrics['engagements'])->toBeGreaterThan(0);
    });

    it('supports multiple period formats', function () {
        Sanctum::actingAs($this->user);

        $periods = ['7d', '30d', '90d', '6m', '1y'];

        foreach ($periods as $period) {
            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/analytics/metrics?period={$period}");
            $response->assertOk();
        }
    });
});
