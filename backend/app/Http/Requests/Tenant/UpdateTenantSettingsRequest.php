<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantSettingsRequest extends FormRequest
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

        // Only admins and owners can update tenant settings
        return $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'string', 'max:100', 'timezone'],
            'language' => ['sometimes', 'string', 'max:10'],
            'notifications' => ['sometimes', 'array'],
            'notifications.email' => ['sometimes', 'boolean'],
            'notifications.in_app' => ['sometimes', 'boolean'],
            'notifications.digest' => ['sometimes', 'string', 'in:instant,daily,weekly,never'],
            'branding' => ['sometimes', 'array'],
            'branding.logo_url' => ['sometimes', 'nullable', 'url'],
            'branding.primary_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'security' => ['sometimes', 'array'],
            'security.require_mfa' => ['sometimes', 'boolean'],
            'security.session_timeout_minutes' => ['sometimes', 'integer', 'min:15', 'max:1440'],
        ];
    }
}
