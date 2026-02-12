<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Workspace\Workspace;
use App\Services\AI\AIAssistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AIAssistController extends Controller
{
    public function __construct(
        private readonly AIAssistService $aiAssistService,
    ) {}

    /**
     * Generate a caption for a social media post.
     */
    public function generateCaption(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'platform' => 'required|string|max:50',
            'tone' => 'nullable|string|max:50',
        ]);

        try {
            $caption = $this->aiAssistService->generateCaption(
                topic: $validated['topic'],
                platform: $validated['platform'],
                tone: $validated['tone'] ?? null,
            );

            return $this->success(['caption' => $caption]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }
    }

    /**
     * Suggest hashtags for content.
     */
    public function suggestHashtags(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'platform' => 'required|string|max:50',
            'count' => 'nullable|integer|min:1|max:30',
        ]);

        try {
            $hashtags = $this->aiAssistService->suggestHashtags(
                content: $validated['content'],
                platform: $validated['platform'],
                count: (int) ($validated['count'] ?? 10),
            );

            return $this->success(['hashtags' => $hashtags]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }
    }

    /**
     * Improve existing content.
     */
    public function improveContent(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'instruction' => 'required|string|max:500',
        ]);

        try {
            $improved = $this->aiAssistService->improveContent(
                content: $validated['content'],
                instruction: $validated['instruction'],
            );

            return $this->success(['content' => $improved]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }
    }

    /**
     * Generate post ideas.
     */
    public function generateIdeas(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'platform' => 'required|string|max:50',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $ideas = $this->aiAssistService->generatePostIdeas(
                topic: $validated['topic'],
                platform: $validated['platform'],
                count: (int) ($validated['count'] ?? 5),
            );

            return $this->success(['ideas' => $ideas]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }
    }

    /**
     * Suggest replies for an engagement message.
     */
    public function suggestReply(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'platform' => 'required|string|max:50',
        ]);

        try {
            $result = $this->aiAssistService->suggestReply(
                content: $validated['content'],
                platform: $validated['platform'],
            );

            return $this->success($result);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }
    }

    /**
     * Analyze engagement metrics for content.
     */
    public function analyzeEngagement(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'metrics' => 'required|array',
        ]);

        try {
            $result = $this->aiAssistService->analyzeEngagement(
                content: $validated['content'],
                metrics: $validated['metrics'],
            );

            return $this->success($result);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }
    }
}
