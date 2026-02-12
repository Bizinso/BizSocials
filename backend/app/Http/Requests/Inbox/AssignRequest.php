<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbox;

use App\Models\Inbox\InboxItem;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;

final class AssignRequest extends FormRequest
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

        /** @var InboxItem|null $inboxItem */
        $inboxItem = $this->route('inboxItem');

        if ($inboxItem === null) {
            return false;
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return false;
        }

        // Check if user has admin permissions (Admin or Owner)
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            return $user->isAdmin();
        }

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
            'user_id' => ['required', 'uuid', 'exists:users,id'],
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
            'user_id.required' => 'User ID is required.',
            'user_id.uuid' => 'User ID must be a valid UUID.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
