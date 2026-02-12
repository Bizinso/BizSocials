<?php

declare(strict_types=1);

use App\Models\Support\SupportCategory;
use App\Models\Tenant\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

describe('GET /api/v1/support/categories', function () {
    it('returns only active categories', function () {
        SupportCategory::factory()->active()->count(3)->create();
        SupportCategory::factory()->inactive()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/support/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'color',
                        'icon',
                        'is_active',
                        'sort_order',
                        'ticket_count',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    });

    it('returns categories ordered by sort_order', function () {
        SupportCategory::factory()->active()->create(['name' => 'Third', 'sort_order' => 30]);
        SupportCategory::factory()->active()->create(['name' => 'First', 'sort_order' => 10]);
        SupportCategory::factory()->active()->create(['name' => 'Second', 'sort_order' => 20]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/support/categories');

        $response->assertOk();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('First');
        expect($data[1]['name'])->toBe('Second');
        expect($data[2]['name'])->toBe('Third');
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/support/categories');

        $response->assertUnauthorized();
    });
});
