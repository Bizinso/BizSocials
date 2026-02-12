<?php

declare(strict_types=1);

use App\Data\Feedback\AddFeedbackCommentData;
use App\Data\Feedback\SubmitFeedbackData;
use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\VoteType;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackVote;
use App\Models\Feedback\RoadmapItem;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Feedback\FeedbackService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(FeedbackService::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('listPublic', function () {
    it('returns paginated public feedback', function () {
        Feedback::factory()->newStatus()->count(5)->create();

        $result = $this->service->listPublic();

        expect($result->total())->toBe(5);
        expect($result->perPage())->toBe(15);
    });

    it('excludes declined and archived feedback', function () {
        Feedback::factory()->newStatus()->count(2)->create();
        Feedback::factory()->declined()->create();
        Feedback::factory()->state(['status' => FeedbackStatus::ARCHIVED])->create();

        $result = $this->service->listPublic();

        expect($result->total())->toBe(2);
    });

    it('filters by type', function () {
        Feedback::factory()->ofType(FeedbackType::FEATURE_REQUEST)->count(2)->create();
        Feedback::factory()->ofType(FeedbackType::BUG_REPORT)->create();

        $result = $this->service->listPublic(['type' => 'feature_request']);

        expect($result->total())->toBe(2);
    });

    it('filters by category', function () {
        Feedback::factory()->inCategory(FeedbackCategory::PUBLISHING)->count(2)->create();
        Feedback::factory()->inCategory(FeedbackCategory::ANALYTICS)->create();

        $result = $this->service->listPublic(['category' => 'publishing']);

        expect($result->total())->toBe(2);
    });
});

describe('submit', function () {
    it('creates feedback without user (anonymous)', function () {
        $data = new SubmitFeedbackData(
            title: 'Anonymous Feedback',
            description: 'Description',
            type: FeedbackType::FEATURE_REQUEST,
            category: FeedbackCategory::GENERAL,
            email: 'anon@example.com',
            name: 'Anonymous',
        );

        $feedback = $this->service->submit($data);

        expect($feedback)->toBeInstanceOf(Feedback::class);
        expect($feedback->user_id)->toBeNull();
        expect($feedback->submitter_email)->toBe('anon@example.com');
        expect($feedback->status)->toBe(FeedbackStatus::NEW);
    });

    it('creates feedback with user and auto-upvotes', function () {
        $data = new SubmitFeedbackData(
            title: 'User Feedback',
            description: 'Description',
        );

        $feedback = $this->service->submit($data, $this->user);

        expect($feedback->user_id)->toBe($this->user->id);
        expect($feedback->vote_count)->toBe(1);

        $vote = FeedbackVote::where('feedback_id', $feedback->id)->first();
        expect($vote)->not->toBeNull();
        expect($vote->vote_type)->toBe(VoteType::UPVOTE);
    });
});

describe('vote', function () {
    it('creates upvote', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 0])->create();

        $vote = $this->service->vote($feedback, $this->user, VoteType::UPVOTE);

        expect($vote->vote_type)->toBe(VoteType::UPVOTE);
        $feedback->refresh();
        expect($feedback->vote_count)->toBe(1);
    });

    it('creates downvote', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 0])->create();

        $vote = $this->service->vote($feedback, $this->user, VoteType::DOWNVOTE);

        expect($vote->vote_type)->toBe(VoteType::DOWNVOTE);
        $feedback->refresh();
        expect($feedback->vote_count)->toBe(-1);
    });

    it('prevents duplicate votes of same type', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 1])->create();
        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        expect(fn () => $this->service->vote($feedback, $this->user, VoteType::UPVOTE))
            ->toThrow(ValidationException::class);
    });

    it('allows changing vote type', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 1])->create();
        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $vote = $this->service->vote($feedback, $this->user, VoteType::DOWNVOTE);

        expect($vote->vote_type)->toBe(VoteType::DOWNVOTE);
        $feedback->refresh();
        expect($feedback->vote_count)->toBe(-1);
    });
});

describe('removeVote', function () {
    it('removes vote and updates count', function () {
        $feedback = Feedback::factory()->newStatus()->state(['vote_count' => 1])->create();
        FeedbackVote::factory()->create([
            'feedback_id' => $feedback->id,
            'user_id' => $this->user->id,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $this->service->removeVote($feedback, $this->user);

        $feedback->refresh();
        expect($feedback->vote_count)->toBe(0);
        expect(FeedbackVote::where('feedback_id', $feedback->id)->count())->toBe(0);
    });
});

describe('addComment', function () {
    it('adds comment to feedback', function () {
        $feedback = Feedback::factory()->newStatus()->create();
        $data = new AddFeedbackCommentData(
            content: 'This is a comment',
        );

        $comment = $this->service->addComment($feedback, $data, $this->user);

        expect($comment->content)->toBe('This is a comment');
        expect($comment->user_id)->toBe($this->user->id);
        expect($comment->is_internal)->toBeFalse();
    });

    it('adds anonymous comment', function () {
        $feedback = Feedback::factory()->newStatus()->create();
        $data = new AddFeedbackCommentData(
            content: 'Anonymous comment',
            commenter_name: 'Guest',
        );

        $comment = $this->service->addComment($feedback, $data);

        expect($comment->commenter_name)->toBe('Guest');
        expect($comment->user_id)->toBeNull();
    });
});

describe('updateStatus', function () {
    it('updates status with valid transition', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        $result = $this->service->updateStatus($feedback, FeedbackStatus::UNDER_REVIEW);

        expect($result->status)->toBe(FeedbackStatus::UNDER_REVIEW);
    });

    it('rejects invalid status transition', function () {
        $feedback = Feedback::factory()->newStatus()->create();

        expect(fn () => $this->service->updateStatus($feedback, FeedbackStatus::SHIPPED))
            ->toThrow(ValidationException::class);
    });
});

describe('linkToRoadmap', function () {
    it('links feedback to roadmap item', function () {
        $feedback = Feedback::factory()->underReview()->create();
        $roadmapItem = RoadmapItem::factory()->planned()->create();

        $this->service->linkToRoadmap($feedback, $roadmapItem);

        $feedback->refresh();
        expect($feedback->roadmap_item_id)->toBe($roadmapItem->id);
        expect($feedback->status)->toBe(FeedbackStatus::PLANNED);
    });
});

describe('getStats', function () {
    it('returns feedback statistics', function () {
        Feedback::factory()->newStatus()->count(3)->create();
        Feedback::factory()->underReview()->count(2)->create();
        Feedback::factory()->planned()->create();

        $stats = $this->service->getStats();

        expect($stats->total_feedback)->toBe(6);
        expect($stats->new_feedback)->toBe(3);
        expect($stats->under_review)->toBe(2);
        expect($stats->planned)->toBe(1);
    });
});
