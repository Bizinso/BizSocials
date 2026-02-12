<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\User\TenantRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InviteUserRequest extends FormRequest
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

        // Only admins and owners can invite users
        return $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', 'string', Rule::in(TenantRole::values())],
            'workspace_ids' => ['sometimes', 'nullable', 'array'],
            'workspace_ids.*' => ['uuid'],
        ];
    }
}
