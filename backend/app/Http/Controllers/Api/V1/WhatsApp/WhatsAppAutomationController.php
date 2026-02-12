<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\WhatsApp\WhatsAppAutomationRule;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppAutomationController extends Controller
{
    public function __construct(
        private readonly WhatsAppAutomationService $automationService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $rules = $this->automationService->listForWorkspace($workspace->id, [
            'per_page' => $request->query('per_page', 15),
        ]);

        return $this->paginated($rules, $rules->items());
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'string'],
            'trigger_conditions' => ['nullable', 'array'],
            'action_type' => ['required', 'string'],
            'action_params' => ['nullable', 'array'],
            'priority' => ['sometimes', 'integer', 'min:0'],
        ]);

        $rule = WhatsAppAutomationRule::create([
            'workspace_id' => $workspace->id,
            ...$validated,
        ]);

        return $this->created($rule);
    }

    public function show(Workspace $workspace, WhatsAppAutomationRule $automationRule): JsonResponse
    {
        return $this->success($automationRule);
    }

    public function update(Request $request, Workspace $workspace, WhatsAppAutomationRule $automationRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'trigger_type' => ['sometimes', 'string'],
            'trigger_conditions' => ['nullable', 'array'],
            'action_type' => ['sometimes', 'string'],
            'action_params' => ['nullable', 'array'],
            'priority' => ['sometimes', 'integer', 'min:0'],
        ]);

        $automationRule->update($validated);

        return $this->success($automationRule->refresh());
    }

    public function destroy(Workspace $workspace, WhatsAppAutomationRule $automationRule): JsonResponse
    {
        $automationRule->delete();

        return $this->noContent();
    }
}
