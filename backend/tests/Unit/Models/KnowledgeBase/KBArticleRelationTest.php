<?php

declare(strict_types=1);

/**
 * KBArticleRelation Model Unit Tests
 *
 * Tests for the KBArticleRelation pivot model which represents
 * relationships between articles.
 *
 * @see \App\Models\KnowledgeBase\KBArticleRelation
 */

use App\Enums\KnowledgeBase\KBRelationType;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('has correct table name', function (): void {
    $pivot = new KBArticleRelation();

    expect($pivot->getTable())->toBe('kb_article_relations');
});

test('casts relation_type correctly', function (): void {
    $article = KBArticle::factory()->create();
    $related = KBArticle::factory()->create();

    $article->relatedArticles()->attach($related->id, [
        'relation_type' => KBRelationType::PREREQUISITE->value,
        'sort_order' => 0,
    ]);

    $pivot = $article->relatedArticles()->first()->pivot;

    expect($pivot->relation_type)->toBe(KBRelationType::PREREQUISITE->value);
});

test('pivot is created when adding relation', function (): void {
    $article = KBArticle::factory()->create();
    $related = KBArticle::factory()->create();

    $article->relatedArticles()->attach($related->id, [
        'relation_type' => 'related',
        'sort_order' => 0,
    ]);

    $this->assertDatabaseHas('kb_article_relations', [
        'article_id' => $article->id,
        'related_article_id' => $related->id,
        'relation_type' => 'related',
    ]);
});

test('pivot includes sort_order', function (): void {
    $article = KBArticle::factory()->create();
    $related = KBArticle::factory()->create();

    $article->relatedArticles()->attach($related->id, [
        'relation_type' => 'related',
        'sort_order' => 5,
    ]);

    $pivot = $article->relatedArticles()->first()->pivot;

    expect($pivot->sort_order)->toBe(5);
});

test('isPrerequisite returns correct value', function (): void {
    $relation = new KBArticleRelation();
    $relation->relation_type = KBRelationType::PREREQUISITE;

    expect($relation->isPrerequisite())->toBeTrue()
        ->and($relation->isNextStep())->toBeFalse()
        ->and($relation->isRelated())->toBeFalse();
});

test('isNextStep returns correct value', function (): void {
    $relation = new KBArticleRelation();
    $relation->relation_type = KBRelationType::NEXT_STEP;

    expect($relation->isNextStep())->toBeTrue()
        ->and($relation->isPrerequisite())->toBeFalse()
        ->and($relation->isRelated())->toBeFalse();
});

test('isRelated returns correct value', function (): void {
    $relation = new KBArticleRelation();
    $relation->relation_type = KBRelationType::RELATED;

    expect($relation->isRelated())->toBeTrue()
        ->and($relation->isPrerequisite())->toBeFalse()
        ->and($relation->isNextStep())->toBeFalse();
});

test('prerequisiteArticles relationship filters by type', function (): void {
    $article = KBArticle::factory()->create();
    $prereq = KBArticle::factory()->create();
    $related = KBArticle::factory()->create();

    $article->relatedArticles()->attach($prereq->id, [
        'relation_type' => KBRelationType::PREREQUISITE->value,
        'sort_order' => 0,
    ]);
    $article->relatedArticles()->attach($related->id, [
        'relation_type' => KBRelationType::RELATED->value,
        'sort_order' => 0,
    ]);

    expect($article->prerequisiteArticles)->toHaveCount(1)
        ->and($article->prerequisiteArticles->first()->id)->toBe($prereq->id);
});

test('nextStepArticles relationship filters by type', function (): void {
    $article = KBArticle::factory()->create();
    $nextStep = KBArticle::factory()->create();
    $related = KBArticle::factory()->create();

    $article->relatedArticles()->attach($nextStep->id, [
        'relation_type' => KBRelationType::NEXT_STEP->value,
        'sort_order' => 0,
    ]);
    $article->relatedArticles()->attach($related->id, [
        'relation_type' => KBRelationType::RELATED->value,
        'sort_order' => 0,
    ]);

    expect($article->nextStepArticles)->toHaveCount(1)
        ->and($article->nextStepArticles->first()->id)->toBe($nextStep->id);
});

test('relations are ordered by sort_order', function (): void {
    $article = KBArticle::factory()->create();
    $first = KBArticle::factory()->create();
    $second = KBArticle::factory()->create();
    $third = KBArticle::factory()->create();

    $article->relatedArticles()->attach($third->id, ['relation_type' => 'related', 'sort_order' => 3]);
    $article->relatedArticles()->attach($first->id, ['relation_type' => 'related', 'sort_order' => 1]);
    $article->relatedArticles()->attach($second->id, ['relation_type' => 'related', 'sort_order' => 2]);

    $ordered = $article->relatedArticles;

    expect($ordered->first()->id)->toBe($first->id)
        ->and($ordered->last()->id)->toBe($third->id);
});

test('detaching removes relation', function (): void {
    $article = KBArticle::factory()->create();
    $related = KBArticle::factory()->create();

    $article->relatedArticles()->attach($related->id, [
        'relation_type' => 'related',
        'sort_order' => 0,
    ]);
    $article->relatedArticles()->detach($related->id);

    $this->assertDatabaseMissing('kb_article_relations', [
        'article_id' => $article->id,
        'related_article_id' => $related->id,
    ]);
});
