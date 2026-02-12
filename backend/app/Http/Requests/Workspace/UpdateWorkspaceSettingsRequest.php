<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkspaceSettingsRequest extends FormRequest
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
            'timezone' => ['sometimes', 'string', 'max:100', 'timezone'],
            'date_format' => ['sometimes', 'string', 'max:20'],
            'approval_workflow' => ['sometimes', 'array'],
            'approval_workflow.enabled' => ['sometimes', 'boolean'],
            'approval_workflow.required_for_roles' => ['sometimes', 'array'],
            'approval_workflow.required_for_roles.*' => ['string', 'in:viewer,editor,admin,owner'],
            'content_categories' => ['sometimes', 'array'],
            'content_categories.*' => ['string', 'max:50'],
            'hashtag_groups' => ['sometimes', 'array'],
        ];
    }
}
