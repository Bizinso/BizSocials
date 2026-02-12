<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ForceReauthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:facebook,instagram'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'notify_tenants' => ['sometimes', 'boolean'],
        ];
    }
}
