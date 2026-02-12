<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateIntegrationRequest extends FormRequest
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
            'app_id' => ['sometimes', 'required', 'string', 'max:255'],
            'app_secret' => ['sometimes', 'required', 'string', 'max:1000'],
            'api_version' => ['sometimes', 'required', 'string', 'regex:/^v\d+\.\d+$/'],
            'scopes' => ['sometimes', 'required', 'array'],
            'scopes.*' => ['array'],
            'scopes.*.*' => ['string'],
            'redirect_uris' => ['sometimes', 'array'],
            'redirect_uris.*' => ['string', 'url'],
        ];
    }
}
