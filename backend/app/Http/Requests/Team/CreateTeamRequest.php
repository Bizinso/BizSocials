<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\Workspace\Permission;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Foundation\Http\FormRequest;

final class CreateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        return $membership !== null && $membership->hasPermission(Permission::WORKSPACE_TEAMS_MANAGE);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
