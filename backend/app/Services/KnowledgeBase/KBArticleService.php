<?php

declare(strict_types=1);

namespace App\Services\KnowledgeBase;

use App\Data\KnowledgeBase\CreateArticleData;
use App\Data\KnowledgeBase\UpdateArticleData;
use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\Platform\SuperAdminUser;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class KBArticleService extends BaseService
{
    /**
     * List published articles for public access.
     *
     * @param array<string, mixed> $filters
     */
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        $query = KBArticle::published()
            ->with(['category', 'tags']);

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->forCategory($filters['category_id']);
        }

        // Filter by article type
        if (!empty($filters['article_type'])) {
            $query->where('article_type', $filters['article_type']);
        }

        // Filter by difficulty level
        if (!empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        // Filter by tag
        if (!empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('kb_tags.id', $filters['tag_id']));
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'published_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Get a published article by slug.
     *
     * @throws ModelNotFoundException
     */
    public function getBySlug(string $slug): KBArticle
    {
        $article = KBArticle::published()
            ->where('slug', $slug)
            ->with(['category', 'tags'])
            ->first();

        if ($article === null) {
            throw new ModelNotFoundException('Article not found.');
        }

        return $article;
    }

    /**
     * Get featured articles.
     */
    public function getFeatured(int $limit = 5): Collection
    {
        return KBArticle::published()
            ->featured()
            ->with(['category'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular articles.
     */
    public function getPopular(int $limit = 10): Collection
    {
        return KBArticle::published()
            ->popular()
            ->with(['category'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get related articles based on category and tags.
     */
    public function getRelated(KBArticle $article, int $limit = 5): Collection
    {
        $tagIds = $article->tags->pluck('id')->toArray();

        return KBArticle::published()
            ->where('id', '!=', $article->id)
            ->where(function ($query) use ($article, $tagIds) {
                $query->where('category_id', $article->category_id);
                if (!empty($tagIds)) {
                    $query->orWhereHas('tags', fn ($q) => $q->whereIn('kb_tags.id', $tagIds));
                }
            })
            ->with(['category'])
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Increment view count for an article.
     */
    public function incrementViewCount(KBArticle $article): void
    {
        $article->incrementViewCount();
    }

    /**
     * List all articles for admin (includes drafts and archived).
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = KBArticle::with(['category', 'tags', 'author']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = KBArticleStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->forCategory($filters['category_id']);
        }

        // Filter by article type
        if (!empty($filters['article_type'])) {
            $query->where('article_type', $filters['article_type']);
        }

        // Filter by difficulty level
        if (!empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        // Search in title and content
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Get a single article by ID for admin.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): KBArticle
    {
        $article = KBArticle::with(['category', 'tags', 'author', 'lastEditedBy'])
            ->find($id);

        if ($article === null) {
            throw new ModelNotFoundException('Article not found.');
        }

        return $article;
    }

    /**
     * Create a new article.
     */
    public function create(SuperAdminUser $author, CreateArticleData $data): KBArticle
    {
        return $this->transaction(function () use ($author, $data) {
            // Validate category exists
            $category = KBCategory::find($data->category_id);
            if ($category === null) {
                throw ValidationException::withMessages([
                    'category_id' => ['Category not found.'],
                ]);
            }

            // Generate slug if not provided
            $slug = $data->slug ?? Str::slug($data->title);

            // Ensure slug is unique
            $slug = $this->ensureUniqueSlug($slug);

            $article = KBArticle::create([
                'category_id' => $data->category_id,
                'title' => $data->title,
                'slug' => $slug,
                'excerpt' => $data->excerpt,
                'content' => $data->content,
                'content_format' => $data->content_format,
                'article_type' => $data->article_type,
                'difficulty_level' => $data->difficulty_level,
                'is_featured' => $data->is_featured,
                'featured_image' => $data->featured_image,
                'meta_title' => $data->meta_title,
                'meta_description' => $data->meta_description,
                'status' => KBArticleStatus::DRAFT,
                'author_id' => $author->id,
                'version' => 1,
                'view_count' => 0,
                'helpful_count' => 0,
                'not_helpful_count' => 0,
                'is_public' => true,
            ]);

            // Sync tags if provided
            if (!empty($data->tag_ids)) {
                $article->syncTags($data->tag_ids);
            }

            $this->log('Article created', [
                'article_id' => $article->id,
                'author_id' => $author->id,
            ]);

            return $article->fresh(['category', 'tags', 'author']);
        });
    }

    /**
     * Update an article.
     */
    public function update(KBArticle $article, SuperAdminUser $editor, UpdateArticleData $data): KBArticle
    {
        return $this->transaction(function () use ($article, $editor, $data) {
            // Create version snapshot before update
            $article->createVersion('Content updated', $editor->id);

            $updateData = [
                'last_edited_by' => $editor->id,
                'version' => $article->version + 1,
            ];

            if ($data->category_id !== null) {
                // Validate category exists
                $category = KBCategory::find($data->category_id);
                if ($category === null) {
                    throw ValidationException::withMessages([
                        'category_id' => ['Category not found.'],
                    ]);
                }
                $updateData['category_id'] = $data->category_id;
            }

            if ($data->title !== null) {
                $updateData['title'] = $data->title;
            }

            if ($data->slug !== null) {
                $slug = Str::slug($data->slug);
                if ($slug !== $article->slug) {
                    $slug = $this->ensureUniqueSlug($slug, $article->id);
                }
                $updateData['slug'] = $slug;
            }

            if ($data->content !== null) {
                $updateData['content'] = $data->content;
            }

            if ($data->excerpt !== null) {
                $updateData['excerpt'] = $data->excerpt;
            }

            if ($data->article_type !== null) {
                $updateData['article_type'] = $data->article_type;
            }

            if ($data->difficulty_level !== null) {
                $updateData['difficulty_level'] = $data->difficulty_level;
            }

            if ($data->is_featured !== null) {
                $updateData['is_featured'] = $data->is_featured;
            }

            if ($data->featured_image !== null) {
                $updateData['featured_image'] = $data->featured_image;
            }

            if ($data->meta_title !== null) {
                $updateData['meta_title'] = $data->meta_title;
            }

            if ($data->meta_description !== null) {
                $updateData['meta_description'] = $data->meta_description;
            }

            $article->update($updateData);

            // Sync tags if provided
            if ($data->tag_ids !== null) {
                $article->syncTags($data->tag_ids);
            }

            $this->log('Article updated', [
                'article_id' => $article->id,
                'editor_id' => $editor->id,
            ]);

            return $article->fresh(['category', 'tags', 'author', 'lastEditedBy']);
        });
    }

    /**
     * Publish an article.
     *
     * @throws ValidationException
     */
    public function publish(KBArticle $article): KBArticle
    {
        if (!$article->status->canTransitionTo(KBArticleStatus::PUBLISHED)) {
            throw ValidationException::withMessages([
                'status' => ['Article cannot be published from its current status.'],
            ]);
        }

        $article->publish();

        $this->log('Article published', [
            'article_id' => $article->id,
        ]);

        return $article->fresh(['category', 'tags', 'author']);
    }

    /**
     * Unpublish an article (set to draft).
     *
     * @throws ValidationException
     */
    public function unpublish(KBArticle $article): KBArticle
    {
        if (!$article->status->canTransitionTo(KBArticleStatus::DRAFT)) {
            throw ValidationException::withMessages([
                'status' => ['Article cannot be unpublished from its current status.'],
            ]);
        }

        $article->unpublish();

        $this->log('Article unpublished', [
            'article_id' => $article->id,
        ]);

        return $article->fresh(['category', 'tags', 'author']);
    }

    /**
     * Archive an article.
     *
     * @throws ValidationException
     */
    public function archive(KBArticle $article): KBArticle
    {
        if (!$article->status->canTransitionTo(KBArticleStatus::ARCHIVED)) {
            throw ValidationException::withMessages([
                'status' => ['Article cannot be archived from its current status.'],
            ]);
        }

        $article->archive();

        $this->log('Article archived', [
            'article_id' => $article->id,
        ]);

        return $article->fresh(['category', 'tags', 'author']);
    }

    /**
     * Delete an article.
     */
    public function delete(KBArticle $article): void
    {
        $this->transaction(function () use ($article) {
            $articleId = $article->id;
            $wasPublished = $article->isPublished();
            $categoryId = $article->category_id;

            $article->delete();

            // Decrement category article count if was published
            if ($wasPublished) {
                $category = KBCategory::find($categoryId);
                $category?->decrementArticleCount();
            }

            $this->log('Article deleted', [
                'article_id' => $articleId,
            ]);
        });
    }

    /**
     * Ensure slug is unique by appending a number if necessary.
     */
    private function ensureUniqueSlug(string $slug, ?string $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = KBArticle::where('slug', $slug);
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
