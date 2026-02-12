<?php

declare(strict_types=1);

use App\Data\Feedback\CreateRoadmapItemData;
use App\Data\Feedback\UpdateRoadmapItemData;
use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\RoadmapItem;
use App\Services\Feedback\RoadmapService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(RoadmapService::class);
});

describe('getPublicRoadmap', function () {
    it('returns public items grouped by status', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->inProgress()->create();
        RoadmapItem::factory()->shipped()->create();

        $result = $this->service->getPublicRoadmap();

        expect($result)->toHaveKey(RoadmapStatus::PLANNED->value);
        expect($result)->toHaveKey(RoadmapStatus::IN_PROGRESS->value);
        expect($result)->toHaveKey(RoadmapStatus::SHIPPED->value);
    });

    it('excludes private items', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->planned()->private()->create();

        $result = $this->service->getPublicRoadmap();

        $totalItems = 0;
        foreach ($result as $items) {
            $totalItems += $items->count();
        }

        expect($totalItems)->toBe(2);
    });

    it('excludes cancelled items', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->cancelled()->create();

        $result = $this->service->getPublicRoadmap();

        $totalItems = 0;
        foreach ($result as $items) {
            $totalItems += $items->count();
        }

        expect($totalItems)->toBe(2);
    });
});

describe('getPublicItem', function () {
    it('returns public item', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $result = $this->service->getPublicItem($item->id);

        expect($result->id)->toBe($item->id);
    });

    it('throws exception for private item', function () {
        $item = RoadmapItem::factory()->planned()->private()->create();

        expect(fn () => $this->service->getPublicItem($item->id))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('create', function () {
    it('creates roadmap item with all fields', function () {
        $data = new CreateRoadmapItemData(
            title: 'New Feature',
            description: 'Feature description',
            detailed_description: 'Detailed description',
            category: RoadmapCategory::PUBLISHING,
            status: RoadmapStatus::PLANNED,
            target_quarter: 'Q2 2026',
            is_public: true,
        );

        $item = $this->service->create($data);

        expect($item)->toBeInstanceOf(RoadmapItem::class);
        expect($item->title)->toBe('New Feature');
        expect($item->category)->toBe(RoadmapCategory::PUBLISHING);
        expect($item->status)->toBe(RoadmapStatus::PLANNED);
    });

    it('creates roadmap item with defaults', function () {
        $data = new CreateRoadmapItemData(
            title: 'Simple Feature',
        );

        $item = $this->service->create($data);

        expect($item->status)->toBe(RoadmapStatus::PLANNED);
        expect($item->is_public)->toBeTrue();
        expect($item->progress_percentage)->toBe(0);
    });
});

describe('update', function () {
    it('updates roadmap item fields', function () {
        $item = RoadmapItem::factory()->planned()->create();
        $data = new UpdateRoadmapItemData(
            title: 'Updated Title',
            description: 'Updated description',
        );

        $result = $this->service->update($item, $data);

        expect($result->title)->toBe('Updated Title');
        expect($result->description)->toBe('Updated description');
    });

    it('updates progress percentage within bounds', function () {
        $item = RoadmapItem::factory()->planned()->create();
        $data = new UpdateRoadmapItemData(
            progress_percentage: 150,
        );

        $result = $this->service->update($item, $data);

        expect($result->progress_percentage)->toBe(100);
    });
});

describe('updateStatus', function () {
    it('updates status with valid transition', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $result = $this->service->updateStatus($item, RoadmapStatus::IN_PROGRESS);

        expect($result->status)->toBe(RoadmapStatus::IN_PROGRESS);
    });

    it('sets shipped date when marking as shipped', function () {
        $item = RoadmapItem::factory()->inProgress()->create();

        $result = $this->service->updateStatus($item, RoadmapStatus::SHIPPED);

        expect($result->status)->toBe(RoadmapStatus::SHIPPED);
        expect($result->shipped_date)->not->toBeNull();
        expect($result->progress_percentage)->toBe(100);
    });

    it('rejects invalid status transition', function () {
        $item = RoadmapItem::factory()->considering()->create();

        expect(fn () => $this->service->updateStatus($item, RoadmapStatus::SHIPPED))
            ->toThrow(ValidationException::class);
    });
});

describe('delete', function () {
    it('deletes roadmap item', function () {
        $item = RoadmapItem::factory()->planned()->create();

        $this->service->delete($item);

        expect(RoadmapItem::count())->toBe(0);
    });
});

describe('listAll', function () {
    it('returns all items including private', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->planned()->private()->create();

        $result = $this->service->listAll();

        expect($result->total())->toBe(3);
    });

    it('filters by status', function () {
        RoadmapItem::factory()->planned()->count(2)->create();
        RoadmapItem::factory()->inProgress()->create();

        $result = $this->service->listAll(['status' => 'planned']);

        expect($result->total())->toBe(2);
    });

    it('filters by category', function () {
        RoadmapItem::factory()->inCategory(RoadmapCategory::PUBLISHING)->count(2)->create();
        RoadmapItem::factory()->inCategory(RoadmapCategory::ANALYTICS)->create();

        $result = $this->service->listAll(['category' => 'publishing']);

        expect($result->total())->toBe(2);
    });
});
