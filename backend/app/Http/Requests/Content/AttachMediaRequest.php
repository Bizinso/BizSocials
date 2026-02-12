<?php

declare(strict_types=1);

namespace App\Http\Requests\Content;

use App\Enums\Content\MediaType;
use App\Models\Content\Post;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AttachMediaRequest extends FormRequest
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

        /** @var Post|null $post */
        $post = $this->route('post');

        if ($workspace === null || $post === null) {
            return false;
        }

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return false;
        }

        // Check if post can be edited
        if (!$post->canEdit()) {
            return false;
        }

        // Tenant admins can edit any post
        if ($user->isAdmin()) {
            return true;
        }

        // Check workspace role
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            return false;
        }

        // Admins/Owners can edit any post
        if ($role->canApproveContent()) {
            return true;
        }

        // Editors can only edit their own posts
        return $post->created_by_user_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'media_type' => ['required', Rule::in(MediaType::values())],
            'file_path' => ['required', 'string', 'max:1000'],
            'file_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'thumbnail_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'original_filename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'file_size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mime_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
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
            'media_type.in' => 'Invalid media type. Supported types: ' . implode(', ', MediaType::values()),
        ];
    }
}
