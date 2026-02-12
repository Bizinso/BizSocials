<?php

declare(strict_types=1);

use App\Data\KnowledgeBase\SubmitFeedbackData;
use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\Platform\SuperAdminUser;
use App\Services\KnowledgeBase\KBFeedbackService;

beforeEach(function () {
    $this->service = app(KBFeedbackService::class);
    $this->category = KBCategory::factory()->public()->create();
    $this->article = KBArticle::factory()
        ->published()
        ->for($this->category, 'category')
        ->create([
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);
    $this->admin = SuperAdminUser::factory()->active()->create();
});

describe('submitFeedback', function () {
    it('creates feedback record', function () {
        $data = new SubmitFeedbackData(is_helpful: true);

        $result = $this->service->submitFeedback($this->article, $data);

        expect($result)->toBeInstanceOf(KBArticleFeedback::class);
        expect($result->is_helpful)->toBeTrue();
        expect($result->status)->toBe(KBFeedbackStatus::PENDING);
    });

    it('increments article helpful count', function () {
        $data = new SubmitFeedbackData(is_helpful: true);

        $this->service->submitFeedback($this->article, $data);

        $this->article->refresh();
        expect($this->article->helpful_count)->toBe(1);
        expect($this->article->not_helpful_count)->toBe(0);
    });

    it('increments article not helpful count', function () {
        $data = new SubmitFeedbackData(is_helpful: false);

        $this->service->submitFeedback($this->article, $data);

        $this->article->refresh();
        expect($this->article->helpful_count)->toBe(0);
        expect($this->article->not_helpful_count)->toBe(1);
    });

    it('stores feedback category', function () {
        $data = new SubmitFeedbackData(
            is_helpful: false,
            category: KBFeedbackCategory::OUTDATED->value,
        );

        $result = $this->service->submitFeedback($this->article, $data);

        expect($result->feedback_category)->toBe(KBFeedbackCategory::OUTDATED);
    });

    it('stores comment', function () {
        $data = new SubmitFeedbackData(
            is_helpful: false,
            comment: 'This article needs updating.',
        );

        $result = $this->service->submitFeedback($this->article, $data);

        expect($result->feedback_text)->toBe('This article needs updating.');
    });

    it('stores IP address', function () {
        $data = new SubmitFeedbackData(is_helpful: true);

        $result = $this->service->submitFeedback($this->article, $data, '192.168.1.1');

        expect($result->ip_address)->toBe('192.168.1.1');
    });
});

describe('listForArticle', function () {
    it('returns feedback for specific article', function () {
        KBArticleFeedback::factory()
            ->count(3)
            ->create(['article_id' => $this->article->id]);

        $otherArticle = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        KBArticleFeedback::factory()->create(['article_id' => $otherArticle->id]);

        $result = $this->service->listForArticle($this->article);

        expect($result)->toHaveCount(3);
    });

    it('orders by created_at descending', function () {
        $older = KBArticleFeedback::factory()
            ->create([
                'article_id' => $this->article->id,
                'created_at' => now()->subDays(2),
            ]);

        $newer = KBArticleFeedback::factory()
            ->create([
                'article_id' => $this->article->id,
                'created_at' => now(),
            ]);

        $result = $this->service->listForArticle($this->article);

        expect($result->first()->id)->toBe($newer->id);
    });
});

describe('listPending', function () {
    it('returns only pending feedback', function () {
        KBArticleFeedback::factory()
            ->pending()
            ->count(2)
            ->create(['article_id' => $this->article->id]);

        KBArticleFeedback::factory()
            ->reviewed()
            ->create(['article_id' => $this->article->id]);

        $result = $this->service->listPending();

        expect($result->total())->toBe(2);
    });

    it('filters by article', function () {
        KBArticleFeedback::factory()
            ->pending()
            ->create(['article_id' => $this->article->id]);

        $otherArticle = KBArticle::factory()
            ->published()
            ->for($this->category, 'category')
            ->create();

        KBArticleFeedback::factory()
            ->pending()
            ->create(['article_id' => $otherArticle->id]);

        $result = $this->service->listPending(['article_id' => $this->article->id]);

        expect($result->total())->toBe(1);
    });

    it('filters by is_helpful', function () {
        KBArticleFeedback::factory()
            ->pending()
            ->create([
                'article_id' => $this->article->id,
                'is_helpful' => true,
            ]);

        KBArticleFeedback::factory()
            ->pending()
            ->create([
                'article_id' => $this->article->id,
                'is_helpful' => false,
            ]);

        $result = $this->service->listPending(['is_helpful' => false]);

        expect($result->total())->toBe(1);
    });
});

describe('resolve', function () {
    it('marks feedback as reviewed', function () {
        $feedback = KBArticleFeedback::factory()
            ->pending()
            ->create(['article_id' => $this->article->id]);

        $result = $this->service->resolve($feedback, $this->admin, 'Thank you for the feedback');

        expect($result->status)->toBe(KBFeedbackStatus::REVIEWED);
        expect($result->reviewed_by)->toBe($this->admin->id);
        expect($result->reviewed_at)->not->toBeNull();
        expect($result->admin_notes)->toBe('Thank you for the feedback');
    });
});

describe('action', function () {
    it('marks feedback as actioned', function () {
        $feedback = KBArticleFeedback::factory()
            ->pending()
            ->create(['article_id' => $this->article->id]);

        $result = $this->service->action($feedback, $this->admin, 'Updated the article');

        expect($result->status)->toBe(KBFeedbackStatus::ACTIONED);
    });
});

describe('dismiss', function () {
    it('marks feedback as dismissed', function () {
        $feedback = KBArticleFeedback::factory()
            ->pending()
            ->create(['article_id' => $this->article->id]);

        $result = $this->service->dismiss($feedback, $this->admin, 'Not applicable');

        expect($result->status)->toBe(KBFeedbackStatus::DISMISSED);
    });
});
