<?php

declare(strict_types=1);

use App\Enums\Billing\InvoiceStatus;
use App\Enums\User\TenantRole;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new InvoiceService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->plan = PlanDefinition::factory()->professional()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('listForTenant', function () {
    it('returns empty paginator when no invoices', function () {
        $result = $this->service->listForTenant($this->tenant);

        expect($result->total())->toBe(0);
    });

    it('returns paginated invoices', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->count(5)
            ->create();

        $result = $this->service->listForTenant($this->tenant);

        expect($result->total())->toBe(5);
    });

    it('filters by status', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->issued()
            ->count(3)
            ->create();
        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->count(2)
            ->create();

        $result = $this->service->listForTenant($this->tenant, ['status' => 'paid']);

        expect($result->total())->toBe(2);
    });

    it('does not include invoices from other tenants', function () {
        $otherTenant = Tenant::factory()->create();
        Invoice::factory()
            ->forTenant($otherTenant)
            ->count(3)
            ->create();
        Invoice::factory()
            ->forTenant($this->tenant)
            ->count(2)
            ->create();

        $result = $this->service->listForTenant($this->tenant);

        expect($result->total())->toBe(2);
    });

    it('respects per_page limit', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->count(20)
            ->create();

        $result = $this->service->listForTenant($this->tenant, ['per_page' => 5]);

        expect($result->perPage())->toBe(5);
        expect($result->total())->toBe(20);
    });
});

describe('get', function () {
    it('returns invoice by id', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->get($invoice->id);

        expect($result->id)->toBe($invoice->id);
    });

    it('throws exception when invoice not found', function () {
        expect(fn () => $this->service->get('00000000-0000-0000-0000-000000000000'))
            ->toThrow(ValidationException::class);
    });
});

describe('getByTenant', function () {
    it('returns invoice for tenant', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->getByTenant($this->tenant, $invoice->id);

        expect($result->id)->toBe($invoice->id);
    });

    it('throws exception for invoice from another tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $invoice = Invoice::factory()
            ->forTenant($otherTenant)
            ->create();

        expect(fn () => $this->service->getByTenant($this->tenant, $invoice->id))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when invoice not found', function () {
        expect(fn () => $this->service->getByTenant($this->tenant, '00000000-0000-0000-0000-000000000000'))
            ->toThrow(ValidationException::class);
    });
});

describe('create', function () {
    it('creates invoice for subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $invoice = $this->service->create($subscription, [
            'description' => 'Test invoice',
        ]);

        expect($invoice)->not->toBeNull();
        expect($invoice->subscription_id)->toBe($subscription->id);
        expect($invoice->tenant_id)->toBe($this->tenant->id);
        expect($invoice->status)->toBe(InvoiceStatus::ISSUED);
    });

    it('calculates tax correctly', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create(['amount' => 1000]);

        $invoice = $this->service->create($subscription, [
            'subtotal' => 1000,
        ]);

        expect((float) $invoice->tax_amount)->toBe(180.0); // 18% GST
        expect((float) $invoice->total)->toBe(1180.0);
    });
});

describe('markAsPaid', function () {
    it('marks invoice as paid', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->issued()
            ->create();

        $result = $this->service->markAsPaid($invoice, 'pay_123');

        expect($result->status)->toBe(InvoiceStatus::PAID);
        expect($result->paid_at)->not->toBeNull();
        expect((float) $result->amount_due)->toBe(0.0);
    });

    it('throws exception when already paid', function () {
        $invoice = Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->create();

        expect(fn () => $this->service->markAsPaid($invoice, 'pay_123'))
            ->toThrow(ValidationException::class);
    });
});

describe('getTotalPaidForTenant', function () {
    it('returns total paid amount', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->create(['total' => 1000]);
        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->create(['total' => 2000]);
        Invoice::factory()
            ->forTenant($this->tenant)
            ->issued()
            ->create(['total' => 500]);

        $total = $this->service->getTotalPaidForTenant($this->tenant);

        expect($total)->toBe(3000.0);
    });

    it('returns zero when no paid invoices', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->issued()
            ->create();

        $total = $this->service->getTotalPaidForTenant($this->tenant);

        expect($total)->toBe(0.0);
    });
});

describe('getCountForTenant', function () {
    it('returns invoice count', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->count(5)
            ->create();

        $count = $this->service->getCountForTenant($this->tenant);

        expect($count)->toBe(5);
    });

    it('returns zero when no invoices', function () {
        $count = $this->service->getCountForTenant($this->tenant);

        expect($count)->toBe(0);
    });
});
