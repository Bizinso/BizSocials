<?php

declare(strict_types=1);

use App\Data\Content\AttachMediaData;
use App\Enums\Content\MediaType;
use App\Enums\Content\MediaProcessingStatus;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostMediaService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Bus::fake();

    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->postMediaService = new PostMediaService();
});

describe('listForPost', function () {
    it('returns media items ordered by sort_order', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 2,
        ]);
        PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 0,
        ]);
        PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 1,
        ]);

        $media = $this->postMediaService->listForPost($post);

        expect($media)->toHaveCount(3);
        expect($media[0]->sort_order)->toBe(0);
        expect($media[1]->sort_order)->toBe(1);
        expect($media[2]->sort_order)->toBe(2);
    });

    it('returns empty collection when no media', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $media = $this->postMediaService->listForPost($post);

        expect($media)->toBeEmpty();
    });
});

describe('attach', function () {
    it('attaches media to a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $data = new AttachMediaData(
            media_type: MediaType::IMAGE,
            file_path: 'media/test-image.jpg',
            file_url: 'https://cdn.example.com/test-image.jpg',
            original_filename: 'test-image.jpg',
            file_size: 1024000,
            mime_type: 'image/jpeg',
        );

        $media = $this->postMediaService->attach($post, $data);

        expect($media)->toBeInstanceOf(PostMedia::class);
        expect($media->post_id)->toBe($post->id);
        expect($media->type)->toBe(MediaType::IMAGE);
        expect($media->storage_path)->toBe('media/test-image.jpg');
        expect($media->cdn_url)->toBe('https://cdn.example.com/test-image.jpg');
        expect($media->processing_status)->toBe(MediaProcessingStatus::PENDING);
    });

    it('auto-assigns sort order to next available', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 0,
        ]);
        PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 1,
        ]);

        $data = new AttachMediaData(
            media_type: MediaType::IMAGE,
            file_path: 'media/new-image.jpg',
        );

        $media = $this->postMediaService->attach($post, $data);

        expect($media->sort_order)->toBe(2);
    });

    it('respects provided sort order', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $data = new AttachMediaData(
            media_type: MediaType::IMAGE,
            file_path: 'media/test-image.jpg',
            sort_order: 5,
        );

        $media = $this->postMediaService->attach($post, $data);

        expect($media->sort_order)->toBe(5);
    });

    it('throws when attaching to non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $data = new AttachMediaData(
            media_type: MediaType::IMAGE,
            file_path: 'media/test-image.jpg',
        );

        $this->postMediaService->attach($post, $data);
    })->throws(ValidationException::class);

    it('attaches video media', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $data = new AttachMediaData(
            media_type: MediaType::VIDEO,
            file_path: 'media/test-video.mp4',
            file_size: 10240000,
            mime_type: 'video/mp4',
        );

        $media = $this->postMediaService->attach($post, $data);

        expect($media->type)->toBe(MediaType::VIDEO);
        expect($media->mime_type)->toBe('video/mp4');
    });
});

describe('updateOrder', function () {
    it('updates media order', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $media1 = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 0,
        ]);
        $media2 = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 1,
        ]);
        $media3 = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'sort_order' => 2,
        ]);

        // Reverse order
        $this->postMediaService->updateOrder($post, [
            $media3->id,
            $media2->id,
            $media1->id,
        ]);

        expect($media1->fresh()->sort_order)->toBe(2);
        expect($media2->fresh()->sort_order)->toBe(1);
        expect($media3->fresh()->sort_order)->toBe(0);
    });

    it('throws when updating order for non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $media = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
        ]);

        $this->postMediaService->updateOrder($post, [$media->id]);
    })->throws(ValidationException::class);
});

describe('remove', function () {
    it('removes media from post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $media = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
        ]);

        $this->postMediaService->remove($media);

        expect(PostMedia::find($media->id))->toBeNull();
    });

    it('throws when removing from non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $media = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
        ]);

        $this->postMediaService->remove($media);
    })->throws(ValidationException::class);
});

describe('removeAll', function () {
    it('removes all media from post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        PostMedia::factory()->image()->count(3)->create([
            'post_id' => $post->id,
        ]);

        expect($post->media()->count())->toBe(3);

        $this->postMediaService->removeAll($post);

        expect($post->media()->count())->toBe(0);
    });

    it('throws when removing from non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        PostMedia::factory()->image()->create([
            'post_id' => $post->id,
        ]);

        $this->postMediaService->removeAll($post);
    })->throws(ValidationException::class);
});
