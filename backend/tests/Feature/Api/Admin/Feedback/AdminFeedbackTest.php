<?php

declare(strict_types=1);

use App\Enums\Feedback\FeedbackStatus;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\RoadmapItem;
use App\Models\Platform\SuperAdminUser;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->superAdmin()->create();
    Sanctum::actingAs($this->admin, ['*'], 'sanctum');
});

describe('GET /api/v1/admin/feedback', function () {
    it('lists all feedback including declined', function () {
        Feedback::factory()->newStatus()->count(2)->create();
        Feedback::factory()->declined()->create();

        $response = $this->getJson('/api/v1/admin/feedback');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'status',
                        'vote_count',
                    ],
                ],
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters feedback by status', function () {
        Feedback::factory()->newStatus()->count(2)->create();
        Feedback::factory()->underReview()->create();

        $response = $this->getJson('/api/v1/admin/feedback?status=new');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters feedback by type', function () {
        Feedback::factory()
            ->ofType(\App\Enums\Feedback\FeedbackType::FEATURE_REQUEST)
            ->count(2)
            ->create();

        Feedback::factory()
            ->ofType(\App\Enums\Feedback\FeedbackType::BUG_REPORT)
            ->create();

        $response = $this->getJson('/api/v1/admin/feedback?type=feature_request');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('searches feedback', function () {
        Feedback::factory()->create(['title' => 'Add dark mode']);
        Feedback::factory()->create(['title' => 'Fix login issue']);

        $response = $this->getJson('/api/v1/admin/feedback?search=dark');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/admin/feedback/stats', function () {
    it('returns feedback statistics', function () {
        Feedback::factory()->newStatus()->count(3)->create();
        Feedback::factory()->underReview()->count(2)->create();
        Feedback::factory()->planned()->create();

        $response = $this->getJson('/api/v1/admin/feedback/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_feedback',
                    'new_feedback',
                    'under_review',
                    'planned',
                    'shipped',
                    'declined',
                    'by_status',
                    'by_type',
                    'by_category',
                ],
            ])
            ->assertJson([
                'data' => [
                    'total_feedback' => 6,
                    'new_feedback' => 3,
                    'under_review' => 2,
                    'planned' => 1,
                ],
            ]);
    });
});

describe('GET /api/v1/admin/feedback/{feedback}', function () {
    it('returns feedback details', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->getJson("/api/v1/admin/feedback/{$feedback->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $feedback->id,
                    'title' => $feedback->title,
                ],
            ]);
    });

    it('returns 404 for non-existent feedback', function () {
        $response = $this->getJson('/api/v1/admin/feedback/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/admin/feedback/{feedback}/status', function () {
    it('updates feedback status', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->putJson("/api/v1/admin/feedback/{$feedback->id}/status", [
            'status' => FeedbackStatus::UNDER_REVIEW->value,
            'reason' => 'Starting review',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => FeedbackStatus::UNDER_REVIEW->value,
                ],
            ]);
    });

    it('rejects invalid status transition', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->putJson("/api/v1/admin/feedback/{$feedback->id}/status", [
            'status' => FeedbackStatus::SHIPPED->value,
        ]);

        $response->assertUnprocessable();
    });

    it('allows transition from under_review to planned', function () {
        $feedback = Feedback::factory()->underReview()->create();

        $response = $this->putJson("/api/v1/admin/feedback/{$feedback->id}/status", [
            'status' => FeedbackStatus::PLANNED->value,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => FeedbackStatus::PLANNED->value,
                ],
            ]);
    });
});

describe('POST /api/v1/admin/feedback/{feedback}/link-roadmap', function () {
    it('links feedback to roadmap item', function () {
        $feedback = Feedback::factory()->underReview()->create();
        $roadmapItem = RoadmapItem::factory()->planned()->create();

        $response = $this->postJson("/api/v1/admin/feedback/{$feedback->id}/link-roadmap", [
            'roadmap_item_id' => $roadmapItem->id,
        ]);

        $response->assertOk();

        $feedback->refresh();
        expect($feedback->roadmap_item_id)->toBe($roadmapItem->id);
        expect($feedback->status)->toBe(FeedbackStatus::PLANNED);
    });

    it('validates roadmap item exists', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->postJson("/api/v1/admin/feedback/{$feedback->id}/link-roadmap", [
            'roadmap_item_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['roadmap_item_id']);
    });
});
