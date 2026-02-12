<?php

declare(strict_types=1);

/**
 * Feedback Model Unit Tests
 *
 * Tests for the Feedback model which represents user feedback submissions.
 *
 * @see \App\Models\Feedback\Feedback
 */

use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\UserPriority;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackComment;
use App\Models\Feedback\FeedbackTag;
use App\Models\Feedback\FeedbackVote;
use App\Models\Feedback\RoadmapItem;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create feedback with factory', function (): void {
    $feedback = Feedback::factory()->create();

    expect($feedback)->toBeInstanceOf(Feedback::class)
        ->and($feedback->id)->not->toBeNull()
        ->and($feedback->title)->not->toBeNull()
        ->and($feedback->description)->not->toBeNull();
});

test('has correct table name', function (): void {
    $feedback = new Feedback();

    expect($feedback->getTable())->toBe('feedback');
});

test('casts attributes correctly', function (): void {
    $feedback = Feedback::factory()->create();

    expect($feedback->feedback_type)->toBeInstanceOf(FeedbackType::class)
        ->and($feedback->status)->toBeInstanceOf(FeedbackStatus::class)
        ->and($feedback->user_priority)->toBeInstanceOf(UserPriority::class)
        ->and($feedback->vote_count)->toBeInt();
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $feedback = Feedback::factory()->forTenant($tenant)->create();

    expect($feedback->tenant)->toBeInstanceOf(Tenant::class)
        ->and($feedback->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $feedback = Feedback::factory()->byUser($user)->create();

    expect($feedback->user)->toBeInstanceOf(User::class)
        ->and($feedback->user->id)->toBe($user->id);
});

test('votes relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    FeedbackVote::factory()->forFeedback($feedback)->count(3)->create();

    expect($feedback->votes)->toHaveCount(3)
        ->and($feedback->votes->first())->toBeInstanceOf(FeedbackVote::class);
});

test('comments relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    FeedbackComment::factory()->forFeedback($feedback)->count(2)->create();

    expect($feedback->comments)->toHaveCount(2)
        ->and($feedback->comments->first())->toBeInstanceOf(FeedbackComment::class);
});

test('tags relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    $tags = FeedbackTag::factory()->count(3)->create();

    $feedback->tags()->attach($tags->pluck('id'));

    expect($feedback->tags)->toHaveCount(3)
        ->and($feedback->tags->first())->toBeInstanceOf(FeedbackTag::class);
});

test('new scope filters new feedback', function (): void {
    Feedback::factory()->newStatus()->count(2)->create();
    Feedback::factory()->shipped()->create();

    expect(Feedback::new()->count())->toBe(2);
});

test('shipped scope filters shipped feedback', function (): void {
    Feedback::factory()->shipped()->count(2)->create();
    Feedback::factory()->newStatus()->create();

    expect(Feedback::shipped()->count())->toBe(2);
});

test('open scope filters open feedback', function (): void {
    Feedback::factory()->newStatus()->count(2)->create();
    Feedback::factory()->underReview()->create();
    Feedback::factory()->shipped()->create();

    expect(Feedback::open()->count())->toBe(3);
});

test('closed scope filters closed feedback', function (): void {
    Feedback::factory()->shipped()->create();
    Feedback::factory()->declined()->create();
    Feedback::factory()->newStatus()->create();

    expect(Feedback::closed()->count())->toBe(2);
});

test('byType scope filters by feedback type', function (): void {
    Feedback::factory()->ofType(FeedbackType::BUG_REPORT)->count(2)->create();
    Feedback::factory()->ofType(FeedbackType::FEATURE_REQUEST)->create();

    expect(Feedback::byType(FeedbackType::BUG_REPORT)->count())->toBe(2);
});

test('byCategory scope filters by category', function (): void {
    Feedback::factory()->inCategory(FeedbackCategory::ANALYTICS)->count(2)->create();
    Feedback::factory()->inCategory(FeedbackCategory::PUBLISHING)->create();

    expect(Feedback::byCategory(FeedbackCategory::ANALYTICS)->count())->toBe(2);
});

test('topVoted scope orders by vote count descending', function (): void {
    Feedback::factory()->create(['vote_count' => 10]);
    Feedback::factory()->create(['vote_count' => 50]);
    Feedback::factory()->create(['vote_count' => 30]);

    $topVoted = Feedback::topVoted()->get();

    expect($topVoted->first()->vote_count)->toBe(50)
        ->and($topVoted->last()->vote_count)->toBe(10);
});

test('isNew returns correct value', function (): void {
    $new = Feedback::factory()->newStatus()->create();
    $shipped = Feedback::factory()->shipped()->create();

    expect($new->isNew())->toBeTrue()
        ->and($shipped->isNew())->toBeFalse();
});

test('isOpen returns correct value', function (): void {
    $new = Feedback::factory()->newStatus()->create();
    $shipped = Feedback::factory()->shipped()->create();

    expect($new->isOpen())->toBeTrue()
        ->and($shipped->isOpen())->toBeFalse();
});

test('isClosed returns correct value', function (): void {
    $shipped = Feedback::factory()->shipped()->create();
    $new = Feedback::factory()->newStatus()->create();

    expect($shipped->isClosed())->toBeTrue()
        ->and($new->isClosed())->toBeFalse();
});

test('incrementVoteCount increases vote count', function (): void {
    $feedback = Feedback::factory()->create(['vote_count' => 5]);

    $feedback->incrementVoteCount();

    expect($feedback->fresh()->vote_count)->toBe(6);
});

test('decrementVoteCount decreases vote count', function (): void {
    $feedback = Feedback::factory()->create(['vote_count' => 5]);

    $feedback->decrementVoteCount();

    expect($feedback->fresh()->vote_count)->toBe(4);
});

test('hasVotedBy returns true when user has voted', function (): void {
    $feedback = Feedback::factory()->create();
    $user = User::factory()->create();
    FeedbackVote::factory()->forFeedback($feedback)->byUser($user)->create();

    expect($feedback->hasVotedBy($user->id))->toBeTrue();
});

test('hasVotedBy returns false when user has not voted', function (): void {
    $feedback = Feedback::factory()->create();
    $user = User::factory()->create();

    expect($feedback->hasVotedBy($user->id))->toBeFalse();
});

test('markAsReviewed updates review fields', function (): void {
    $feedback = Feedback::factory()->newStatus()->create();
    $admin = SuperAdminUser::factory()->create();

    $feedback->markAsReviewed($admin->id);

    $feedback->refresh();
    expect($feedback->reviewed_at)->not->toBeNull()
        ->and($feedback->reviewed_by)->toBe($admin->id)
        ->and($feedback->status)->toBe(FeedbackStatus::UNDER_REVIEW);
});

test('markAsDuplicate updates status and links to original', function (): void {
    $original = Feedback::factory()->create();
    $duplicate = Feedback::factory()->newStatus()->create();

    $duplicate->markAsDuplicate($original);

    $duplicate->refresh();
    expect($duplicate->status)->toBe(FeedbackStatus::DUPLICATE)
        ->and($duplicate->duplicate_of_id)->toBe($original->id)
        ->and($duplicate->status_reason)->toContain($original->title);
});

test('linkToRoadmap links feedback to roadmap item', function (): void {
    $feedback = Feedback::factory()->underReview()->create();
    $roadmapItem = RoadmapItem::factory()->create();

    $feedback->linkToRoadmap($roadmapItem);

    $feedback->refresh();
    expect($feedback->roadmap_item_id)->toBe($roadmapItem->id)
        ->and($feedback->status)->toBe(FeedbackStatus::PLANNED);
});
