<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\Workspace\Permission;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddMemberRequest extends FormRequest
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

        // Get the workspace from route
        $workspace = $this->route('workspace');

        if (!$workspace instanceof Workspace) {
            return false;
        }

        // Check if user can manage members in this workspace
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        return $membership !== null && $membership->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE);
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
            'role' => ['sometimes', 'string', Rule::in(WorkspaceRole::values())],
        ];
    }
}
