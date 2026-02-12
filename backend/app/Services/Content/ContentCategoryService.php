<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\ContentCategory;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class ContentCategoryService extends BaseService
{
    /**
     * List all categories for a workspace.
     */
    public function list(string $workspaceId): Collection
    {
        return ContentCategory::forWorkspace($workspaceId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $workspaceId, array $data): ContentCategory
    {
        $slug = Str::slug($data['name']);

        $category = ContentCategory::create([
            'workspace_id' => $workspaceId,
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->log('Content category created', ['category_id' => $category->id]);

        return $category;
    }

    /**
     * Update a category.
     *
     * @param array<string, mixed> $data
     */
    public function update(ContentCategory $category, array $data): ContentCategory
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        $this->log('Content category updated', ['category_id' => $category->id]);

        return $category;
    }

    /**
     * Delete a category.
     */
    public function delete(ContentCategory $category): bool
    {
        $deleted = $category->delete();

        $this->log('Content category deleted', ['category_id' => $category->id]);

        return $deleted;
    }

    /**
     * Reorder categories.
     *
     * @param array<string> $categoryIds
     */
    public function reorder(array $categoryIds): int
    {
        $count = 0;

        foreach ($categoryIds as $index => $categoryId) {
            $updated = ContentCategory::where('id', $categoryId)
                ->update(['sort_order' => $index]);
            $count += $updated;
        }

        $this->log('Content categories reordered', ['count' => $count]);

        return $count;
    }
}
