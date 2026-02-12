<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbox;

use App\Enums\Inbox\InboxItemType;
use App\Models\Inbox\InboxItem;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;

final class CreateReplyRequest extends FormRequest
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

        // Only replyable item types (COMMENT, DM, WHATSAPP_MESSAGE) can be replied to
        if (!$inboxItem->item_type->canReply()) {
            return false;
        }

        // Check if user has access to create content (Editor or above)
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
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
            'content_text' => ['required', 'string', 'max:1000'],
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
            'content_text.required' => 'Reply content is required.',
            'content_text.max' => 'Reply content cannot exceed 1000 characters.',
        ];
    }
}
