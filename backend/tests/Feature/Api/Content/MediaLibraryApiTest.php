<?php

declare(strict_types=1);

use App\Models\Content\MediaFolder;
use App\Models\Content\MediaLibraryItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['tenant_id' => $this->user->tenant_id]);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'role' => 'admin',
    ]);
    $this->actingAs($this->user);
});

describe('Media Library API', function () {
    describe('POST /api/v1/workspaces/{workspace}/media-library', function () {
        it('uploads a file successfully', function () {
            Storage::fake('public');

            $file = UploadedFile::fake()->image('test-upload.jpg', 1200, 800);

            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library", [
                'file' => $file,
                'alt_text' => 'Test image',
            ]);

            $response->assertCreated()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'workspace_id',
                        'original_name',
                        'mime_type',
                        'file_size',
                        'url',
                        'alt_text',
                        'width',
                        'height',
                    ],
                ])
                ->assertJsonPath('data.original_name', 'test-upload.jpg')
                ->assertJsonPath('data.alt_text', 'Test image');

            $this->assertDatabaseHas('media_library_items', [
                'workspace_id' => $this->workspace->id,
                'original_name' => 'test-upload.jpg',
                'alt_text' => 'Test image',
            ]);
        });

        it('uploads file to a specific folder', function () {
            Storage::fake('public');

            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            $file = UploadedFile::fake()->image('test-upload.jpg');

            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library", [
                'file' => $file,
                'folder_id' => $folder->id,
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.folder_id', $folder->id);
        });

        it('validates file is required', function () {
            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
        });

        it('validates file type', function () {
            Storage::fake('public');

            $file = UploadedFile::fake()->create('document.exe', 1000);

            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library", [
                'file' => $file,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
        });

        it('validates file size limit', function () {
            Storage::fake('public');

            $file = UploadedFile::fake()->create('large-file.jpg', 60000); // 60MB

            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library", [
                'file' => $file,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
        });

        it('requires workspace access', function () {
            $otherWorkspace = Workspace::factory()->create();

            Storage::fake('public');
            $file = UploadedFile::fake()->image('test.jpg');

            $response = $this->postJson("/api/v1/workspaces/{$otherWorkspace->id}/media-library", [
                'file' => $file,
            ]);

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/workspaces/{workspace}/media-library', function () {
        it('lists media items for workspace', function () {
            MediaLibraryItem::factory()->count(5)->create(['workspace_id' => $this->workspace->id]);
            MediaLibraryItem::factory()->count(3)->create(); // Different workspace

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library");

            $response->assertOk()
                ->assertJsonCount(5, 'data');
        });

        it('filters by folder', function () {
            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            MediaLibraryItem::factory()->count(3)->create([
                'workspace_id' => $this->workspace->id,
                'folder_id' => $folder->id,
            ]);
            MediaLibraryItem::factory()->count(2)->create(['workspace_id' => $this->workspace->id]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library?folder_id={$folder->id}");

            $response->assertOk()
                ->assertJsonCount(3, 'data');
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

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library?type=image");

            $response->assertOk()
                ->assertJsonCount(3, 'data');
        });

        it('searches by filename', function () {
            MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'original_name' => 'sunset-beach.jpg',
            ]);
            MediaLibraryItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'original_name' => 'mountain-view.jpg',
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library?search=sunset");

            $response->assertOk()
                ->assertJsonCount(1, 'data');
        });

        it('paginates results', function () {
            MediaLibraryItem::factory()->count(25)->create(['workspace_id' => $this->workspace->id]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library?per_page=10");

            $response->assertOk()
                ->assertJsonCount(10, 'data')
                ->assertJsonPath('meta.total', 25);
        });

        it('requires workspace access', function () {
            $otherWorkspace = Workspace::factory()->create();

            $response = $this->getJson("/api/v1/workspaces/{$otherWorkspace->id}/media-library");

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/workspaces/{workspace}/media-library/{media_library}', function () {
        it('retrieves a single media item', function () {
            $item = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library/{$item->id}");

            $response->assertOk()
                ->assertJsonPath('data.id', $item->id)
                ->assertJsonPath('data.original_name', $item->original_name);
        });

        it('returns 404 for non-existent item', function () {
            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library/non-existent-id");

            $response->assertNotFound();
        });

        it('requires workspace access', function () {
            $otherWorkspace = Workspace::factory()->create();
            $item = MediaLibraryItem::factory()->create(['workspace_id' => $otherWorkspace->id]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library/{$item->id}");

            $response->assertNotFound();
        });
    });

    describe('PUT /api/v1/workspaces/{workspace}/media-library/{media_library}', function () {
        it('updates media item metadata', function () {
            $item = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);

            $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/media-library/{$item->id}", [
                'alt_text' => 'Updated alt text',
                'tags' => ['nature', 'landscape'],
            ]);

            $response->assertOk()
                ->assertJsonPath('data.alt_text', 'Updated alt text')
                ->assertJsonPath('data.tags', ['nature', 'landscape']);

            $this->assertDatabaseHas('media_library_items', [
                'id' => $item->id,
                'alt_text' => 'Updated alt text',
            ]);
        });

        it('requires workspace access', function () {
            $otherWorkspace = Workspace::factory()->create();
            $item = MediaLibraryItem::factory()->create(['workspace_id' => $otherWorkspace->id]);

            $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/media-library/{$item->id}", [
                'alt_text' => 'Updated',
            ]);

            $response->assertNotFound();
        });
    });

    describe('DELETE /api/v1/workspaces/{workspace}/media-library/{media_library}', function () {
        it('deletes a media item', function () {
            Storage::fake('public');

            $item = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);

            $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/media-library/{$item->id}");

            $response->assertOk();

            $this->assertDatabaseMissing('media_library_items', [
                'id' => $item->id,
                'deleted_at' => null,
            ]);
        });

        it('requires workspace access', function () {
            $otherWorkspace = Workspace::factory()->create();
            $item = MediaLibraryItem::factory()->create(['workspace_id' => $otherWorkspace->id]);

            $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/media-library/{$item->id}");

            $response->assertNotFound();
        });
    });

    describe('GET /api/v1/workspaces/{workspace}/media-library-folders', function () {
        it('lists folders for workspace', function () {
            MediaFolder::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);
            MediaFolder::factory()->count(2)->create(); // Different workspace

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library-folders");

            $response->assertOk()
                ->assertJsonCount(3, 'data');
        });
    });

    describe('POST /api/v1/workspaces/{workspace}/media-library-folders', function () {
        it('creates a new folder', function () {
            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library-folders", [
                'name' => 'Test Folder',
                'color' => '#FF0000',
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.name', 'Test Folder')
                ->assertJsonPath('data.color', '#FF0000');

            $this->assertDatabaseHas('media_folders', [
                'workspace_id' => $this->workspace->id,
                'name' => 'Test Folder',
            ]);
        });

        it('validates folder name is required', function () {
            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library-folders", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });
    });

    describe('POST /api/v1/workspaces/{workspace}/media-library/move', function () {
        it('moves items to a folder', function () {
            $folder = MediaFolder::factory()->create(['workspace_id' => $this->workspace->id]);
            $item1 = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);
            $item2 = MediaLibraryItem::factory()->create(['workspace_id' => $this->workspace->id]);

            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library/move", [
                'item_ids' => [$item1->id, $item2->id],
                'folder_id' => $folder->id,
            ]);

            $response->assertOk()
                ->assertJsonPath('data.moved_count', 2);

            expect($item1->fresh()->folder_id)->toBe($folder->id);
            expect($item2->fresh()->folder_id)->toBe($folder->id);
        });

        it('validates item_ids is required', function () {
            $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/media-library/move", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['item_ids']);
        });
    });

    describe('GET /api/v1/workspaces/{workspace}/media-library-stats', function () {
        it('returns usage statistics', function () {
            MediaLibraryItem::factory()->count(3)->create([
                'workspace_id' => $this->workspace->id,
                'mime_type' => 'image/jpeg',
                'file_size' => 1000,
            ]);
            MediaLibraryItem::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'mime_type' => 'video/mp4',
                'file_size' => 5000,
            ]);

            $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/media-library-stats");

            $response->assertOk()
                ->assertJsonPath('data.total_items', 5)
                ->assertJsonPath('data.total_size', 13000)
                ->assertJsonPath('data.by_type.images', 3)
                ->assertJsonPath('data.by_type.videos', 2);
        });

        it('requires workspace access', function () {
            $otherWorkspace = Workspace::factory()->create();

            $response = $this->getJson("/api/v1/workspaces/{$otherWorkspace->id}/media-library-stats");

            $response->assertNotFound();
        });
    });
});
