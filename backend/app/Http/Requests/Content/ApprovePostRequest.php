<?php

declare(strict_types=1);

namespace App\Http\Requests\Content;

use App\Enums\Content\PostStatus;
use App\Models\Content\Post;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;

final class ApprovePostRequest extends FormRequest
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

        // Check if post can be approved
        if (!$post->status->canTransitionTo(PostStatus::APPROVED)) {
            return false;
        }

        // Tenant admins can approve any post
        if ($user->isAdmin()) {
            return true;
        }

        // Check workspace role
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            return false;
        }

        // Only admins/owners can approve
        return $role->canApproveContent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'comment' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
