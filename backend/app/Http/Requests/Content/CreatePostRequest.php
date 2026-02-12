<?php

declare(strict_types=1);

namespace App\Http\Requests\Content;

use App\Enums\Content\PostType;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreatePostRequest extends FormRequest
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

        // Check if user has access to create content
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            // If user is not a member, check if they are tenant admin
            return $user->isAdmin();
        }

        return $role->canCreateContent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'content_text' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'content_variations' => ['sometimes', 'nullable', 'array'],
            'post_type' => ['sometimes', Rule::in(PostType::values())],
            'scheduled_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'scheduled_timezone' => ['sometimes', 'nullable', 'string', 'timezone'],
            'hashtags' => ['sometimes', 'nullable', 'array'],
            'hashtags.*' => ['string', 'max:100'],
            'mentions' => ['sometimes', 'nullable', 'array'],
            'mentions.*' => ['string', 'max:100'],
            'link_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'first_comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'social_account_ids' => ['sometimes', 'nullable', 'array'],
            'social_account_ids.*' => ['uuid', 'exists:social_accounts,id'],
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
            'scheduled_at.after' => 'The scheduled time must be in the future.',
            'social_account_ids.*.exists' => 'One or more selected social accounts do not exist.',
        ];
    }
}
