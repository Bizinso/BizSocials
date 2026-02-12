<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\Post;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class BulkPostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {}

    /**
     * Bulk delete posts.
     */
    public function bulkDelete(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'post_ids' => 'required|array|min:1|max:50',
            'post_ids.*' => 'required|uuid',
        ]);

        $posts = Post::forWorkspace($workspace->id)
            ->whereIn('id', $request->input('post_ids'))
            ->get();

        $deleted = 0;
        $errors = [];

        foreach ($posts as $post) {
            try {
                $this->postService->delete($post);
                $deleted++;
            } catch (\Throwable $e) {
                $errors[] = ['post_id' => $post->id, 'error' => $e->getMessage()];
            }
        }

        return $this->success([
            'deleted' => $deleted,
            'errors' => $errors,
        ], "{$deleted} post(s) deleted");
    }

    /**
     * Bulk submit posts for approval.
     */
    public function bulkSubmit(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'post_ids' => 'required|array|min:1|max:50',
            'post_ids.*' => 'required|uuid',
        ]);

        $posts = Post::forWorkspace($workspace->id)
            ->whereIn('id', $request->input('post_ids'))
            ->get();

        $submitted = 0;
        $errors = [];

        foreach ($posts as $post) {
            try {
                $this->postService->submit($post);
                $submitted++;
            } catch (\Throwable $e) {
                $errors[] = ['post_id' => $post->id, 'error' => $e->getMessage()];
            }
        }

        return $this->success([
            'submitted' => $submitted,
            'errors' => $errors,
        ], "{$submitted} post(s) submitted for approval");
    }

    /**
     * Bulk schedule posts.
     */
    public function bulkSchedule(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'post_ids' => 'required|array|min:1|max:50',
            'post_ids.*' => 'required|uuid',
            'scheduled_at' => 'required|date|after:now',
            'timezone' => ['sometimes', 'nullable', 'string', 'timezone'],
        ]);

        $timezone = $request->input('timezone');
        $scheduledAt = $timezone
            ? Carbon::parse($request->input('scheduled_at'), $timezone)->utc()
            : Carbon::parse($request->input('scheduled_at'));
        $posts = Post::forWorkspace($workspace->id)
            ->whereIn('id', $request->input('post_ids'))
            ->get();

        $scheduled = 0;
        $errors = [];

        foreach ($posts as $post) {
            try {
                $this->postService->schedule($post, $scheduledAt);
                $scheduled++;
            } catch (\Throwable $e) {
                $errors[] = ['post_id' => $post->id, 'error' => $e->getMessage()];
            }
        }

        return $this->success([
            'scheduled' => $scheduled,
            'errors' => $errors,
        ], "{$scheduled} post(s) scheduled");
    }
}
