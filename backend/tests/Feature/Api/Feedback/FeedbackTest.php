<?php

declare(strict_types=1);

use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\VoteType;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackVote;
use App\Models\Tenant\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

describe('GET /api/v1/feedback', function () {
    it('returns paginated public feedback without authentication', function () {
        Feedback::factory()
            ->newStatus()
            ->count(3)
            ->create();

        $response = $this->getJson('/api/v1/feedback');

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
                        'type_label',
                        'category',
                        'status',
                        'status_label',
                        'vote_count',
                        'comment_count',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters feedback by type', function () {
        Feedback::factory()
            ->ofType(FeedbackType::FEATURE_REQUEST)
            ->newStatus()
            ->count(2)
            ->create();

        Feedback::factory()
            ->ofType(FeedbackType::BUG_REPORT)
            ->newStatus()
            ->create();

        $response = $this->getJson('/api/v1/feedback?type=feature_request');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters feedback by category', function () {
        Feedback::factory()
            ->inCategory(FeedbackCategory::PUBLISHING)
            ->newStatus()
            ->count(2)
            ->create();

        Feedback::factory()
            ->inCategory(FeedbackCategory::ANALYTICS)
            ->newStatus()
            ->create();

        $response = $this->getJson('/api/v1/feedback?category=publishing');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters feedback by status', function () {
        Feedback::factory()
            ->newStatus()
            ->count(2)
            ->create();

        Feedback::factory()
            ->planned()
            ->create();

        $response = $this->getJson('/api/v1/feedback?status=new');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('excludes declined and archived feedback from public list', function () {
        Feedback::factory()->newStatus()->count(2)->create();
        Feedback::factory()->declined()->create();
        Feedback::factory()->state(['status' => FeedbackStatus::ARCHIVED])->create();

        $response = $this->getJson('/api/v1/feedback');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('includes user vote status when authenticated', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $response->assertOk()
            ->assertJsonPath('data.0.user_vote', 1);
    });
});

describe('GET /api/v1/feedback/popular', function () {
    it('returns popular feedback sorted by votes', function () {
        Feedback::factory()->newStatus()->state(['vote_count' => 50])->create();
        Feedback::factory()->newStatus()->state(['vote_count' => 100])->create();
        Feedback::factory()->newStatus()->state(['vote_count' => 25])->create();

        $response = $this->getJson('/api/v1/feedback/popular');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        expect($data[0]['vote_count'])->toBeGreaterThanOrEqual($data[1]['vote_count']);
    });

    it('limits results', function () {
        Feedback::factory()->newStatus()->count(15)->create();

        $response = $this->getJson('/api/v1/feedback/popular?limit=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    });
});

describe('POST /api/v1/feedback', function () {
    it('creates feedback without authentication (anonymous)', function () {
        $response = $this->postJson('/api/v1/feedback', [
            'title' => 'Add dark mode',
            'description' => 'Please add a dark mode option for the application.',
            'type' => FeedbackType::FEATURE_REQUEST->value,
            'category' => FeedbackCategory::GENERAL->value,
            'email' => 'anonymous@example.com',
            'name' => 'Anonymous User',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'category',
                    'status',
                    'is_anonymous',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Add dark mode',
                    'status' => FeedbackStatus::NEW->value,
                    'is_anonymous' => true,
                ],
            ]);

        expect(Feedback::count())->toBe(1);
    });

    it('creates feedback with authentication', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', [
                'title' => 'Improve scheduling',
                'description' => 'The scheduling feature could be improved.',
                'type' => FeedbackType::IMPROVEMENT->value,
                'category' => FeedbackCategory::SCHEDULING->value,
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'title' => 'Improve scheduling',
                    'is_anonymous' => false,
                ],
            ]);

        $feedback = Feedback::first();
        expect($feedback->user_id)->toBe($this->user->id);
        // Authenticated users auto-upvote their feedback
        expect($feedback->vote_count)->toBe(1);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/feedback', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description']);
    });

    it('validates title max length', function () {
        $response = $this->postJson('/api/v1/feedback', [
            'title' => str_repeat('a', 201),
            'description' => 'Description',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });
});

describe('GET /api/v1/feedback/{feedback}', function () {
    it('returns feedback details', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->getJson("/api/v1/feedback/{$feedback->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'type_label',
                    'category',
                    'category_label',
                    'status',
                    'status_label',
                    'vote_count',
                    'comment_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    });

    it('returns 404 for non-existent feedback', function () {
        $response = $this->getJson('/api/v1/feedback/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('POST /api/v1/feedback/{feedback}/vote', function () {
    it('requires authentication', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->postJson("/api/v1/feedback/{$feedback->id}/vote");

        $response->assertUnauthorized();
    });

    it('upvotes feedback', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 0])->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/feedback/{$feedback->id}/vote", [
                'vote_type' => VoteType::UPVOTE->value,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'vote_count' => 1,
                    'user_vote' => 1,
                ],
            ]);
    });

    it('downvotes feedback', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 0])->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/feedback/{$feedback->id}/vote", [
                'vote_type' => VoteType::DOWNVOTE->value,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'vote_count' => -1,
                    'user_vote' => -1,
                ],
            ]);
    });

    it('prevents duplicate votes of same type', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 1])->create();

        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/feedback/{$feedback->id}/vote", [
                'vote_type' => VoteType::UPVOTE->value,
            ]);

        $response->assertUnprocessable();
    });

    it('allows changing vote type', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 1])->create();

        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/feedback/{$feedback->id}/vote", [
                'vote_type' => VoteType::DOWNVOTE->value,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'vote_count' => -1,
                    'user_vote' => -1,
                ],
            ]);
    });
});

describe('DELETE /api/v1/feedback/{feedback}/vote', function () {
    it('requires authentication', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->deleteJson("/api/v1/feedback/{$feedback->id}/vote");

        $response->assertUnauthorized();
    });

    it('removes vote', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 1])->create();

        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/feedback/{$feedback->id}/vote");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'vote_count' => 0,
                    'user_vote' => null,
                ],
            ]);

        expect(FeedbackVote::count())->toBe(0);
    });
});

describe('POST /api/v1/feedback/{feedback}/comments', function () {
    it('adds a comment without authentication', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->postJson("/api/v1/feedback/{$feedback->id}/comments", [
            'content' => 'I agree with this feedback!',
            'commenter_name' => 'Anonymous Commenter',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'feedback_id',
                    'content',
                    'author_name',
                    'is_official_response',
                    'created_at',
                ],
            ]);
    });

    it('adds a comment with authentication', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/feedback/{$feedback->id}/comments", [
                'content' => 'Great suggestion!',
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'author_name' => $this->user->name,
                ],
            ]);
    });

    it('validates required content', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $response = $this->postJson("/api/v1/feedback/{$feedback->id}/comments", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });
});

describe('GET /api/v1/feedback/{feedback}/comments', function () {
    it('returns feedback comments', function () {
        $feedback = Feedback::factory()
            ->newStatus()
            ->has(\App\Models\Feedback\FeedbackComment::factory()->count(3), 'comments')
            ->create();

        $response = $this->getJson("/api/v1/feedback/{$feedback->id}/comments");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });
});
