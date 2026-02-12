<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\WhatsApp\WhatsAppQuickReply;
use App\Models\Workspace\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppQuickReplyController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $replies = WhatsAppQuickReply::forWorkspace($workspace->id)
            ->orderBy('title')
            ->paginate($request->query('per_page', 50));

        return $this->paginated($replies, 'Quick replies retrieved successfully');
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:4096'],
            'shortcut' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $reply = WhatsAppQuickReply::create([
            'workspace_id' => $workspace->id,
            ...$validated,
        ]);

        return $this->created($reply);
    }

    public function update(Request $request, Workspace $workspace, WhatsAppQuickReply $quickReply): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string', 'max:4096'],
            'shortcut' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $quickReply->update($validated);

        return $this->success($quickReply->refresh());
    }

    public function destroy(Workspace $workspace, WhatsAppQuickReply $quickReply): JsonResponse
    {
        $quickReply->delete();

        return $this->noContent();
    }
}
