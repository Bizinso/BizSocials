<?php

declare(strict_types=1);

use App\Data\Feedback\CreateReleaseNoteData;
use App\Data\Feedback\SubscribeChangelogData;
use App\Data\Feedback\UpdateReleaseNoteData;
use App\Enums\Feedback\ChangeType;
use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ChangelogSubscription;
use App\Models\Feedback\ReleaseNote;
use App\Services\Feedback\ReleaseNoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(ReleaseNoteService::class);
});

describe('listPublished', function () {
    it('returns only published release notes', function () {
        ReleaseNote::factory()->published()->count(3)->create();
        ReleaseNote::factory()->draft()->create();
        ReleaseNote::factory()->scheduled()->create();

        $result = $this->service->listPublished();

        expect($result->total())->toBe(3);
    });

    it('filters by release type', function () {
        ReleaseNote::factory()->published()->major()->count(2)->create();
        ReleaseNote::factory()->published()->minor()->create();

        $result = $this->service->listPublished(['type' => 'major']);

        expect($result->total())->toBe(2);
    });
});

describe('getBySlug', function () {
    it('returns release note by version slug', function () {
        ReleaseNote::factory()->published()->withVersion('1.2.3')->create();

        $result = $this->service->getBySlug('v1-2-3');

        expect($result->version)->toBe('1.2.3');
    });

    it('throws exception for non-existent version', function () {
        expect(fn () => $this->service->getBySlug('v99-99-99'))
            ->toThrow(ModelNotFoundException::class);
    });

    it('throws exception for draft release note', function () {
        ReleaseNote::factory()->draft()->withVersion('2.0.0')->create();

        expect(fn () => $this->service->getBySlug('v2-0-0'))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('subscribe', function () {
    it('creates new subscription', function () {
        $data = new SubscribeChangelogData(
            email: 'subscriber@example.com',
            notify_major: true,
            notify_minor: true,
            notify_patch: false,
        );

        $subscription = $this->service->subscribe($data);

        expect($subscription->email)->toBe('subscriber@example.com');
        expect($subscription->is_active)->toBeTrue();
        expect($subscription->notify_major)->toBeTrue();
        expect($subscription->notify_patch)->toBeFalse();
    });

    it('reactivates inactive subscription', function () {
        ChangelogSubscription::factory()->create([
            'email' => 'resubscriber@example.com',
            'is_active' => false,
        ]);

        $data = new SubscribeChangelogData(email: 'resubscriber@example.com');

        $subscription = $this->service->subscribe($data);

        expect($subscription->is_active)->toBeTrue();
    });

    it('updates preferences for existing subscription', function () {
        ChangelogSubscription::factory()->create([
            'email' => 'existing@example.com',
            'notify_patch' => false,
        ]);

        $data = new SubscribeChangelogData(
            email: 'existing@example.com',
            notify_patch: true,
        );

        $subscription = $this->service->subscribe($data);

        expect($subscription->notify_patch)->toBeTrue();
    });
});

describe('unsubscribe', function () {
    it('deactivates subscription by email', function () {
        $subscription = ChangelogSubscription::factory()->create([
            'email' => 'unsubscriber@example.com',
            'is_active' => true,
        ]);

        $this->service->unsubscribe('unsubscriber@example.com');

        $subscription->refresh();
        expect($subscription->is_active)->toBeFalse();
        expect($subscription->unsubscribed_at)->not->toBeNull();
    });

    it('throws exception for non-existent subscription', function () {
        expect(fn () => $this->service->unsubscribe('nonexistent@example.com'))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('create', function () {
    it('creates release note with all fields', function () {
        $data = new CreateReleaseNoteData(
            version: '2.0.0',
            title: 'Major Release',
            content: '## What\'s New',
            version_name: 'Phoenix',
            summary: 'Major update',
            release_type: ReleaseType::MAJOR,
        );

        $note = $this->service->create($data);

        expect($note)->toBeInstanceOf(ReleaseNote::class);
        expect($note->version)->toBe('2.0.0');
        expect($note->title)->toBe('Major Release');
        expect($note->status)->toBe(ReleaseNoteStatus::DRAFT);
    });

    it('creates release note with items', function () {
        $data = new CreateReleaseNoteData(
            version: '1.5.0',
            title: 'Feature Update',
            content: 'Content',
            items: [
                [
                    'title' => 'New dashboard',
                    'description' => 'Redesigned',
                    'change_type' => ChangeType::NEW_FEATURE->value,
                ],
                [
                    'title' => 'Fixed bug',
                    'change_type' => ChangeType::BUG_FIX->value,
                ],
            ],
        );

        $note = $this->service->create($data);

        expect($note->items)->toHaveCount(2);
    });
});

describe('update', function () {
    it('updates release note fields', function () {
        $note = ReleaseNote::factory()->draft()->create();
        $data = new UpdateReleaseNoteData(
            title: 'Updated Title',
            summary: 'Updated summary',
        );

        $result = $this->service->update($note, $data);

        expect($result->title)->toBe('Updated Title');
        expect($result->summary)->toBe('Updated summary');
    });

    it('replaces items when provided', function () {
        $note = ReleaseNote::factory()
            ->draft()
            ->has(\App\Models\Feedback\ReleaseNoteItem::factory()->count(3), 'items')
            ->create();

        $data = new UpdateReleaseNoteData(
            items: [
                [
                    'title' => 'New item only',
                    'change_type' => ChangeType::IMPROVEMENT->value,
                ],
            ],
        );

        $result = $this->service->update($note, $data);

        expect($result->items)->toHaveCount(1);
    });
});

describe('publish', function () {
    it('publishes draft release note', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $result = $this->service->publish($note);

        expect($result->status)->toBe(ReleaseNoteStatus::PUBLISHED);
        expect($result->published_at)->not->toBeNull();
    });

    it('throws exception if already published', function () {
        $note = ReleaseNote::factory()->published()->create();

        expect(fn () => $this->service->publish($note))
            ->toThrow(ValidationException::class);
    });
});

describe('unpublish', function () {
    it('unpublishes published release note', function () {
        $note = ReleaseNote::factory()->published()->create();

        $result = $this->service->unpublish($note);

        expect($result->status)->toBe(ReleaseNoteStatus::DRAFT);
    });

    it('throws exception if not published', function () {
        $note = ReleaseNote::factory()->draft()->create();

        expect(fn () => $this->service->unpublish($note))
            ->toThrow(ValidationException::class);
    });
});

describe('delete', function () {
    it('deletes draft release note', function () {
        $note = ReleaseNote::factory()->draft()->create();

        $this->service->delete($note);

        expect(ReleaseNote::count())->toBe(0);
    });

    it('throws exception for published release note', function () {
        $note = ReleaseNote::factory()->published()->create();

        expect(fn () => $this->service->delete($note))
            ->toThrow(ValidationException::class);
    });
});

describe('listAll', function () {
    it('returns all release notes including drafts', function () {
        ReleaseNote::factory()->published()->count(2)->create();
        ReleaseNote::factory()->draft()->create();

        $result = $this->service->listAll();

        expect($result->total())->toBe(3);
    });

    it('filters by status', function () {
        ReleaseNote::factory()->published()->count(2)->create();
        ReleaseNote::factory()->draft()->create();

        $result = $this->service->listAll(['status' => 'draft']);

        expect($result->total())->toBe(1);
    });
});
