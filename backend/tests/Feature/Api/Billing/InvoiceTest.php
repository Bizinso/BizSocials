<?php

declare(strict_types=1);

use App\Enums\Billing\InvoiceStatus;
use App\Enums\User\TenantRole;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->plan = PlanDefinition::factory()->professional()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/billing/invoices', function () {
    it('returns empty list when no invoices exist', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertOk()
            ->assertJsonPath('data', []);
    });

    it('returns paginated invoices', function () {
        Invoice::factory()
            ->count(3)
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'subscription_id',
                        'invoice_number',
                        'status',
                        'currency',
                        'subtotal',
                        'tax',
                        'total',
                        'amount_paid',
                        'amount_due',
                        'due_date',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    });

    it('allows any tenant member to view invoices', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertOk();
    });

    it('filters by status', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->issued()
            ->create();
        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/invoices?status=paid');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', InvoiceStatus::PAID->value);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertUnauthorized();
    });

    it('does not return invoices from other tenants', function () {
        $otherTenant = Tenant::factory()->create();
        Invoice::factory()
            ->forTenant($otherTenant)
            ->count(2)
            ->create();
        Invoice::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/billing/invoices/{invoice}', function () {
    it('returns invoice details', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/billing/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.invoice_number', $invoice->invoice_number);
    });

    it('returns 404 for non-existent invoice', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/invoices/00000000-0000-0000-0000-000000000000');

        $response->assertUnprocessable();
    });

    it('returns 404 for invoice from another tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $invoice = Invoice::factory()
            ->forTenant($otherTenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/billing/invoices/{$invoice->id}");

        $response->assertUnprocessable();
    });

    it('allows member to view invoice', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->create();

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/billing/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id);
    });
});

describe('GET /api/v1/billing/invoices/{invoice}/download', function () {
    it('returns download URL', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->create([
                'pdf_url' => 'https://example.com/invoices/test-invoice.pdf',
            ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/billing/invoices/{$invoice->id}/download");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'download_url',
                ],
            ]);
    });

    it('returns 404 for invoice from another tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $invoice = Invoice::factory()
            ->forTenant($otherTenant)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/billing/invoices/{$invoice->id}/download");

        $response->assertUnprocessable();
    });
});
