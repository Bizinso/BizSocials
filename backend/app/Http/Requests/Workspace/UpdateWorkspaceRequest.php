<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkspaceRequest extends FormRequest
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

        // Check if user can manage this workspace
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        return $membership !== null && $membership->canManageWorkspace();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
