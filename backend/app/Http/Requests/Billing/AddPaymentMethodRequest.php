<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Enums\Billing\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddPaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // Only owner can manage billing
        return $user->isOwner();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(PaymentMethodType::values())],
            'is_default' => ['sometimes', 'boolean'],
            'card_token' => ['sometimes', 'nullable', 'string'],
            'upi_id' => ['required_if:type,upi', 'nullable', 'string', 'max:255'],
            'card_last4' => ['sometimes', 'nullable', 'string', 'size:4'],
            'card_brand' => ['sometimes', 'nullable', 'string', 'max:50'],
            'card_exp_month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:12'],
            'card_exp_year' => ['sometimes', 'nullable', 'integer', 'min:' . date('Y'), 'max:' . (date('Y') + 20)],
            'bank_name' => ['required_if:type,netbanking,emandate', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select a payment method type.',
            'type.in' => 'The selected payment method type is invalid.',
            'upi_id.required_if' => 'UPI ID is required for UPI payment method.',
            'bank_name.required_if' => 'Bank name is required for net banking and e-mandate.',
        ];
    }
}
