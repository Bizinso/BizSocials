<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\WatermarkPreset;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\ImageEditorService;
use App\Services\Content\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ImageEditorController extends Controller
{
    public function __construct(
        private readonly ImageEditorService $imageEditorService,
        private readonly WatermarkService $watermarkService,
    ) {}

    /**
     * Crop an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/crop
     */
    public function crop(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        $inputPath = $this->resolveInputPath($request);
        $outputPath = $this->imageEditorService->crop(
            $inputPath,
            (int) $validated['x'],
            (int) $validated['y'],
            (int) $validated['width'],
            (int) $validated['height'],
        );

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Image cropped successfully');
    }

    /**
     * Resize an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/resize
     */
    public function resize(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'width' => 'required|integer|min:1|max:10000',
            'height' => 'nullable|integer|min:1|max:10000',
        ]);

        $inputPath = $this->resolveInputPath($request);
        $height = isset($validated['height']) ? (int) $validated['height'] : null;
        $outputPath = $this->imageEditorService->resize($inputPath, (int) $validated['width'], $height);

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Image resized successfully');
    }

    /**
     * Add text to an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/text
     */
    public function addText(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'text' => 'required|string|max:500',
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
            'size' => 'required|integer|min:8|max:200',
            'color' => 'required|string|max:20',
        ]);

        $inputPath = $this->resolveInputPath($request);
        $outputPath = $this->imageEditorService->addText(
            $inputPath,
            $validated['text'],
            (int) $validated['x'],
            (int) $validated['y'],
            (int) $validated['size'],
            $validated['color'],
        );

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Text added to image successfully');
    }

    /**
     * Rotate an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/rotate
     */
    public function rotate(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'angle' => 'required|numeric|min:-360|max:360',
        ]);

        $inputPath = $this->resolveInputPath($request);
        $outputPath = $this->imageEditorService->rotate($inputPath, (float) $validated['angle']);

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Image rotated successfully');
    }

    /**
     * Flip an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/flip
     */
    public function flip(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'direction' => 'required|in:h,v',
        ]);

        $inputPath = $this->resolveInputPath($request);
        $outputPath = $this->imageEditorService->flip($inputPath, $validated['direction']);

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Image flipped successfully');
    }

    /**
     * Apply a filter to an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/filter
     */
    public function filter(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'filter' => 'required|in:grayscale,sepia,blur,sharpen,brightness,contrast',
        ]);

        $inputPath = $this->resolveInputPath($request);
        $outputPath = $this->imageEditorService->applyFilter($inputPath, $validated['filter']);

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Filter applied successfully');
    }

    /**
     * Apply a watermark preset to an image.
     * POST /api/v1/workspaces/{workspace}/image-editor/watermark
     */
    public function watermark(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'file' => 'required_without:file_path|file|image|max:10240',
            'file_path' => 'required_without:file|string|max:500',
            'preset_id' => 'required|uuid|exists:watermark_presets,id',
        ]);

        $preset = WatermarkPreset::findOrFail($validated['preset_id']);

        if ($preset->workspace_id !== $workspace->id) {
            return $this->notFound('Watermark preset not found');
        }

        $inputPath = $this->resolveInputPath($request);
        $outputPath = $this->watermarkService->apply($inputPath, $preset);

        $storedPath = $this->storeResult($outputPath, $workspace->id);

        return $this->success(['path' => $storedPath], 'Watermark applied successfully');
    }

    /**
     * List watermark presets for the workspace.
     * GET /api/v1/workspaces/{workspace}/watermark-presets
     */
    public function watermarkPresets(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $presets = WatermarkPreset::forWorkspace($workspace->id)
            ->orderBy('name')
            ->get();

        return $this->success($presets, 'Watermark presets retrieved successfully');
    }

    /**
     * Create a new watermark preset.
     * POST /api/v1/workspaces/{workspace}/watermark-presets
     */
    public function createPreset(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|in:image,text',
            'image' => 'required_if:type,image|file|image|max:5120',
            'text' => 'required_if:type,text|nullable|string|max:500',
            'position' => 'sometimes|string|in:top-left,top-center,top-right,center-left,center,center-right,bottom-left,bottom-center,bottom-right',
            'opacity' => 'sometimes|integer|min:0|max:100',
            'scale' => 'sometimes|integer|min:1|max:100',
            'is_default' => 'sometimes|boolean',
        ]);

        $imagePath = null;
        if ($validated['type'] === 'image' && $request->hasFile('image')) {
            $imagePath = $request->file('image')->store(
                "workspaces/{$workspace->id}/watermarks",
                'public'
            );
        }

        // If setting as default, unset other defaults
        if (! empty($validated['is_default'])) {
            WatermarkPreset::forWorkspace($workspace->id)->update(['is_default' => false]);
        }

        $preset = WatermarkPreset::create([
            'workspace_id' => $workspace->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'image_path' => $imagePath,
            'text' => $validated['text'] ?? null,
            'position' => $validated['position'] ?? 'bottom-right',
            'opacity' => $validated['opacity'] ?? 50,
            'scale' => $validated['scale'] ?? 20,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return $this->created($preset, 'Watermark preset created successfully');
    }

    /**
     * Delete a watermark preset.
     * DELETE /api/v1/workspaces/{workspace}/watermark-presets/{preset}
     */
    public function deletePreset(Request $request, Workspace $workspace, WatermarkPreset $preset): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($preset->workspace_id !== $workspace->id) {
            return $this->notFound('Watermark preset not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        // Delete associated image file if present
        if ($preset->image_path !== null && Storage::disk('public')->exists($preset->image_path)) {
            Storage::disk('public')->delete($preset->image_path);
        }

        $preset->delete();

        return $this->noContent();
    }

    /**
     * Resolve the input image path from either an uploaded file or a file_path string.
     */
    private function resolveInputPath(Request $request): string
    {
        if ($request->hasFile('file')) {
            return $request->file('file')->getRealPath();
        }

        $filePath = $request->input('file_path');

        // If it's a storage path, resolve to full disk path
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->path($filePath);
        }

        return $filePath;
    }

    /**
     * Store a processed image result and return the storage path.
     */
    private function storeResult(string $tempPath, string $workspaceId): string
    {
        $extension = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'png';
        $storagePath = "workspaces/{$workspaceId}/edited/" . uniqid('edit_', true) . '.' . $extension;

        Storage::disk('public')->put($storagePath, file_get_contents($tempPath));

        // Clean up temp file
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        return $storagePath;
    }
}
