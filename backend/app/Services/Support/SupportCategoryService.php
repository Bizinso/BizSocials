<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Data\Support\CreateCategoryData;
use App\Data\Support\UpdateCategoryData;
use App\Models\Support\SupportCategory;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupportCategoryService extends BaseService
{
    /**
     * List active categories (for user-facing forms).
     *
     * @return Collection<int, SupportCategory>
     */
    public function listActive(): Collection
    {
        return SupportCategory::active()
            ->ordered()
            ->get();
    }

    /**
     * List all categories (admin).
     *
     * @return Collection<int, SupportCategory>
     */
    public function list(): Collection
    {
        return SupportCategory::ordered()
            ->get();
    }

    /**
     * Get a category by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): SupportCategory
    {
        $category = SupportCategory::find($id);

        if ($category === null) {
            throw new ModelNotFoundException('Support category not found.');
        }

        return $category;
    }

    /**
     * Create a new category.
     *
     * @throws ValidationException
     */
    public function create(CreateCategoryData $data): SupportCategory
    {
        return $this->transaction(function () use ($data) {
            // Validate parent if provided
            if ($data->parent_id !== null) {
                $parent = SupportCategory::find($data->parent_id);
                if ($parent === null) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['Parent category not found.'],
                    ]);
                }
            }

            // Generate slug from name
            $slug = $this->ensureUniqueSlug(Str::slug($data->name));

            $category = SupportCategory::create([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'color' => $data->color ?? '#6B7280',
                'icon' => $data->icon,
                'parent_id' => $data->parent_id,
                'sort_order' => $data->sort_order,
                'is_active' => $data->is_active,
                'ticket_count' => 0,
            ]);

            $this->log('Support category created', [
                'category_id' => $category->id,
            ]);

            return $category;
        });
    }

    /**
     * Update a category.
     *
     * @throws ValidationException
     */
    public function update(SupportCategory $category, UpdateCategoryData $data): SupportCategory
    {
        return $this->transaction(function () use ($category, $data) {
            $updateData = [];

            if ($data->name !== null) {
                $updateData['name'] = $data->name;
                $updateData['slug'] = $this->ensureUniqueSlug(Str::slug($data->name), $category->id);
            }

            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }

            if ($data->color !== null) {
                $updateData['color'] = $data->color;
            }

            if ($data->icon !== null) {
                $updateData['icon'] = $data->icon;
            }

            if ($data->parent_id !== null) {
                // Prevent circular reference
                if ($data->parent_id === $category->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['A category cannot be its own parent.'],
                    ]);
                }

                $parent = SupportCategory::find($data->parent_id);
                if ($parent === null) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['Parent category not found.'],
                    ]);
                }
                $updateData['parent_id'] = $data->parent_id;
            }

            if ($data->sort_order !== null) {
                $updateData['sort_order'] = $data->sort_order;
            }

            if ($data->is_active !== null) {
                $updateData['is_active'] = $data->is_active;
            }

            if (!empty($updateData)) {
                $category->update($updateData);
            }

            $this->log('Support category updated', [
                'category_id' => $category->id,
            ]);

            return $category->fresh();
        });
    }

    /**
     * Delete a category.
     *
     * @throws ValidationException
     */
    public function delete(SupportCategory $category): void
    {
        // Check if category has tickets
        if ($category->ticket_count > 0) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete a category that has tickets assigned.'],
            ]);
        }

        // Check if category has children
        if ($category->hasChildren()) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete a category that has child categories.'],
            ]);
        }

        $categoryId = $category->id;
        $category->delete();

        $this->log('Support category deleted', [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Ensure slug is unique by appending a number if necessary.
     */
    private function ensureUniqueSlug(string $slug, ?string $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = SupportCategory::where('slug', $slug);
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
