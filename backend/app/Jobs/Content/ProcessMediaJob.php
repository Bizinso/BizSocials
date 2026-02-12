<?php

declare(strict_types=1);

namespace App\Jobs\Content;

use App\Models\Content\PostMedia;
use App\Services\Content\MediaProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly PostMedia $media,
    ) {
        $this->queue = 'media';
    }

    public function handle(MediaProcessingService $service): void
    {
        $this->media->markProcessing();

        try {
            $service->process($this->media);

            Log::info('Media processed successfully', [
                'media_id' => $this->media->id,
                'post_id' => $this->media->post_id,
                'type' => $this->media->type->value,
            ]);
        } catch (\Throwable $e) {
            $this->media->markFailed();

            Log::error('Media processing failed', [
                'media_id' => $this->media->id,
                'post_id' => $this->media->post_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
