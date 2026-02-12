<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbox;

use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Http\FormRequest;

final class BulkActionRequest extends FormRequest
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

        // Check if user has access to this workspace
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            return $user->isAdmin();
        }

        // Editor or above can perform bulk actions
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
            'item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'item_ids.*' => ['uuid', 'exists:inbox_items,id'],
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
            'item_ids.required' => 'Item IDs are required.',
            'item_ids.array' => 'Item IDs must be an array.',
            'item_ids.min' => 'At least one item ID is required.',
            'item_ids.max' => 'Cannot process more than 100 items at once.',
            'item_ids.*.uuid' => 'Each item ID must be a valid UUID.',
            'item_ids.*.exists' => 'One or more selected items do not exist.',
        ];
    }
}
