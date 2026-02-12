<?php

declare(strict_types=1);

/**
 * KBArticle Model Unit Tests
 *
 * Tests for the KBArticle model which represents knowledge base articles.
 *
 * @see \App\Models\KnowledgeBase\KBArticle
 */

use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use App\Enums\KnowledgeBase\KBRelationType;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\KnowledgeBase\KBArticleVersion;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBTag;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create article with factory', function (): void {
    $article = KBArticle::factory()->create();

    expect($article)->toBeInstanceOf(KBArticle::class)
        ->and($article->id)->not->toBeNull()
        ->and($article->title)->not->toBeNull()
        ->and($article->content)->not->toBeNull();
});

test('has correct table name', function (): void {
    $article = new KBArticle();

    expect($article->getTable())->toBe('kb_articles');
});

test('casts attributes correctly', function (): void {
    $article = KBArticle::factory()->published()->create();

    expect($article->status)->toBeInstanceOf(KBArticleStatus::class)
        ->and($article->article_type)->toBeInstanceOf(KBArticleType::class)
        ->and($article->difficulty_level)->toBeInstanceOf(KBDifficultyLevel::class)
        ->and($article->is_featured)->toBeBool()
        ->and($article->view_count)->toBeInt()
        ->and($article->version)->toBeInt();
});

test('category relationship works', function (): void {
    $category = KBCategory::factory()->create();
    $article = KBArticle::factory()->forCategory($category)->create();

    expect($article->category)->toBeInstanceOf(KBCategory::class)
        ->and($article->category->id)->toBe($category->id);
});

test('author relationship works', function (): void {
    $author = SuperAdminUser::factory()->create();
    $article = KBArticle::factory()->byAuthor($author)->create();

    expect($article->author)->toBeInstanceOf(SuperAdminUser::class)
        ->and($article->author->id)->toBe($author->id);
});

test('tags relationship works', function (): void {
    $article = KBArticle::factory()->create();
    $tags = KBTag::factory()->count(3)->create();

    $article->tags()->attach($tags->pluck('id'));

    expect($article->tags)->toHaveCount(3)
        ->and($article->tags->first())->toBeInstanceOf(KBTag::class);
});

test('feedback relationship works', function (): void {
    $article = KBArticle::factory()->create();
    KBArticleFeedback::factory()->forArticle($article)->count(2)->create();

    expect($article->feedback)->toHaveCount(2)
        ->and($article->feedback->first())->toBeInstanceOf(KBArticleFeedback::class);
});

test('versions relationship works', function (): void {
    $article = KBArticle::factory()->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(1)->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(2)->create();

    expect($article->versions)->toHaveCount(2)
        ->and($article->versions->first())->toBeInstanceOf(KBArticleVersion::class);
});

test('published scope filters published articles', function (): void {
    KBArticle::factory()->published()->count(2)->create();
    KBArticle::factory()->draft()->create();

    expect(KBArticle::published()->count())->toBe(2);
});

test('draft scope filters draft articles', function (): void {
    KBArticle::factory()->published()->create();
    KBArticle::factory()->draft()->count(2)->create();

    expect(KBArticle::draft()->count())->toBe(2);
});

test('archived scope filters archived articles', function (): void {
    KBArticle::factory()->published()->create();
    KBArticle::factory()->archived()->count(2)->create();

    expect(KBArticle::archived()->count())->toBe(2);
});

test('featured scope filters featured articles', function (): void {
    KBArticle::factory()->featured()->count(2)->create();
    KBArticle::factory()->create(['is_featured' => false]);

    expect(KBArticle::featured()->count())->toBe(2);
});

test('forCategory scope filters by category', function (): void {
    $category = KBCategory::factory()->create();
    KBArticle::factory()->forCategory($category)->count(2)->create();
    KBArticle::factory()->create();

    expect(KBArticle::forCategory($category->id)->count())->toBe(2);
});

test('ofType scope filters by article type', function (): void {
    KBArticle::factory()->ofType(KBArticleType::HOW_TO)->count(2)->create();
    KBArticle::factory()->ofType(KBArticleType::FAQ)->create();

    expect(KBArticle::ofType(KBArticleType::HOW_TO)->count())->toBe(2);
});

test('withDifficulty scope filters by difficulty level', function (): void {
    KBArticle::factory()->withDifficulty(KBDifficultyLevel::BEGINNER)->count(2)->create();
    KBArticle::factory()->withDifficulty(KBDifficultyLevel::ADVANCED)->create();

    expect(KBArticle::withDifficulty(KBDifficultyLevel::BEGINNER)->count())->toBe(2);
});

test('popular scope orders by view count descending', function (): void {
    KBArticle::factory()->create(['view_count' => 100]);
    KBArticle::factory()->create(['view_count' => 500]);
    KBArticle::factory()->create(['view_count' => 200]);

    $popular = KBArticle::popular()->get();

    expect($popular->first()->view_count)->toBe(500)
        ->and($popular->last()->view_count)->toBe(100);
});

test('isPublished returns correct value', function (): void {
    $published = KBArticle::factory()->published()->create();
    $draft = KBArticle::factory()->draft()->create();

    expect($published->isPublished())->toBeTrue()
        ->and($draft->isPublished())->toBeFalse();
});

test('isDraft returns correct value', function (): void {
    $draft = KBArticle::factory()->draft()->create();
    $published = KBArticle::factory()->published()->create();

    expect($draft->isDraft())->toBeTrue()
        ->and($published->isDraft())->toBeFalse();
});

test('isArchived returns correct value', function (): void {
    $archived = KBArticle::factory()->archived()->create();
    $published = KBArticle::factory()->published()->create();

    expect($archived->isArchived())->toBeTrue()
        ->and($published->isArchived())->toBeFalse();
});

test('publish changes status to published', function (): void {
    $article = KBArticle::factory()->draft()->create();

    $article->publish();

    expect($article->fresh()->status)->toBe(KBArticleStatus::PUBLISHED)
        ->and($article->fresh()->published_at)->not->toBeNull();
});

test('unpublish changes status to draft', function (): void {
    $article = KBArticle::factory()->published()->create();

    $article->unpublish();

    expect($article->fresh()->status)->toBe(KBArticleStatus::DRAFT);
});

test('archive changes status to archived', function (): void {
    $article = KBArticle::factory()->published()->create();

    $article->archive();

    expect($article->fresh()->status)->toBe(KBArticleStatus::ARCHIVED);
});

test('incrementViewCount increases view count', function (): void {
    $article = KBArticle::factory()->create(['view_count' => 10]);

    $article->incrementViewCount();

    expect($article->fresh()->view_count)->toBe(11);
});

test('recordHelpfulVote increases helpful count', function (): void {
    $article = KBArticle::factory()->create(['helpful_count' => 5]);

    $article->recordHelpfulVote();

    expect($article->fresh()->helpful_count)->toBe(6);
});

test('recordNotHelpfulVote increases not helpful count', function (): void {
    $article = KBArticle::factory()->create(['not_helpful_count' => 3]);

    $article->recordNotHelpfulVote();

    expect($article->fresh()->not_helpful_count)->toBe(4);
});

test('getHelpfulPercentage calculates correctly', function (): void {
    $article = KBArticle::factory()->create([
        'helpful_count' => 80,
        'not_helpful_count' => 20,
    ]);

    expect($article->getHelpfulPercentage())->toBe(80.0);
});

test('getHelpfulPercentage returns zero when no votes', function (): void {
    $article = KBArticle::factory()->create([
        'helpful_count' => 0,
        'not_helpful_count' => 0,
    ]);

    expect($article->getHelpfulPercentage())->toBe(0.0);
});

test('createVersion creates a version record', function (): void {
    $author = SuperAdminUser::factory()->create();
    $article = KBArticle::factory()->create(['version' => 1]);

    $version = $article->createVersion('Initial version', $author->id);

    expect($version)->toBeInstanceOf(KBArticleVersion::class)
        ->and($version->version)->toBe(1)
        ->and($version->article_id)->toBe($article->id);
});

test('getUrl returns correct URL format', function (): void {
    $category = KBCategory::factory()->create(['slug' => 'test-category']);
    $article = KBArticle::factory()->forCategory($category)->create(['slug' => 'test-article']);

    expect($article->getUrl())->toBe('/kb/test-category/test-article');
});

test('attachTag adds tag to article', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create(['usage_count' => 0]);

    $article->attachTag($tag);

    expect($article->tags()->where('tag_id', $tag->id)->exists())->toBeTrue()
        ->and($tag->fresh()->usage_count)->toBe(1);
});

test('detachTag removes tag from article', function (): void {
    $article = KBArticle::factory()->create();
    $tag = KBTag::factory()->create(['usage_count' => 1]);
    $article->tags()->attach($tag->id);

    $article->detachTag($tag);

    expect($article->tags()->where('tag_id', $tag->id)->exists())->toBeFalse()
        ->and($tag->fresh()->usage_count)->toBe(0);
});

test('addRelation creates relation between articles', function (): void {
    $article = KBArticle::factory()->create();
    $relatedArticle = KBArticle::factory()->create();

    $article->addRelation($relatedArticle, KBRelationType::PREREQUISITE);

    expect($article->relatedArticles()->where('related_article_id', $relatedArticle->id)->exists())->toBeTrue();
});

test('removeRelation removes relation between articles', function (): void {
    $article = KBArticle::factory()->create();
    $relatedArticle = KBArticle::factory()->create();
    $article->relatedArticles()->attach($relatedArticle->id, ['relation_type' => 'related']);

    $article->removeRelation($relatedArticle);

    expect($article->relatedArticles()->where('related_article_id', $relatedArticle->id)->exists())->toBeFalse();
});
