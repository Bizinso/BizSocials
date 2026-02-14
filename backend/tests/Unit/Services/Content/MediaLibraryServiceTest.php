<?php

declare(strict_types=1);

use App\Models\Content\MediaFolder;
use App\Models\Content\MediaLibraryItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\MediaLibraryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(MediaLibraryService::class);
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->workspace->tenant_id]);
    $this->actingAs($this->user);
});

describe('MediaLibraryService', function () {
    describe('upload', function () {
        it('uploads a file and stores metadata', function () {
            Storage::fake('public');

            $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

            $item = $this->service->upload($this->workspace, $file, null, 'Test alt text');

            expect($item)->toBeInstanceOf(MediaLibraryItem::class);
            expect($item->workspace_id)->toBe($this->workspace->id);
            expect($item->uploaded_by_user_id)->toBe($this->user->id);
            expect($item->original_name)->toBe('test-image.jpg');
            expect($item->mime_type)->toBe('image/jpeg');
            expect($item->alt_text)->toBe('Test alt text');
            expect($item->file_size)->toBeGreaterThan(0);
            expect($item->path)->toContain('media/');
        });

        it('stores image dimensions for image files', function () {
            Storage::fake('public');

            $file = UploadedFile::fake()->image('test-image.jpg', 1200, 800);

            $item = $this->service->upload($this->workspace, $file);

            expect($item->width)->toBe(1200);
            expect($item->height)->toBe(800);
        });

        it('uploads file to a specific folder', function () {
            Storage::fake('public');

            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            $file = UploadedFile::fake()->image('test-image.jpg');

            $item = $this->service->upload($this->workspace, $file, $folder->id);

            expect($item->folder_id)->toBe($folder->id);
        });
    });

    describe('createFolder', function () {
        it('creates a new folder', function () {
            $folder = $this->service->createFolder($this->workspace, 'Test Folder', null, '#FF0000');

            expect($folder)->toBeInstanceOf(MediaFolder::class);
            expect($folder->workspace_id)->toBe($this->workspace->id);
            expect($folder->name)->toBe('Test Folder');
            expect($folder->slug)->toBe('test-folder');
            expect($folder->color)->toBe('#FF0000');
        });

        it('creates a subfolder with parent', function () {
            $parent = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);

            $folder = $this->service->createFolder($this->workspace, 'Subfolder', $parent->id);

            expect($folder->parent_id)->toBe($parent->id);
        });
    });

    describe('moveItems', function () {
        it('moves items to a target folder', function () {
            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            $item1 = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);
            $item2 = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);

            $count = $this->service->moveItems([$item1->id, $item2->id], $folder->id);

            expect($count)->toBe(2);
            expect($item1->fresh()->folder_id)->toBe($folder->id);
            expect($item2->fresh()->folder_id)->toBe($folder->id);
        });

        it('moves items to root when folder is null', function () {
            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            $item = MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'folder_id' => $folder->id,
            ]);

            $count = $this->service->moveItems([$item->id], null);

            expect($count)->toBe(1);
            expect($item->fresh()->folder_id)->toBeNull();
        });
    });

    describe('tagItems', function () {
        it('adds tags to items', function () {
            $item1 = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);
            $item2 = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);

            $count = $this->service->tagItems([$item1->id, $item2->id], ['nature', 'landscape']);

            expect($count)->toBe(2);
            expect($item1->fresh()->tags)->toContain('nature', 'landscape');
            expect($item2->fresh()->tags)->toContain('nature', 'landscape');
        });

        it('merges tags with existing tags', function () {
            $item = MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'tags' => ['existing'],
            ]);

            $this->service->tagItems([$item->id], ['new']);

            expect($item->fresh()->tags)->toContain('existing', 'new');
        });
    });

    describe('search', function () {
        it('returns all items for workspace', function () {
            MediaLibraryItem::factory()->count(5)->create(['workspace_id' => $this->workspace->id]);
            MediaLibraryItem::factory()->count(3)->create(); // Different workspace

            $results = $this->service->search($this->workspace->id);

            expect($results->total())->toBe(5);
        });

        it('filters by folder', function () {
            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            MediaLibraryItem::factory()->count(3)->create([
                'workspace_id' => $this->workspace->id,
                'folder_id' => $folder->id,
            ]);
            MediaLibraryItem::factory()->count(2)->create(['workspace_id' => $this->workspace->id]);

            $results = $this->service->search($this->workspace->id, ['folder_id' => $folder->id]);

            expect($results->total())->toBe(3);
        });

        it('filters by type', function () {
            MediaLibraryItem::factory()->count(3)->create([
                'workspace_id' => $this->workspace->id,
                'mime_type' => 'image/jpeg',
            ]);
            MediaLibraryItem::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'mime_type' => 'video/mp4',
            ]);

            $results = $this->service->search($this->workspace->id, ['type' => 'image']);

            expect($results->total())->toBe(3);
        });

        it('searches by filename and alt text', function () {
            MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'original_name' => 'sunset-beach.jpg',
            ]);
            MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'alt_text' => 'Beautiful sunset',
            ]);
            MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'original_name' => 'mountain.jpg',
            ]);

            $results = $this->service->search($this->workspace->id, ['search' => 'sunset']);

            expect($results->total())->toBe(2);
        });
    });

    describe('delete', function () {
        it('deletes item and removes file from storage', function () {
            Storage::fake('public');

            $file = UploadedFile::fake()->image('test-image.jpg');
            $item = $this->service->upload($this->workspace, $file);

            $itemId = $item->id;
            $deleted = $this->service->delete($item);

            expect($deleted)->toBeTrue();
            expect(MediaLibraryItem::find($itemId))->toBeNull();
        });
    });

    describe('trackUsage', function () {
        it('increments usage count', function () {
            $item = MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'usage_count' => 5,
            ]);

            $this->service->trackUsage($item);

            expect($item->fresh()->usage_count)->toBe(6);
            expect($item->fresh()->last_used_at)->not->toBeNull();
        });
    });

    describe('getUsageStats', function () {
        it('returns usage statistics', function () {
            MediaLibraryItem::factory()->count(3)->create([
                'workspace_id' => $this->workspace->id,
                'mime_type' => 'image/jpeg',
                'file_size' => 1000,
                'usage_count' => 10,
            ]);
            MediaLibraryItem::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'mime_type' => 'video/mp4',
                'file_size' => 5000,
                'usage_count' => 5,
            ]);

            $stats = $this->service->getUsageStats($this->workspace->id);

            expect($stats['total_items'])->toBe(5);
            expect($stats['total_size'])->toBe(13000);
            expect($stats['total_usage'])->toBe(40);
            expect($stats['by_type']['images'])->toBe(3);
            expect($stats['by_type']['videos'])->toBe(2);
        });

        it('returns most used items', function () {
            $mostUsed = MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'usage_count' => 100,
            ]);
            MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'usage_count' => 10,
            ]);

            $stats = $this->service->getUsageStats($this->workspace->id);

            expect($stats['most_used'])->toHaveCount(2);
            expect($stats['most_used'][0]->id)->toBe($mostUsed->id);
        });
    });
});
