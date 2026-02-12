<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use App\Enums\WhatsApp\WhatsAppOptInSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateOptInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'string', Rule::in(array_column(WhatsAppOptInSource::cases(), 'value'))],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be in E.164 format (e.g., +919876543210).',
        ];
    }
}
