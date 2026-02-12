<?php

declare(strict_types=1);

/**
 * FeedbackVote Model Unit Tests
 *
 * Tests for the FeedbackVote model which represents votes on feedback.
 *
 * @see \App\Models\Feedback\FeedbackVote
 */

use App\Enums\Feedback\VoteType;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackVote;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create vote with factory', function (): void {
    $vote = FeedbackVote::factory()->create();

    expect($vote)->toBeInstanceOf(FeedbackVote::class)
        ->and($vote->id)->not->toBeNull()
        ->and($vote->feedback_id)->not->toBeNull();
});

test('has correct table name', function (): void {
    $vote = new FeedbackVote();

    expect($vote->getTable())->toBe('feedback_votes');
});

test('casts vote_type correctly', function (): void {
    $vote = FeedbackVote::factory()->upvote()->create();

    expect($vote->vote_type)->toBeInstanceOf(VoteType::class);
});

test('feedback relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    $vote = FeedbackVote::factory()->forFeedback($feedback)->create();

    expect($vote->feedback)->toBeInstanceOf(Feedback::class)
        ->and($vote->feedback->id)->toBe($feedback->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $vote = FeedbackVote::factory()->byUser($user)->create();

    expect($vote->user)->toBeInstanceOf(User::class)
        ->and($vote->user->id)->toBe($user->id);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $vote = FeedbackVote::factory()->create(['tenant_id' => $tenant->id]);

    expect($vote->tenant)->toBeInstanceOf(Tenant::class)
        ->and($vote->tenant->id)->toBe($tenant->id);
});

test('forFeedback scope filters by feedback', function (): void {
    $feedback = Feedback::factory()->create();
    FeedbackVote::factory()->forFeedback($feedback)->count(2)->create();
    FeedbackVote::factory()->create();

    expect(FeedbackVote::forFeedback($feedback->id)->count())->toBe(2);
});

test('byUser scope filters by user', function (): void {
    $user = User::factory()->create();
    FeedbackVote::factory()->byUser($user)->count(2)->create();
    FeedbackVote::factory()->create();

    expect(FeedbackVote::byUser($user->id)->count())->toBe(2);
});

test('upvotes scope filters upvotes', function (): void {
    FeedbackVote::factory()->upvote()->count(2)->create();
    FeedbackVote::factory()->downvote()->create();

    expect(FeedbackVote::upvotes()->count())->toBe(2);
});

test('downvotes scope filters downvotes', function (): void {
    FeedbackVote::factory()->downvote()->count(2)->create();
    FeedbackVote::factory()->upvote()->create();

    expect(FeedbackVote::downvotes()->count())->toBe(2);
});

test('isUpvote returns correct value', function (): void {
    $upvote = FeedbackVote::factory()->upvote()->create();
    $downvote = FeedbackVote::factory()->downvote()->create();

    expect($upvote->isUpvote())->toBeTrue()
        ->and($downvote->isUpvote())->toBeFalse();
});

test('isDownvote returns correct value', function (): void {
    $downvote = FeedbackVote::factory()->downvote()->create();
    $upvote = FeedbackVote::factory()->upvote()->create();

    expect($downvote->isDownvote())->toBeTrue()
        ->and($upvote->isDownvote())->toBeFalse();
});
