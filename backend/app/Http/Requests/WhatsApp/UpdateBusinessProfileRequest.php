<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBusinessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:512'],
            'address' => ['sometimes', 'nullable', 'string', 'max:256'],
            'website' => ['sometimes', 'nullable', 'url', 'max:256'],
            'support_email' => ['sometimes', 'nullable', 'email', 'max:128'],
        ];
    }
}
