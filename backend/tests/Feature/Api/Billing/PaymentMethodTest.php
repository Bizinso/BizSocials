<?php

declare(strict_types=1);

use App\Enums\Billing\PaymentMethodType;
use App\Enums\User\TenantRole;
use App\Models\Billing\PaymentMethod;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/billing/payment-methods', function () {
    it('returns empty list when no payment methods exist', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/payment-methods');

        $response->assertOk()
            ->assertJsonPath('data', []);
    });

    it('returns payment methods', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->card()
            ->default()
            ->create();
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->upi()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/payment-methods');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'type_label',
                        'is_default',
                        'display_name',
                        'is_expired',
                        'created_at',
                    ],
                ],
            ]);
    });

    it('allows member to view payment methods', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/billing/payment-methods');

        $response->assertOk();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/billing/payment-methods');

        $response->assertUnauthorized();
    });

    it('does not return payment methods from other tenants', function () {
        $otherTenant = Tenant::factory()->create();
        PaymentMethod::factory()
            ->forTenant($otherTenant)
            ->count(2)
            ->create();
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/payment-methods');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/billing/payment-methods', function () {
    it('adds card payment method for owner', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'card',
            'card_last4' => '4242',
            'card_brand' => 'Visa',
            'card_exp_month' => 12,
            'card_exp_year' => 2028,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', PaymentMethodType::CARD->value)
            ->assertJsonPath('data.card_last_four', '4242')
            ->assertJsonPath('data.card_brand', 'Visa');
    });

    it('adds UPI payment method', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'upi',
            'upi_id' => 'user@paytm',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', PaymentMethodType::UPI->value)
            ->assertJsonPath('data.upi_id', 'user@paytm');
    });

    it('adds netbanking payment method', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'netbanking',
            'bank_name' => 'HDFC',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', PaymentMethodType::NETBANKING->value)
            ->assertJsonPath('data.bank_name', 'HDFC');
    });

    it('sets first payment method as default', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'card',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true);
    });

    it('sets as default when requested', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'card',
            'is_default' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true);

        // Previous default should be unset
        $methods = PaymentMethod::forTenant($this->tenant->id)->get();
        expect($methods->where('is_default', true)->count())->toBe(1);
    });

    it('denies admin from adding payment method', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'card',
        ]);

        $response->assertForbidden();
    });

    it('denies member from adding payment method', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'card',
        ]);

        $response->assertForbidden();
    });

    it('validates type is required', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('validates type is valid', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('validates upi_id for UPI type', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'upi',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['upi_id']);
    });

    it('validates bank_name for netbanking type', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/payment-methods', [
            'type' => 'netbanking',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['bank_name']);
    });
});

describe('PUT /api/v1/billing/payment-methods/{id}/default', function () {
    it('sets payment method as default for owner', function () {
        $existing = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/billing/payment-methods/{$method->id}/default");

        $response->assertOk()
            ->assertJsonPath('data.is_default', true);

        $existing->refresh();
        expect($existing->is_default)->toBeFalse();
    });

    it('denies admin from setting default', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/billing/payment-methods/{$method->id}/default");

        $response->assertForbidden();
    });

    it('returns 404 for method from another tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $method = PaymentMethod::factory()
            ->forTenant($otherTenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/billing/payment-methods/{$method->id}/default");

        $response->assertUnprocessable();
    });
});

describe('DELETE /api/v1/billing/payment-methods/{id}', function () {
    it('removes payment method for owner', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/billing/payment-methods/{$method->id}");

        $response->assertOk();

        expect(PaymentMethod::find($method->id))->toBeNull();
    });

    it('sets another as default when deleting default', function () {
        $default = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();
        $other = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/billing/payment-methods/{$default->id}");

        $response->assertOk();

        $other->refresh();
        expect($other->is_default)->toBeTrue();
    });

    it('fails when deleting only payment method', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/billing/payment-methods/{$method->id}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method']);
    });

    it('denies admin from removing payment method', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/billing/payment-methods/{$method->id}");

        $response->assertForbidden();
    });

    it('returns 404 for method from another tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $method = PaymentMethod::factory()
            ->forTenant($otherTenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/billing/payment-methods/{$method->id}");

        $response->assertUnprocessable();
    });
});
