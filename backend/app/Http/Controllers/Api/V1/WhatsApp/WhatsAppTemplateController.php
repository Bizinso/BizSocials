<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppTemplateData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\WhatsApp\CreateTemplateRequest;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppTemplateController extends Controller
{
    public function __construct(
        private readonly WhatsAppTemplateService $templateService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $templates = $this->templateService->listForWorkspace($workspace->id, [
            'status' => $request->query('status'),
            'category' => $request->query('category'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ]);

        return $this->paginated($templates, 'Templates retrieved successfully');
    }

    public function store(CreateTemplateRequest $request, Workspace $workspace): JsonResponse
    {
        $template = $this->templateService->create($workspace, $request->validated());

        return $this->created(WhatsAppTemplateData::fromModel($template));
    }

    public function show(Workspace $workspace, WhatsAppTemplate $template): JsonResponse
    {
        return $this->success(WhatsAppTemplateData::fromModel($template->load('phoneNumber')));
    }

    public function update(CreateTemplateRequest $request, Workspace $workspace, WhatsAppTemplate $template): JsonResponse
    {
        $updated = $this->templateService->update($template, $request->validated());

        return $this->success(WhatsAppTemplateData::fromModel($updated));
    }

    public function destroy(Workspace $workspace, WhatsAppTemplate $template): JsonResponse
    {
        $template->delete();

        return $this->noContent();
    }

    public function submit(Workspace $workspace, WhatsAppTemplate $template): JsonResponse
    {
        $this->templateService->submitForApproval($template);

        return $this->success(WhatsAppTemplateData::fromModel($template->refresh()));
    }

    public function sync(Workspace $workspace, WhatsAppTemplate $template): JsonResponse
    {
        $this->templateService->syncTemplateStatus($template);

        return $this->success(WhatsAppTemplateData::fromModel($template->refresh()));
    }

    /**
     * Sync all templates from WhatsApp Business API for a workspace.
     */
    public function syncAll(Request $request, Workspace $workspace): JsonResponse
    {
        $wabaId = $request->input('waba_id');
        
        if (!$wabaId) {
            return $this->error('WhatsApp Business Account ID is required', 422);
        }

        $waba = $workspace->whatsappBusinessAccounts()
            ->where('id', $wabaId)
            ->firstOrFail();

        $stats = $this->templateService->syncTemplatesFromApi($waba);

        return $this->success([
            'message' => 'Templates synced successfully',
            'stats' => $stats,
        ]);
    }

    /**
     * Send a template message.
     */
    public function send(Request $request, Workspace $workspace, WhatsAppTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'recipient_phone' => 'required|string',
            'parameters' => 'array',
        ]);

        try {
            $message = $this->templateService->sendTemplate(
                $template,
                $validated['recipient_phone'],
                $validated['parameters'] ?? [],
                $request->user()?->id
            );

            return $this->success([
                'message' => 'Template message sent successfully',
                'message_id' => $message->id,
                'wamid' => $message->wamid,
            ]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
