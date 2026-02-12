<?php

declare(strict_types=1);

use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ChangelogSubscription;
use App\Models\Feedback\ReleaseNote;

describe('GET /api/v1/changelog', function () {
    it('returns paginated published release notes', function () {
        ReleaseNote::factory()->published()->count(3)->create();
        ReleaseNote::factory()->draft()->create(); // Should not appear

        $response = $this->getJson('/api/v1/changelog');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'version',
                        'slug',
                        'title',
                        'content',
                        'release_type',
                        'release_type_label',
                        'status',
                        'published_at',
                        'items',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters by release type', function () {
        ReleaseNote::factory()->published()->major()->count(2)->create();
        ReleaseNote::factory()->published()->minor()->create();

        $response = $this->getJson('/api/v1/changelog?type=major');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('orders by published date descending', function () {
        $older = ReleaseNote::factory()->published()->create([
            'published_at' => now()->subDays(10),
            'version' => '1.0.0',
        ]);

        $newer = ReleaseNote::factory()->published()->create([
            'published_at' => now(),
            'version' => '1.1.0',
        ]);

        $response = $this->getJson('/api/v1/changelog');

        $response->assertOk();
        $data = $response->json('data');
        expect($data[0]['version'])->toBe('1.1.0');
    });

    it('excludes draft and scheduled release notes', function () {
        ReleaseNote::factory()->published()->create();
        ReleaseNote::factory()->draft()->create();
        ReleaseNote::factory()->scheduled()->create();

        $response = $this->getJson('/api/v1/changelog');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/changelog/{slug}', function () {
    it('returns release note by version slug', function () {
        $note = ReleaseNote::factory()
            ->published()
            ->withVersion('1.2.3')
            ->create();

        $response = $this->getJson('/api/v1/changelog/v1-2-3');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'version' => '1.2.3',
                ],
            ]);
    });

    it('returns 404 for non-existent version', function () {
        $response = $this->getJson('/api/v1/changelog/v99-99-99');

        $response->assertNotFound();
    });

    it('returns 404 for draft release note', function () {
        ReleaseNote::factory()
            ->draft()
            ->withVersion('2.0.0')
            ->create();

        $response = $this->getJson('/api/v1/changelog/v2-0-0');

        $response->assertNotFound();
    });
});

describe('POST /api/v1/changelog/subscribe', function () {
    it('creates a new subscription', function () {
        $response = $this->postJson('/api/v1/changelog/subscribe', [
            'email' => 'subscriber@example.com',
            'notify_major' => true,
            'notify_minor' => true,
            'notify_patch' => false,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'subscribed' => true,
                    'email' => 'subscriber@example.com',
                    'preferences' => [
                        'notify_major' => true,
                        'notify_minor' => true,
                        'notify_patch' => false,
                    ],
                ],
            ]);

        expect(ChangelogSubscription::count())->toBe(1);
    });

    it('reactivates inactive subscription', function () {
        $subscription = ChangelogSubscription::factory()->create([
            'email' => 'resubscriber@example.com',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/changelog/subscribe', [
            'email' => 'resubscriber@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'subscribed' => true,
                ],
            ]);

        $subscription->refresh();
        expect($subscription->is_active)->toBeTrue();
    });

    it('updates preferences for existing subscription', function () {
        ChangelogSubscription::factory()->create([
            'email' => 'existing@example.com',
            'notify_patch' => false,
        ]);

        $response = $this->postJson('/api/v1/changelog/subscribe', [
            'email' => 'existing@example.com',
            'notify_patch' => true,
        ]);

        $response->assertOk();

        $subscription = ChangelogSubscription::where('email', 'existing@example.com')->first();
        expect($subscription->notify_patch)->toBeTrue();
    });

    it('validates required email', function () {
        $response = $this->postJson('/api/v1/changelog/subscribe', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates email format', function () {
        $response = $this->postJson('/api/v1/changelog/subscribe', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/v1/changelog/unsubscribe', function () {
    it('unsubscribes by email', function () {
        $subscription = ChangelogSubscription::factory()->create([
            'email' => 'unsubscriber@example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/changelog/unsubscribe', [
            'email' => 'unsubscriber@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'unsubscribed' => true,
                ],
            ]);

        $subscription->refresh();
        expect($subscription->is_active)->toBeFalse();
    });

    it('returns 404 for non-existent subscription', function () {
        $response = $this->postJson('/api/v1/changelog/unsubscribe', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertNotFound();
    });
});
