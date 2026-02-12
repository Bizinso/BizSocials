<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Data\Content\AttachMediaData;
use App\Enums\Content\MediaProcessingStatus;
use App\Jobs\Content\ProcessMediaJob;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class PostMediaService extends BaseService
{
    /**
     * List media for a post.
     *
     * @return Collection<int, PostMedia>
     */
    public function listForPost(Post $post): Collection
    {
        return $post->media()->orderBy('sort_order')->get();
    }

    /**
     * Attach media to a post.
     *
     * @throws ValidationException
     */
    public function attach(Post $post, AttachMediaData $data): PostMedia
    {
        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot attach media to a post that cannot be edited.'],
            ]);
        }

        return $this->transaction(function () use ($post, $data) {
            // Determine sort order if not provided
            $sortOrder = $data->sort_order;
            if ($sortOrder === 0) {
                $maxOrder = $post->media()->max('sort_order') ?? -1;
                $sortOrder = $maxOrder + 1;
            }

            $media = $post->media()->create([
                'type' => $data->media_type,
                'storage_path' => $data->file_path,
                'cdn_url' => $data->file_url,
                'thumbnail_url' => $data->thumbnail_url,
                'file_name' => $data->original_filename ?? basename($data->file_path),
                'file_size' => $data->file_size,
                'mime_type' => $data->mime_type,
                'sort_order' => $sortOrder,
                'metadata' => $data->metadata,
                'processing_status' => MediaProcessingStatus::PENDING,
            ]);

            // Dispatch async processing (resize, thumbnail, compress)
            ProcessMediaJob::dispatch($media);

            $this->log('Media attached to post', [
                'media_id' => $media->id,
                'post_id' => $post->id,
                'type' => $data->media_type->value,
            ]);

            return $media;
        });
    }

    /**
     * Update the order of media items.
     *
     * @param array<string> $mediaOrder Array of media IDs in desired order
     * @throws ValidationException
     */
    public function updateOrder(Post $post, array $mediaOrder): void
    {
        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot reorder media for a post that cannot be edited.'],
            ]);
        }

        $this->transaction(function () use ($post, $mediaOrder) {
            foreach ($mediaOrder as $index => $mediaId) {
                $post->media()
                    ->where('id', $mediaId)
                    ->update(['sort_order' => $index]);
            }

            $this->log('Media order updated', [
                'post_id' => $post->id,
                'new_order' => $mediaOrder,
            ]);
        });
    }

    /**
     * Remove a media item from a post.
     *
     * @throws ValidationException
     */
    public function remove(PostMedia $media): void
    {
        $post = $media->post;

        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot remove media from a post that cannot be edited.'],
            ]);
        }

        $this->transaction(function () use ($media) {
            $mediaId = $media->id;
            $postId = $media->post_id;

            $media->delete();

            $this->log('Media removed from post', [
                'media_id' => $mediaId,
                'post_id' => $postId,
            ]);
        });
    }

    /**
     * Remove all media from a post.
     *
     * @throws ValidationException
     */
    public function removeAll(Post $post): void
    {
        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot remove media from a post that cannot be edited.'],
            ]);
        }

        $this->transaction(function () use ($post) {
            $count = $post->media()->count();
            $post->media()->delete();

            $this->log('All media removed from post', [
                'post_id' => $post->id,
                'count' => $count,
            ]);
        });
    }
}
