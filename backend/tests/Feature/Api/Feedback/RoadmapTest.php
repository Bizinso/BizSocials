<?php

declare(strict_types=1);

use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\RoadmapItem;

describe('GET /api/v1/roadmap', function () {
    it('returns public roadmap grouped by status', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->inProgress()->count(1)->create();
        RoadmapItem::factory()->shipped()->count(1)->create();

        $response = $this->getJson('/api/v1/roadmap');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'grouped',
                    'statuses',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('excludes cancelled roadmap items', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->cancelled()->create();

        $response = $this->getJson('/api/v1/roadmap');

        $response->assertOk();

        // Count all items in grouped
        $grouped = $response->json('data.grouped');
        $totalItems = 0;
        foreach ($grouped as $items) {
            $totalItems += count($items);
        }

        expect($totalItems)->toBe(2);
    });

    it('excludes private roadmap items', function () {
        RoadmapItem::factory()->planned()->create();
        RoadmapItem::factory()->planned()->private()->create();

        $response = $this->getJson('/api/v1/roadmap');

        $response->assertOk();

        $grouped = $response->json('data.grouped');
        $totalItems = 0;
        foreach ($grouped as $items) {
            $totalItems += count($items);
        }

        expect($totalItems)->toBe(1);
    });
});

describe('GET /api/v1/roadmap/{roadmapItem}', function () {
    it('returns roadmap item details', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $response = $this->getJson("/api/v1/roadmap/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'category',
                    'category_label',
                    'status',
                    'status_label',
                    'target_quarter',
                    'progress_percentage',
                    'feedback_count',
                    'vote_count',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $item->id,
                    'title' => $item->title,
                ],
            ]);
    });

    it('returns 404 for private roadmap item', function () {
        $item = RoadmapItem::factory()->planned()->private()->create();

        $response = $this->getJson("/api/v1/roadmap/{$item->id}");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent roadmap item', function () {
        $response = $this->getJson('/api/v1/roadmap/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});
