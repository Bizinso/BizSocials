<?php

declare(strict_types=1);

namespace App\Http\Requests\Social;

use App\Enums\Social\SocialPlatform;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ConnectAccountRequest extends FormRequest
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

        /** @var Workspace|null $workspace */
        $workspace = $this->route('workspace');

        if ($workspace === null) {
            return false;
        }

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Check if user has access to manage social accounts
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            // If user is not a member, check if they are tenant admin
            return $user->isAdmin();
        }

        return $role->canManageSocialAccounts();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', Rule::in(SocialPlatform::values())],
            'platform_account_id' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_username' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile_image_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'access_token' => ['required', 'string'],
            'refresh_token' => ['sometimes', 'nullable', 'string'],
            'token_expires_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
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
            'platform.in' => 'The selected platform is not supported. Supported platforms: ' . implode(', ', SocialPlatform::values()),
            'access_token.required' => 'An access token is required to connect the account.',
        ];
    }
}
