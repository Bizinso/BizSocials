<?php

declare(strict_types=1);

/**
 * KBArticleFeedback Model Unit Tests
 *
 * Tests for the KBArticleFeedback model which represents user feedback on articles.
 *
 * @see \App\Models\KnowledgeBase\KBArticleFeedback
 */

use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create feedback with factory', function (): void {
    $feedback = KBArticleFeedback::factory()->create();

    expect($feedback)->toBeInstanceOf(KBArticleFeedback::class)
        ->and($feedback->id)->not->toBeNull();
});

test('has correct table name', function (): void {
    $feedback = new KBArticleFeedback();

    expect($feedback->getTable())->toBe('kb_article_feedback');
});

test('casts attributes correctly', function (): void {
    $feedback = KBArticleFeedback::factory()->create([
        'is_helpful' => true,
        'feedback_category' => KBFeedbackCategory::HELPFUL,
        'status' => KBFeedbackStatus::PENDING,
    ]);

    expect($feedback->is_helpful)->toBeBool()
        ->and($feedback->feedback_category)->toBeInstanceOf(KBFeedbackCategory::class)
        ->and($feedback->status)->toBeInstanceOf(KBFeedbackStatus::class);
});

test('article relationship works', function (): void {
    $article = KBArticle::factory()->create();
    $feedback = KBArticleFeedback::factory()->forArticle($article)->create();

    expect($feedback->article)->toBeInstanceOf(KBArticle::class)
        ->and($feedback->article->id)->toBe($article->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $feedback = KBArticleFeedback::factory()->byUser($user)->create();

    expect($feedback->user)->toBeInstanceOf(User::class)
        ->and($feedback->user->id)->toBe($user->id);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $feedback = KBArticleFeedback::factory()->create(['tenant_id' => $tenant->id]);

    expect($feedback->tenant)->toBeInstanceOf(Tenant::class)
        ->and($feedback->tenant->id)->toBe($tenant->id);
});

test('reviewedBy relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $feedback = KBArticleFeedback::factory()->reviewed()->create([
        'reviewed_by' => $admin->id,
    ]);

    expect($feedback->reviewedBy)->toBeInstanceOf(SuperAdminUser::class)
        ->and($feedback->reviewedBy->id)->toBe($admin->id);
});

test('pending scope filters pending feedback', function (): void {
    KBArticleFeedback::factory()->pending()->count(2)->create();
    KBArticleFeedback::factory()->reviewed()->create();

    expect(KBArticleFeedback::pending()->count())->toBe(2);
});

test('reviewed scope filters reviewed feedback', function (): void {
    KBArticleFeedback::factory()->pending()->create();
    KBArticleFeedback::factory()->reviewed()->count(2)->create();

    expect(KBArticleFeedback::reviewed()->count())->toBe(2);
});

test('helpful scope filters helpful feedback', function (): void {
    KBArticleFeedback::factory()->helpful()->count(2)->create();
    KBArticleFeedback::factory()->notHelpful()->create();

    expect(KBArticleFeedback::helpful()->count())->toBe(2);
});

test('notHelpful scope filters not helpful feedback', function (): void {
    KBArticleFeedback::factory()->helpful()->create();
    KBArticleFeedback::factory()->notHelpful()->count(2)->create();

    expect(KBArticleFeedback::notHelpful()->count())->toBe(2);
});

test('forArticle scope filters by article', function (): void {
    $article = KBArticle::factory()->create();
    KBArticleFeedback::factory()->forArticle($article)->count(2)->create();
    KBArticleFeedback::factory()->create();

    expect(KBArticleFeedback::forArticle($article->id)->count())->toBe(2);
});

test('withCategory scope filters by category', function (): void {
    KBArticleFeedback::factory()->create(['feedback_category' => KBFeedbackCategory::OUTDATED]);
    KBArticleFeedback::factory()->create(['feedback_category' => KBFeedbackCategory::OUTDATED]);
    KBArticleFeedback::factory()->create(['feedback_category' => KBFeedbackCategory::HELPFUL]);

    expect(KBArticleFeedback::withCategory(KBFeedbackCategory::OUTDATED)->count())->toBe(2);
});

test('markAsReviewed updates status and reviewer', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $feedback = KBArticleFeedback::factory()->pending()->create();

    $feedback->markAsReviewed($admin->id, 'Reviewed and noted');

    expect($feedback->fresh()->status)->toBe(KBFeedbackStatus::REVIEWED)
        ->and($feedback->fresh()->reviewed_by)->toBe($admin->id)
        ->and($feedback->fresh()->reviewed_at)->not->toBeNull()
        ->and($feedback->fresh()->admin_notes)->toBe('Reviewed and noted');
});

test('markAsActioned updates status and reviewer', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $feedback = KBArticleFeedback::factory()->pending()->create();

    $feedback->markAsActioned($admin->id, 'Fixed the issue');

    expect($feedback->fresh()->status)->toBe(KBFeedbackStatus::ACTIONED)
        ->and($feedback->fresh()->reviewed_by)->toBe($admin->id);
});

test('dismiss updates status and reviewer', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $feedback = KBArticleFeedback::factory()->pending()->create();

    $feedback->dismiss($admin->id, 'Not applicable');

    expect($feedback->fresh()->status)->toBe(KBFeedbackStatus::DISMISSED)
        ->and($feedback->fresh()->reviewed_by)->toBe($admin->id);
});

test('isPending returns correct value', function (): void {
    $pending = KBArticleFeedback::factory()->pending()->create();
    $reviewed = KBArticleFeedback::factory()->reviewed()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($reviewed->isPending())->toBeFalse();
});

test('isPositive returns true for helpful feedback', function (): void {
    $helpful = KBArticleFeedback::factory()->helpful()->create();
    $notHelpful = KBArticleFeedback::factory()->notHelpful()->create();

    expect($helpful->isPositive())->toBeTrue()
        ->and($notHelpful->isPositive())->toBeFalse();
});
