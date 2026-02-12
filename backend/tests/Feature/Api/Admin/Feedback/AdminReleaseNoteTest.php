<?php

declare(strict_types=1);

use App\Enums\Feedback\ChangeType;
use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ReleaseNote;
use App\Models\Platform\SuperAdminUser;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->superAdmin()->create();
    Sanctum::actingAs($this->admin, ['*'], 'sanctum');
});

describe('GET /api/v1/admin/release-notes', function () {
    it('lists all release notes including drafts', function () {
        ReleaseNote::factory()->published()->count(2)->create();
        ReleaseNote::factory()->draft()->create();

        $response = $this->getJson('/api/v1/admin/release-notes');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'version',
                        'title',
                        'release_type',
                        'status',
                        'published_at',
                    ],
                ],
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters by status', function () {
        ReleaseNote::factory()->published()->count(2)->create();
        ReleaseNote::factory()->draft()->create();

        $response = $this->getJson('/api/v1/admin/release-notes?status=draft');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by release type', function () {
        ReleaseNote::factory()->major()->count(2)->create();
        ReleaseNote::factory()->minor()->create();

        $response = $this->getJson('/api/v1/admin/release-notes?type=major');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('searches release notes', function () {
        ReleaseNote::factory()->create(['title' => 'Major Feature Release']);
        ReleaseNote::factory()->create(['title' => 'Bug Fix Update']);

        $response = $this->getJson('/api/v1/admin/release-notes?search=Feature');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/admin/release-notes', function () {
    it('creates a new release note', function () {
        $response = $this->postJson('/api/v1/admin/release-notes', [
            'version' => '2.0.0',
            'title' => 'Major Release 2.0',
            'content' => '## What\'s New\n\nNew features...',
            'version_name' => 'Phoenix',
            'summary' => 'Major update with new features',
            'release_type' => ReleaseType::MAJOR->value,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'version',
                    'title',
                    'content',
                    'release_type',
                    'status',
                    'items',
                ],
            ])
            ->assertJson([
                'data' => [
                    'version' => '2.0.0',
                    'title' => 'Major Release 2.0',
                    'status' => ReleaseNoteStatus::DRAFT->value,
                ],
            ]);

        expect(ReleaseNote::count())->toBe(1);
    });

    it('creates release note with items', function () {
        $response = $this->postJson('/api/v1/admin/release-notes', [
            'version' => '1.5.0',
            'title' => 'Feature Update',
            'content' => 'Release content',
            'items' => [
                [
                    'title' => 'New dashboard',
                    'description' => 'Completely redesigned dashboard',
                    'change_type' => ChangeType::NEW_FEATURE->value,
                ],
                [
                    'title' => 'Fixed login issue',
                    'change_type' => ChangeType::BUG_FIX->value,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.items');
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/admin/release-notes', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['version', 'title', 'content']);
    });
});

describe('GET /api/v1/admin/release-notes/{releaseNote}', function () {
    it('returns release note details', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $response = $this->getJson("/api/v1/admin/release-notes/{$note->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $note->id,
                    'version' => $note->version,
                ],
            ]);
    });

    it('returns 404 for non-existent release note', function () {
        $response = $this->getJson('/api/v1/admin/release-notes/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/admin/release-notes/{releaseNote}', function () {
    it('updates release note', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $response = $this->putJson("/api/v1/admin/release-notes/{$note->id}", [
            'title' => 'Updated Title',
            'summary' => 'Updated summary',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated Title',
                    'summary' => 'Updated summary',
                ],
            ]);
    });

    it('updates release note items', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $response = $this->putJson("/api/v1/admin/release-notes/{$note->id}", [
            'items' => [
                [
                    'title' => 'New item',
                    'change_type' => ChangeType::IMPROVEMENT->value,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.items');
    });
});

describe('POST /api/v1/admin/release-notes/{releaseNote}/publish', function () {
    it('publishes a draft release note', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $response = $this->postJson("/api/v1/admin/release-notes/{$note->id}/publish");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => ReleaseNoteStatus::PUBLISHED->value,
                ],
            ]);

        $note->refresh();
        expect($note->published_at)->not->toBeNull();
    });

    it('fails if already published', function () {
        $note = ReleaseNote::factory()->published()->create();

        $response = $this->postJson("/api/v1/admin/release-notes/{$note->id}/publish");

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/admin/release-notes/{releaseNote}/unpublish', function () {
    it('unpublishes a published release note', function () {
        $note = ReleaseNote::factory()->published()->create();

        $response = $this->postJson("/api/v1/admin/release-notes/{$note->id}/unpublish");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => ReleaseNoteStatus::DRAFT->value,
                ],
            ]);
    });

    it('fails if not published', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $response = $this->postJson("/api/v1/admin/release-notes/{$note->id}/unpublish");

        $response->assertUnprocessable();
    });
});

describe('DELETE /api/v1/admin/release-notes/{releaseNote}', function () {
    it('deletes a draft release note', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $response = $this->deleteJson("/api/v1/admin/release-notes/{$note->id}");

        $response->assertOk();
        expect(ReleaseNote::count())->toBe(0);
    });

    it('fails to delete published release note', function () {
        $note = ReleaseNote::factory()->published()->create();

        $response = $this->deleteJson("/api/v1/admin/release-notes/{$note->id}");

        $response->assertUnprocessable();
    });
});
