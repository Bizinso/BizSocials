<?php

declare(strict_types=1);

/**
 * KBArticleTag Model Unit Tests
 *
 * Tests for the KBArticleTag pivot model which represents the
 * many-to-many relationship between articles and tags.
 *
 * @see \App\Models\KnowledgeBase\KBArticleTag
 */

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleTag;
use App\Models\KnowledgeBase\KBTag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('has correct table name', function (): void {
    $pivot = new KBArticleTag();

    expect($pivot->getTable())->toBe('kb_article_tags');
});

test('pivot is created when attaching tag to article', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create();

    $article->tags()->attach($tag->id);

    $this->assertDatabaseHas('kb_article_tags', [
        'article_id' => $article->id,
        'tag_id' => $tag->id,
    ]);
});

test('pivot includes timestamps', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create();

    $article->tags()->attach($tag->id);

    $pivot = $article->tags()->first()->pivot;

    expect($pivot->created_at)->not->toBeNull()
        ->and($pivot->updated_at)->not->toBeNull();
});

test('article relationship works through pivot', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create();
    $article->tags()->attach($tag->id);

    $tagFromArticle = $article->tags()->first();

    expect($tagFromArticle)->toBeInstanceOf(KBTag::class)
        ->and($tagFromArticle->id)->toBe($tag->id);
});

test('tag relationship works through pivot', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create();
    $article->tags()->attach($tag->id);

    $articleFromTag = $tag->articles()->first();

    expect($articleFromTag)->toBeInstanceOf(KBArticle::class)
        ->and($articleFromTag->id)->toBe($article->id);
});

test('detaching removes pivot record', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create();
    $article->tags()->attach($tag->id);

    $article->tags()->detach($tag->id);

    $this->assertDatabaseMissing('kb_article_tags', [
        'article_id' => $article->id,
        'tag_id' => $tag->id,
    ]);
});

test('multiple tags can be attached to one article', function (): void {
    $article = KBArticle::factory()->create();
    $tags = KBTag::factory()->count(5)->create();

    $article->tags()->attach($tags->pluck('id'));

    expect($article->tags)->toHaveCount(5);
});

test('multiple articles can have same tag', function (): void {
    $tag = KBTag::factory()->create();
    $articles = KBArticle::factory()->count(3)->create();

    foreach ($articles as $article) {
        $article->tags()->attach($tag->id);
    }

    expect($tag->articles)->toHaveCount(3);
});

test('sync replaces all tags', function (): void {
    $article = KBArticle::factory()->create();
    $originalTags = KBTag::factory()->count(3)->create();
    $newTags = KBTag::factory()->count(2)->create();

    $article->tags()->attach($originalTags->pluck('id'));
    $article->tags()->sync($newTags->pluck('id'));

    expect($article->tags)->toHaveCount(2)
        ->and($article->tags->pluck('id')->toArray())->toBe($newTags->pluck('id')->toArray());
});
