<?php

declare(strict_types=1);

use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\KnowledgeBase\KBCategory;

beforeEach(function () {
    $this->category = KBCategory::factory()->public()->create();
    $this->article = KBArticle::factory()
        ->published()
        ->for($this->category, 'category')
        ->create([
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);
});

describe('POST /api/v1/kb/articles/{article}/feedback', function () {
    it('submits helpful feedback', function () {
        $response = $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'is_helpful' => true,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'message'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Feedback submitted successfully',
            ]);

        $this->article->refresh();
        expect($this->article->helpful_count)->toBe(1);
        expect($this->article->not_helpful_count)->toBe(0);
    });

    it('submits not helpful feedback', function () {
        $response = $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'is_helpful' => false,
        ]);

        $response->assertCreated();

        $this->article->refresh();
        expect($this->article->helpful_count)->toBe(0);
        expect($this->article->not_helpful_count)->toBe(1);
    });

    it('submits feedback with category and comment', function () {
        $response = $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'is_helpful' => false,
            'category' => KBFeedbackCategory::OUTDATED->value,
            'comment' => 'The screenshots are from an old version.',
        ]);

        $response->assertCreated();

        $feedback = KBArticleFeedback::where('article_id', $this->article->id)->first();
        expect($feedback->feedback_category)->toBe(KBFeedbackCategory::OUTDATED);
        expect($feedback->feedback_text)->toBe('The screenshots are from an old version.');
        expect($feedback->status)->toBe(KBFeedbackStatus::PENDING);
    });

    it('requires is_helpful field', function () {
        $response = $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'comment' => 'Great article!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['is_helpful']);
    });

    it('validates feedback category', function () {
        $response = $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'is_helpful' => false,
            'category' => 'invalid_category',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    });

    it('returns 404 for draft articles', function () {
        $draftArticle = KBArticle::factory()
            ->draft()
            ->for($this->category, 'category')
            ->create();

        $response = $this->postJson("/api/v1/kb/articles/{$draftArticle->id}/feedback", [
            'is_helpful' => true,
        ]);

        $response->assertNotFound();
    });

    it('validates comment length', function () {
        $response = $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'is_helpful' => true,
            'comment' => str_repeat('a', 2001),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    });

    it('stores IP address with feedback', function () {
        $this->postJson("/api/v1/kb/articles/{$this->article->id}/feedback", [
            'is_helpful' => true,
        ]);

        $feedback = KBArticleFeedback::where('article_id', $this->article->id)->first();
        expect($feedback->ip_address)->not->toBeNull();
    });
});
