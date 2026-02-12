<?php

declare(strict_types=1);

/**
 * KBArticleVersion Model Unit Tests
 *
 * Tests for the KBArticleVersion model which represents version history of articles.
 *
 * @see \App\Models\KnowledgeBase\KBArticleVersion
 */

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleVersion;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create version with factory', function (): void {
    $version = KBArticleVersion::factory()->create();

    expect($version)->toBeInstanceOf(KBArticleVersion::class)
        ->and($version->id)->not->toBeNull()
        ->and($version->version)->toBeInt();
});

test('has correct table name', function (): void {
    $version = new KBArticleVersion();

    expect($version->getTable())->toBe('kb_article_versions');
});

test('casts attributes correctly', function (): void {
    $version = KBArticleVersion::factory()->create(['version' => 5]);

    expect($version->version)->toBeInt()
        ->and($version->version)->toBe(5);
});

test('article relationship works', function (): void {
    $article = KBArticle::factory()->create();
    $version = KBArticleVersion::factory()->forArticle($article)->create();

    expect($version->article)->toBeInstanceOf(KBArticle::class)
        ->and($version->article->id)->toBe($article->id);
});

test('changedBy relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $version = KBArticleVersion::factory()->byAuthor($admin)->create();

    expect($version->changedBy)->toBeInstanceOf(SuperAdminUser::class)
        ->and($version->changedBy->id)->toBe($admin->id);
});

test('forArticle scope filters by article', function (): void {
    $article = KBArticle::factory()->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(1)->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(2)->create();
    KBArticleVersion::factory()->create();

    expect(KBArticleVersion::forArticle($article->id)->count())->toBe(2);
});

test('latestFirst scope orders by version descending', function (): void {
    $article = KBArticle::factory()->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(1)->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(3)->create();
    KBArticleVersion::factory()->forArticle($article)->withVersion(2)->create();

    $versions = KBArticleVersion::forArticle($article->id)->latestFirst()->get();

    expect($versions->first()->version)->toBe(3)
        ->and($versions->last()->version)->toBe(1);
});

test('isLatest returns true for current version', function (): void {
    $article = KBArticle::factory()->create(['version' => 3]);
    $latestVersion = KBArticleVersion::factory()->forArticle($article)->withVersion(3)->create();
    $olderVersion = KBArticleVersion::factory()->forArticle($article)->withVersion(2)->create();

    expect($latestVersion->isLatest())->toBeTrue()
        ->and($olderVersion->isLatest())->toBeFalse();
});

test('getDiff returns differences between versions', function (): void {
    $article = KBArticle::factory()->create();
    $version1 = KBArticleVersion::factory()->forArticle($article)->create([
        'version' => 1,
        'title' => 'Original Title',
        'content' => 'Original content.',
    ]);
    $version2 = KBArticleVersion::factory()->forArticle($article)->create([
        'version' => 2,
        'title' => 'Updated Title',
        'content' => 'Updated content.',
    ]);

    $diff = $version2->getDiff($version1);

    expect($diff)->toHaveKeys(['title', 'content'])
        ->and($diff['title']['old'])->toBe('Original Title')
        ->and($diff['title']['new'])->toBe('Updated Title')
        ->and($diff['content']['old'])->toBe('Original content.')
        ->and($diff['content']['new'])->toBe('Updated content.');
});

test('getDiff returns empty array when no differences', function (): void {
    $article = KBArticle::factory()->create();
    $version1 = KBArticleVersion::factory()->forArticle($article)->create([
        'version' => 1,
        'title' => 'Same Title',
        'content' => 'Same content.',
    ]);
    $version2 = KBArticleVersion::factory()->forArticle($article)->create([
        'version' => 2,
        'title' => 'Same Title',
        'content' => 'Same content.',
    ]);

    $diff = $version2->getDiff($version1);

    expect($diff)->toBeEmpty();
});

test('forArticle factory state uses article data', function (): void {
    $article = KBArticle::factory()->create([
        'title' => 'Test Article',
        'content' => 'Test content.',
    ]);
    $version = KBArticleVersion::factory()->forArticle($article)->create();

    expect($version->title)->toBe('Test Article')
        ->and($version->content)->toBe('Test content.')
        ->and($version->article_id)->toBe($article->id);
});

test('withChangeSummary factory state sets summary', function (): void {
    $version = KBArticleVersion::factory()
        ->withChangeSummary('Fixed typos and updated examples')
        ->create();

    expect($version->change_summary)->toBe('Fixed typos and updated examples');
});
