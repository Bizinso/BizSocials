<?php

declare(strict_types=1);

use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\RoadmapItem;
use App\Models\Platform\SuperAdminUser;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->superAdmin()->create();
    Sanctum::actingAs($this->admin, ['*'], 'sanctum');
});

describe('GET /api/v1/admin/roadmap', function () {
    it('lists all roadmap items including private', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->planned()->private()->create();

        $response = $this->getJson('/api/v1/admin/roadmap');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'category',
                        'status',
                        'progress_percentage',
                    ],
                ],
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters by status', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->inProgress()->create();

        $response = $this->getJson('/api/v1/admin/roadmap?status=planned');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by category', function () {
        RoadmapItem::factory()
            ->inCategory(RoadmapCategory::PUBLISHING)
            ->count(2)
            ->create();

        RoadmapItem::factory()
            ->inCategory(RoadmapCategory::ANALYTICS)
            ->create();

        $response = $this->getJson('/api/v1/admin/roadmap?category=publishing');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('searches roadmap items', function () {
        RoadmapItem::factory()->create(['title' => 'Add dark mode']);
        RoadmapItem::factory()->create(['title' => 'Improve scheduling']);

        $response = $this->getJson('/api/v1/admin/roadmap?search=dark');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/admin/roadmap', function () {
    it('creates a new roadmap item', function () {
        $response = $this->postJson('/api/v1/admin/roadmap', [
            'title' => 'New Feature',
            'description' => 'Description of the new feature',
            'category' => RoadmapCategory::PUBLISHING->value,
            'status' => RoadmapStatus::PLANNED->value,
            'target_quarter' => 'Q2 2026',
            'is_public' => true,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'category',
                    'status',
                    'target_quarter',
                    'progress_percentage',
                ],
            ])
            ->assertJson([
                'data' => [
                    'title' => 'New Feature',
                    'category' => RoadmapCategory::PUBLISHING->value,
                    'status' => RoadmapStatus::PLANNED->value,
                ],
            ]);

        expect(RoadmapItem::count())->toBe(1);
    });

    it('creates roadmap item with minimal data', function () {
        $response = $this->postJson('/api/v1/admin/roadmap', [
            'title' => 'Simple Feature',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'title' => 'Simple Feature',
                    'status' => RoadmapStatus::PLANNED->value,
                ],
            ]);
    });

    it('validates required title', function () {
        $response = $this->postJson('/api/v1/admin/roadmap', [
            'description' => 'No title',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    it('validates title max length', function () {
        $response = $this->postJson('/api/v1/admin/roadmap', [
            'title' => str_repeat('a', 201),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });
});

describe('GET /api/v1/admin/roadmap/{roadmapItem}', function () {
    it('returns roadmap item details', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $response = $this->getJson("/api/v1/admin/roadmap/{$item->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'title' => $item->title,
                ],
            ]);
    });

    it('returns 404 for non-existent item', function () {
        $response = $this->getJson('/api/v1/admin/roadmap/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/admin/roadmap/{roadmapItem}', function () {
    it('updates roadmap item', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $response = $this->putJson("/api/v1/admin/roadmap/{$item->id}", [
            'title' => 'Updated Title',
            'progress_percentage' => 50,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                    'progress_percentage' => 50,
                ],
            ]);
    });

    it('clamps progress percentage to valid range', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $response = $this->putJson("/api/v1/admin/roadmap/{$item->id}", [
            'progress_percentage' => 150,
        ]);

        $response->assertUnprocessable();
    });
});

describe('PUT /api/v1/admin/roadmap/{roadmapItem}/status', function () {
    it('updates roadmap item status', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $response = $this->putJson("/api/v1/admin/roadmap/{$item->id}/status", [
            'status' => RoadmapStatus::IN_PROGRESS->value,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => RoadmapStatus::IN_PROGRESS->value,
                ],
            ]);
    });

    it('sets shipped date when marking as shipped', function () {
        $item = RoadmapItem::factory()->inProgress()->create();

        $response = $this->putJson("/api/v1/admin/roadmap/{$item->id}/status", [
            'status' => RoadmapStatus::SHIPPED->value,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => RoadmapStatus::SHIPPED->value,
                    'progress_percentage' => 100,
                ],
            ]);
    });

    it('rejects invalid status transition', function () {
        $item = RoadmapItem::factory()->considering()->create();

        $response = $this->putJson("/api/v1/admin/roadmap/{$item->id}/status", [
            'status' => RoadmapStatus::SHIPPED->value,
        ]);

        $response->assertUnprocessable();
    });
});

describe('DELETE /api/v1/admin/roadmap/{roadmapItem}', function () {
    it('deletes roadmap item', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $response = $this->deleteJson("/api/v1/admin/roadmap/{$item->id}");

        $response->assertOk();
        expect(RoadmapItem::count())->toBe(0);
    });

    it('returns 404 for non-existent item', function () {
        $response = $this->deleteJson('/api/v1/admin/roadmap/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});
