<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Enums\WhatsApp\WhatsAppTemplateStatus;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncTemplateStatusesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(WhatsAppTemplateService $templateService): void
    {
        WhatsAppTemplate::where('status', WhatsAppTemplateStatus::PENDING_APPROVAL)
            ->whereNotNull('meta_template_id')
            ->chunk(50, function ($templates) use ($templateService) {
                foreach ($templates as $template) {
                    try {
                        $templateService->syncTemplateStatus($template);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to sync template status', [
                            'template_id' => $template->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
