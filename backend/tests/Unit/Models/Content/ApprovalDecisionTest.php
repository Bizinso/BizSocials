<?php

declare(strict_types=1);

/**
 * ApprovalDecision Model Unit Tests
 *
 * Tests for the ApprovalDecision model which represents an approval
 * or rejection decision for a post.
 *
 * @see \App\Models\Content\ApprovalDecision
 */

use App\Enums\Content\ApprovalDecisionType;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $decision = new ApprovalDecision();

    expect($decision->getTable())->toBe('approval_decisions');
});

test('uses uuid primary key', function (): void {
    $decision = ApprovalDecision::factory()->create();

    expect($decision->id)->not->toBeNull()
        ->and(strlen($decision->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $decision = new ApprovalDecision();
    $fillable = $decision->getFillable();

    expect($fillable)->toContain('post_id')
        ->and($fillable)->toContain('decided_by_user_id')
        ->and($fillable)->toContain('decision')
        ->and($fillable)->toContain('comment')
        ->and($fillable)->toContain('is_active')
        ->and($fillable)->toContain('decided_at');
});

test('decision casts to enum', function (): void {
    $decision = ApprovalDecision::factory()->approved()->create();

    expect($decision->decision)->toBeInstanceOf(ApprovalDecisionType::class)
        ->and($decision->decision)->toBe(ApprovalDecisionType::APPROVED);
});

test('is_active casts to boolean', function (): void {
    $decision = ApprovalDecision::factory()->active()->create();

    expect($decision->is_active)->toBeBool()
        ->and($decision->is_active)->toBeTrue();
});

test('decided_at casts to datetime', function (): void {
    $decision = ApprovalDecision::factory()->create();

    expect($decision->decided_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('post relationship returns belongs to', function (): void {
    $decision = new ApprovalDecision();

    expect($decision->post())->toBeInstanceOf(BelongsTo::class);
});

test('post relationship works correctly', function (): void {
    $post = Post::factory()->create();
    $decision = ApprovalDecision::factory()->forPost($post)->create();

    expect($decision->post)->toBeInstanceOf(Post::class)
        ->and($decision->post->id)->toBe($post->id);
});

test('decidedBy relationship returns belongs to', function (): void {
    $decision = new ApprovalDecision();

    expect($decision->decidedBy())->toBeInstanceOf(BelongsTo::class);
});

test('decidedBy relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $decision = ApprovalDecision::factory()->byUser($user)->create();

    expect($decision->decidedBy)->toBeInstanceOf(User::class)
        ->and($decision->decidedBy->id)->toBe($user->id);
});

test('scope active filters active decisions', function (): void {
    ApprovalDecision::factory()->active()->create();
    ApprovalDecision::factory()->active()->create();
    ApprovalDecision::factory()->inactive()->create();

    $activeDecisions = ApprovalDecision::active()->get();

    expect($activeDecisions)->toHaveCount(2)
        ->and($activeDecisions->every(fn ($d) => $d->is_active === true))->toBeTrue();
});

test('scope forPost filters by post', function (): void {
    $post1 = Post::factory()->create();
    $post2 = Post::factory()->create();

    ApprovalDecision::factory()->count(3)->forPost($post1)->create();
    ApprovalDecision::factory()->count(2)->forPost($post2)->create();

    $decisions = ApprovalDecision::forPost($post1->id)->get();

    expect($decisions)->toHaveCount(3)
        ->and($decisions->every(fn ($d) => $d->post_id === $post1->id))->toBeTrue();
});

test('isApproved returns correct value', function (): void {
    $approved = ApprovalDecision::factory()->approved()->create();
    $rejected = ApprovalDecision::factory()->rejected()->create();

    expect($approved->isApproved())->toBeTrue()
        ->and($rejected->isApproved())->toBeFalse();
});

test('isRejected returns correct value', function (): void {
    $rejected = ApprovalDecision::factory()->rejected()->create();
    $approved = ApprovalDecision::factory()->approved()->create();

    expect($rejected->isRejected())->toBeTrue()
        ->and($approved->isRejected())->toBeFalse();
});

test('deactivate sets is_active to false', function (): void {
    $decision = ApprovalDecision::factory()->active()->create();

    expect($decision->is_active)->toBeTrue();

    $decision->deactivate();

    expect($decision->is_active)->toBeFalse();
});

test('factory creates valid model', function (): void {
    $decision = ApprovalDecision::factory()->create();

    expect($decision)->toBeInstanceOf(ApprovalDecision::class)
        ->and($decision->id)->not->toBeNull()
        ->and($decision->post_id)->not->toBeNull()
        ->and($decision->decided_by_user_id)->not->toBeNull()
        ->and($decision->decision)->toBeInstanceOf(ApprovalDecisionType::class)
        ->and($decision->is_active)->toBeBool()
        ->and($decision->decided_at)->not->toBeNull();
});

test('factory approved state works correctly', function (): void {
    $decision = ApprovalDecision::factory()->approved()->create();

    expect($decision->decision)->toBe(ApprovalDecisionType::APPROVED);
});

test('factory rejected state works correctly', function (): void {
    $decision = ApprovalDecision::factory()->rejected()->create();

    expect($decision->decision)->toBe(ApprovalDecisionType::REJECTED)
        ->and($decision->comment)->not->toBeNull();
});

test('factory active state works correctly', function (): void {
    $decision = ApprovalDecision::factory()->active()->create();

    expect($decision->is_active)->toBeTrue();
});

test('factory inactive state works correctly', function (): void {
    $decision = ApprovalDecision::factory()->inactive()->create();

    expect($decision->is_active)->toBeFalse();
});

test('factory withComment state works correctly', function (): void {
    $comment = 'This is my feedback on the post.';
    $decision = ApprovalDecision::factory()->withComment($comment)->create();

    expect($decision->comment)->toBe($comment);
});

test('factory decidedAt state works correctly', function (): void {
    $decidedAt = now()->subDays(5);
    $decision = ApprovalDecision::factory()->decidedAt($decidedAt)->create();

    expect($decision->decided_at->format('Y-m-d'))->toBe($decidedAt->format('Y-m-d'));
});

test('multiple decisions can exist for same post with different active states', function (): void {
    $post = Post::factory()->create();

    // First decision (now inactive)
    $oldDecision = ApprovalDecision::factory()
        ->forPost($post)
        ->rejected()
        ->inactive()
        ->create();

    // Second decision (active)
    $newDecision = ApprovalDecision::factory()
        ->forPost($post)
        ->approved()
        ->active()
        ->create();

    $allDecisions = ApprovalDecision::forPost($post->id)->get();
    $activeDecisions = ApprovalDecision::forPost($post->id)->active()->get();

    expect($allDecisions)->toHaveCount(2)
        ->and($activeDecisions)->toHaveCount(1)
        ->and($activeDecisions->first()->id)->toBe($newDecision->id);
});
