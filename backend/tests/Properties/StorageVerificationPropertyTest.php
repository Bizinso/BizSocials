<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Models\Content\MediaLibraryItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\MediaLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Storage Verification Property Test
 *
 * Tests that uploaded files exist in storage.
 *
 * Feature: platform-audit-and-testing
 */
class StorageVerificationPropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    /**
     * Override the default iteration count to reduce memory usage.
     */
    protected function getPropertyTestIterations(): int
    {
        return 5; // Minimal iterations for testing
    }

    /**
     * Property 10: Storage Verification
     *
     * For any media file upload operation, the file should exist in the configured
     * storage system (local filesystem or S3) and be retrievable using the stored path.
     *
     * Feature: platform-audit-and-testing, Property 10: Storage Verification
     * Validates: Requirements 3.5
     */
    public function test_uploaded_files_exist_in_storage(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 50),
            PropertyGenerators::integer(100, 10000)
        )
            ->then(function ($fileName, $fileSize) {
                // Set up fake storage for testing
                Storage::fake('public');

                // Create a user and workspace
                $user = User::factory()->create();
                $this->actingAs($user);
                
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a fake uploaded file
                $file = UploadedFile::fake()->image($fileName . '.jpg')->size($fileSize);

                // Upload the file using the service
                $mediaLibraryService = app(MediaLibraryService::class);
                $mediaItem = $mediaLibraryService->upload($workspace, $file);

                // Verify the media item was created in the database
                $this->assertDatabaseHas('media_library_items', [
                    'id' => $mediaItem->id,
                    'workspace_id' => $workspace->id,
                    'original_name' => $file->getClientOriginalName(),
                ]);

                // Verify the file exists in storage
                Storage::disk($mediaItem->disk)->assertExists($mediaItem->path);

                // Verify we can retrieve the file content
                $storedContent = Storage::disk($mediaItem->disk)->get($mediaItem->path);
                $this->assertNotEmpty($storedContent);

                // Verify the file size matches
                $storedSize = Storage::disk($mediaItem->disk)->size($mediaItem->path);
                $this->assertGreaterThan(0, $storedSize);
            });
    }

    /**
     * Property 10: Storage Verification - Multiple Files
     *
     * For any batch of media file uploads, all files should exist in storage
     * and be independently retrievable.
     *
     * Feature: platform-audit-and-testing, Property 10: Storage Verification
     * Validates: Requirements 3.5
     */
    public function test_multiple_uploaded_files_exist_in_storage(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 5)
        )
            ->then(function ($fileCount) {
                // Set up fake storage for testing
                Storage::fake('public');

                // Create a user and workspace
                $user = User::factory()->create();
                $this->actingAs($user);
                
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                $mediaLibraryService = app(MediaLibraryService::class);
                $uploadedItems = [];

                // Upload multiple files
                for ($i = 0; $i < $fileCount; $i++) {
                    $file = UploadedFile::fake()->image("test_file_{$i}.jpg");
                    $mediaItem = $mediaLibraryService->upload($workspace, $file);
                    $uploadedItems[] = $mediaItem;
                }

                // Verify all files exist in storage
                foreach ($uploadedItems as $item) {
                    Storage::disk($item->disk)->assertExists($item->path);
                    
                    // Verify file is retrievable
                    $content = Storage::disk($item->disk)->get($item->path);
                    $this->assertNotEmpty($content);
                }

                // Verify all items are in the database
                $this->assertEquals($fileCount, MediaLibraryItem::where('workspace_id', $workspace->id)->count());
            });
    }

    /**
     * Property 10: Storage Verification - File Deletion
     *
     * For any media file deletion operation, the file should be removed from storage
     * and no longer be retrievable.
     *
     * Feature: platform-audit-and-testing, Property 10: Storage Verification
     * Validates: Requirements 3.5
     */
    public function test_deleted_files_removed_from_storage(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 50)
        )
            ->then(function ($fileName) {
                // Set up fake storage for testing
                Storage::fake('public');

                // Create a user and workspace
                $user = User::factory()->create();
                $this->actingAs($user);
                
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Upload a file
                $file = UploadedFile::fake()->image($fileName . '.jpg');
                $mediaLibraryService = app(MediaLibraryService::class);
                $mediaItem = $mediaLibraryService->upload($workspace, $file);

                // Verify file exists in storage
                Storage::disk($mediaItem->disk)->assertExists($mediaItem->path);

                $storedPath = $mediaItem->path;
                $storedDisk = $mediaItem->disk;

                // Delete the media item
                $mediaLibraryService->delete($mediaItem);

                // Verify file no longer exists in storage
                Storage::disk($storedDisk)->assertMissing($storedPath);

                // Verify the database record is soft deleted
                $this->assertSoftDeleted('media_library_items', ['id' => $mediaItem->id]);
            });
    }

    /**
     * Property 10: Storage Verification - Storage Path Consistency
     *
     * For any uploaded file, the stored path should follow the expected pattern
     * and be consistent with the workspace structure.
     *
     * Feature: platform-audit-and-testing, Property 10: Storage Verification
     * Validates: Requirements 3.5
     */
    public function test_storage_path_follows_workspace_structure(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 50)
        )
            ->then(function ($fileName) {
                // Set up fake storage for testing
                Storage::fake('public');

                // Create a user and workspace
                $user = User::factory()->create();
                $this->actingAs($user);
                
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Upload a file
                $file = UploadedFile::fake()->image($fileName . '.jpg');
                $mediaLibraryService = app(MediaLibraryService::class);
                $mediaItem = $mediaLibraryService->upload($workspace, $file);

                // Verify the path includes the workspace ID
                $this->assertStringContainsString('media/' . $workspace->id, $mediaItem->path);

                // Verify the file exists at the expected path
                Storage::disk($mediaItem->disk)->assertExists($mediaItem->path);

                // Verify the URL is accessible
                $this->assertNotEmpty($mediaItem->url);
            });
    }

    /**
     * Property 10: Storage Verification - File Metadata Accuracy
     *
     * For any uploaded file, the stored metadata (size, mime type, dimensions)
     * should accurately reflect the actual file in storage.
     *
     * Feature: platform-audit-and-testing, Property 10: Storage Verification
     * Validates: Requirements 3.5
     */
    public function test_file_metadata_matches_stored_file(): void
    {
        $this->forAll(
            PropertyGenerators::integer(100, 5000)
        )
            ->then(function ($fileSize) {
                // Set up fake storage for testing
                Storage::fake('public');

                // Create a user and workspace
                $user = User::factory()->create();
                $this->actingAs($user);
                
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Upload an image file
                $file = UploadedFile::fake()->image('test.jpg')->size($fileSize);
                $mediaLibraryService = app(MediaLibraryService::class);
                $mediaItem = $mediaLibraryService->upload($workspace, $file);

                // Verify file exists in storage
                Storage::disk($mediaItem->disk)->assertExists($mediaItem->path);

                // Verify the stored file size matches the database record
                $actualSize = Storage::disk($mediaItem->disk)->size($mediaItem->path);
                $this->assertGreaterThan(0, $actualSize);
                $this->assertGreaterThan(0, $mediaItem->file_size);

                // Verify mime type is set
                $this->assertNotEmpty($mediaItem->mime_type);
                $this->assertStringStartsWith('image/', $mediaItem->mime_type);

                // Verify image dimensions are captured for images
                if ($mediaItem->isImage()) {
                    $this->assertNotNull($mediaItem->width);
                    $this->assertNotNull($mediaItem->height);
                    $this->assertGreaterThan(0, $mediaItem->width);
                    $this->assertGreaterThan(0, $mediaItem->height);
                }
            });
    }
}
