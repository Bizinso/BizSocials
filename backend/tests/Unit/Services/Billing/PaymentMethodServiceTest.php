<?php

declare(strict_types=1);

use App\Data\Billing\AddPaymentMethodData;
use App\Enums\Billing\PaymentMethodType;
use App\Enums\User\TenantRole;
use App\Models\Billing\PaymentMethod;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Billing\PaymentMethodService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new PaymentMethodService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('listForTenant', function () {
    it('returns empty collection when no payment methods', function () {
        $result = $this->service->listForTenant($this->tenant);

        expect($result)->toBeEmpty();
    });

    it('returns payment methods for tenant', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->count(3)
            ->create();

        $result = $this->service->listForTenant($this->tenant);

        expect($result)->toHaveCount(3);
    });

    it('does not include methods from other tenants', function () {
        $otherTenant = Tenant::factory()->create();
        PaymentMethod::factory()
            ->forTenant($otherTenant)
            ->count(2)
            ->create();
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->listForTenant($this->tenant);

        expect($result)->toHaveCount(1);
    });

    it('returns default method first', function () {
        $regular = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();
        $default = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        $result = $this->service->listForTenant($this->tenant);

        expect($result->first()->id)->toBe($default->id);
    });
});

describe('get', function () {
    it('returns payment method by id', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->get($method->id);

        expect($result->id)->toBe($method->id);
    });

    it('throws exception when not found', function () {
        expect(fn () => $this->service->get('00000000-0000-0000-0000-000000000000'))
            ->toThrow(ValidationException::class);
    });
});

describe('getByTenant', function () {
    it('returns payment method for tenant', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->getByTenant($this->tenant, $method->id);

        expect($result->id)->toBe($method->id);
    });

    it('throws exception for method from another tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $method = PaymentMethod::factory()
            ->forTenant($otherTenant)
            ->create();

        expect(fn () => $this->service->getByTenant($this->tenant, $method->id))
            ->toThrow(ValidationException::class);
    });
});

describe('getDefaultForTenant', function () {
    it('returns default payment method', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();
        $default = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        $result = $this->service->getDefaultForTenant($this->tenant);

        expect($result->id)->toBe($default->id);
    });

    it('returns null when no default', function () {
        $result = $this->service->getDefaultForTenant($this->tenant);

        expect($result)->toBeNull();
    });
});

describe('add', function () {
    it('adds card payment method', function () {
        $data = new AddPaymentMethodData(
            type: PaymentMethodType::CARD,
            card_last4: '4242',
            card_brand: 'Visa',
            card_exp_month: 12,
            card_exp_year: 2028,
        );

        $method = $this->service->add($this->tenant, $data);

        expect($method->type)->toBe(PaymentMethodType::CARD);
        expect($method->details['last4'])->toBe('4242');
        expect($method->details['brand'])->toBe('Visa');
    });

    it('adds UPI payment method', function () {
        $data = new AddPaymentMethodData(
            type: PaymentMethodType::UPI,
            upi_id: 'user@paytm',
        );

        $method = $this->service->add($this->tenant, $data);

        expect($method->type)->toBe(PaymentMethodType::UPI);
        expect($method->details['vpa'])->toBe('user@paytm');
    });

    it('adds netbanking payment method', function () {
        $data = new AddPaymentMethodData(
            type: PaymentMethodType::NETBANKING,
            bank_name: 'HDFC',
        );

        $method = $this->service->add($this->tenant, $data);

        expect($method->type)->toBe(PaymentMethodType::NETBANKING);
        expect($method->details['bank'])->toBe('HDFC');
    });

    it('sets first method as default', function () {
        $data = new AddPaymentMethodData(
            type: PaymentMethodType::CARD,
        );

        $method = $this->service->add($this->tenant, $data);

        expect($method->is_default)->toBeTrue();
    });

    it('sets as default when requested', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        $data = new AddPaymentMethodData(
            type: PaymentMethodType::CARD,
            is_default: true,
        );

        $method = $this->service->add($this->tenant, $data);

        expect($method->is_default)->toBeTrue();

        // Should only have one default
        $defaults = PaymentMethod::forTenant($this->tenant->id)->where('is_default', true)->count();
        expect($defaults)->toBe(1);
    });

    it('does not set as default when not requested', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        $data = new AddPaymentMethodData(
            type: PaymentMethodType::CARD,
            is_default: false,
        );

        $method = $this->service->add($this->tenant, $data);

        expect($method->is_default)->toBeFalse();
    });
});

describe('setDefault', function () {
    it('sets payment method as default', function () {
        $existing = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->setDefault($method);

        expect($result->is_default)->toBeTrue();

        $existing->refresh();
        expect($existing->is_default)->toBeFalse();
    });
});

describe('remove', function () {
    it('removes payment method', function () {
        PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        $this->service->remove($method);

        expect(PaymentMethod::find($method->id))->toBeNull();
    });

    it('sets another as default when removing default', function () {
        $default = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();
        $other = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->create();

        $this->service->remove($default);

        $other->refresh();
        expect($other->is_default)->toBeTrue();
    });

    it('throws exception when removing only payment method', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        expect(fn () => $this->service->remove($method))
            ->toThrow(ValidationException::class);
    });
});
