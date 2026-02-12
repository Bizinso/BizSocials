<?php

declare(strict_types=1);

/**
 * FeedbackComment Model Unit Tests
 *
 * Tests for the FeedbackComment model which represents comments on feedback.
 *
 * @see \App\Models\Feedback\FeedbackComment
 */

use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackComment;
use App\Models\Platform\SuperAdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create comment with factory', function (): void {
    $comment = FeedbackComment::factory()->create();

    expect($comment)->toBeInstanceOf(FeedbackComment::class)
        ->and($comment->id)->not->toBeNull()
        ->and($comment->content)->not->toBeNull();
});

test('has correct table name', function (): void {
    $comment = new FeedbackComment();

    expect($comment->getTable())->toBe('feedback_comments');
});

test('casts boolean attributes correctly', function (): void {
    $comment = FeedbackComment::factory()->internal()->create();

    expect($comment->is_internal)->toBeBool()
        ->and($comment->is_official_response)->toBeBool();
});

test('feedback relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    $comment = FeedbackComment::factory()->forFeedback($feedback)->create();

    expect($comment->feedback)->toBeInstanceOf(Feedback::class)
        ->and($comment->feedback->id)->toBe($feedback->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $comment = FeedbackComment::factory()->byUser($user)->create();

    expect($comment->user)->toBeInstanceOf(User::class)
        ->and($comment->user->id)->toBe($user->id);
});

test('admin relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $comment = FeedbackComment::factory()->byAdmin($admin)->create();

    expect($comment->admin)->toBeInstanceOf(SuperAdminUser::class)
        ->and($comment->admin->id)->toBe($admin->id);
});

test('forFeedback scope filters by feedback', function (): void {
    $feedback = Feedback::factory()->create();
    FeedbackComment::factory()->forFeedback($feedback)->count(2)->create();
    FeedbackComment::factory()->create();

    expect(FeedbackComment::forFeedback($feedback->id)->count())->toBe(2);
});

test('public scope filters public comments', function (): void {
    FeedbackComment::factory()->count(2)->create(['is_internal' => false]);
    FeedbackComment::factory()->internal()->create();

    expect(FeedbackComment::public()->count())->toBe(2);
});

test('internal scope filters internal comments', function (): void {
    FeedbackComment::factory()->internal()->count(2)->create();
    FeedbackComment::factory()->create(['is_internal' => false]);

    expect(FeedbackComment::internal()->count())->toBe(2);
});

test('official scope filters official responses', function (): void {
    FeedbackComment::factory()->official()->count(2)->create();
    FeedbackComment::factory()->create(['is_official_response' => false]);

    expect(FeedbackComment::official()->count())->toBe(2);
});

test('isInternal returns correct value', function (): void {
    $internal = FeedbackComment::factory()->internal()->create();
    $public = FeedbackComment::factory()->create(['is_internal' => false]);

    expect($internal->isInternal())->toBeTrue()
        ->and($public->isInternal())->toBeFalse();
});

test('isOfficial returns correct value', function (): void {
    $official = FeedbackComment::factory()->official()->create();
    $regular = FeedbackComment::factory()->create(['is_official_response' => false]);

    expect($official->isOfficial())->toBeTrue()
        ->and($regular->isOfficial())->toBeFalse();
});

test('getAuthorName returns admin name when admin commented', function (): void {
    $admin = SuperAdminUser::factory()->create(['name' => 'Admin User']);
    $comment = FeedbackComment::factory()->byAdmin($admin)->create();

    expect($comment->getAuthorName())->toBe('Admin User');
});

test('getAuthorName returns user name when user commented', function (): void {
    $user = User::factory()->create(['name' => 'Test User']);
    $comment = FeedbackComment::factory()->byUser($user)->create();

    expect($comment->getAuthorName())->toBe('Test User');
});

test('getAuthorName returns commenter_name when no user or admin', function (): void {
    $comment = FeedbackComment::factory()->create([
        'user_id' => null,
        'admin_id' => null,
        'commenter_name' => 'Guest User',
    ]);

    expect($comment->getAuthorName())->toBe('Guest User');
});

test('getAuthorName returns Anonymous when no name available', function (): void {
    $comment = FeedbackComment::factory()->create([
        'user_id' => null,
        'admin_id' => null,
        'commenter_name' => null,
    ]);

    expect($comment->getAuthorName())->toBe('Anonymous');
});
