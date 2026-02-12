<?php

declare(strict_types=1);

namespace App\Services\KnowledgeBase;

use App\Data\KnowledgeBase\CreateCategoryData;
use App\Data\KnowledgeBase\UpdateCategoryData;
use App\Models\KnowledgeBase\KBCategory;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class KBCategoryService extends BaseService
{
    /**
     * List all public categories with article counts.
     */
    public function listWithArticleCount(): Collection
    {
        return KBCategory::published()
            ->ordered()
            ->get();
    }

    /**
     * Get a category by slug with its published articles.
     *
     * @throws ModelNotFoundException
     */
    public function getBySlug(string $slug): KBCategory
    {
        $category = KBCategory::published()
            ->where('slug', $slug)
            ->first();

        if ($category === null) {
            throw new ModelNotFoundException('Category not found.');
        }

        return $category;
    }

    /**
     * Get the full category tree for public view.
     */
    public function getTree(): Collection
    {
        $categories = KBCategory::published()
            ->ordered()
            ->get();

        return $this->buildTree($categories);
    }

    /**
     * List all categories for admin (includes non-public).
     */
    public function list(): Collection
    {
        return KBCategory::ordered()->get();
    }

    /**
     * Get a category by ID for admin.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): KBCategory
    {
        $category = KBCategory::find($id);

        if ($category === null) {
            throw new ModelNotFoundException('Category not found.');
        }

        return $category;
    }

    /**
     * Create a new category.
     */
    public function create(CreateCategoryData $data): KBCategory
    {
        return $this->transaction(function () use ($data) {
            // Validate parent exists if provided
            if ($data->parent_id !== null) {
                $parent = KBCategory::find($data->parent_id);
                if ($parent === null) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['Parent category not found.'],
                    ]);
                }
            }

            // Generate slug if not provided
            $slug = $data->slug ?? Str::slug($data->name);

            // Ensure slug is unique
            $slug = $this->ensureUniqueSlug($slug);

            // Get max sort order for this level
            $maxOrder = KBCategory::where('parent_id', $data->parent_id)
                ->max('sort_order') ?? 0;

            $category = KBCategory::create([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'icon' => $data->icon,
                'color' => $data->color,
                'parent_id' => $data->parent_id,
                'sort_order' => $maxOrder + 1,
                'is_public' => true,
                'article_count' => 0,
            ]);

            $this->log('Category created', [
                'category_id' => $category->id,
            ]);

            return $category;
        });
    }

    /**
     * Update a category.
     */
    public function update(KBCategory $category, UpdateCategoryData $data): KBCategory
    {
        return $this->transaction(function () use ($category, $data) {
            $updateData = [];

            if ($data->name !== null) {
                $updateData['name'] = $data->name;
            }

            if ($data->slug !== null) {
                $slug = Str::slug($data->slug);
                if ($slug !== $category->slug) {
                    $slug = $this->ensureUniqueSlug($slug, $category->id);
                }
                $updateData['slug'] = $slug;
            }

            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }

            if ($data->icon !== null) {
                $updateData['icon'] = $data->icon;
            }

            if ($data->color !== null) {
                $updateData['color'] = $data->color;
            }

            if (!empty($updateData)) {
                $category->update($updateData);
            }

            $this->log('Category updated', [
                'category_id' => $category->id,
            ]);

            return $category->fresh();
        });
    }

    /**
     * Update category order.
     *
     * @param array<array{id: string, sort_order: int}> $order
     */
    public function updateOrder(array $order): void
    {
        $this->transaction(function () use ($order) {
            foreach ($order as $item) {
                KBCategory::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            $this->log('Category order updated', [
                'count' => count($order),
            ]);
        });
    }

    /**
     * Delete a category.
     *
     * @throws ValidationException
     */
    public function delete(KBCategory $category): void
    {
        // Check if category has articles
        if ($category->hasArticles()) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete a category that contains articles.'],
            ]);
        }

        // Check if category has children
        if ($category->hasChildren()) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete a category that has subcategories.'],
            ]);
        }

        $this->transaction(function () use ($category) {
            $categoryId = $category->id;
            $category->delete();

            $this->log('Category deleted', [
                'category_id' => $categoryId,
            ]);
        });
    }

    /**
     * Build a hierarchical tree from flat collection.
     */
    private function buildTree(Collection $categories, ?string $parentId = null): Collection
    {
        $tree = new Collection();

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $category->setRelation('children', $children);
                $tree->push($category);
            }
        }

        return $tree;
    }

    /**
     * Ensure slug is unique by appending a number if necessary.
     */
    private function ensureUniqueSlug(string $slug, ?string $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = KBCategory::where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
}
