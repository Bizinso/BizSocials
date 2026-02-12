<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Enums\WhatsApp\WhatsAppCampaignStatus;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Models\WhatsApp\WhatsAppCampaign;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendWhatsAppCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600; // 1 hour max

    public function __construct(
        private readonly WhatsAppCampaign $campaign,
    ) {
        $this->onQueue('content');
    }

    public function handle(WhatsAppMessagingService $messagingService): void
    {
        $campaign = $this->campaign->fresh();

        if (! $campaign || $campaign->status === WhatsAppCampaignStatus::CANCELLED) {
            return;
        }

        if ($campaign->status !== WhatsAppCampaignStatus::SENDING) {
            $campaign->update([
                'status' => WhatsAppCampaignStatus::SENDING,
                'started_at' => now(),
            ]);
        }

        $template = $campaign->template;
        $phone = $campaign->phoneNumber;
        $waba = $phone->businessAccount;

        $sentCount = 0;
        $failedCount = 0;

        // Process recipients in batches
        $campaign->recipients()
            ->where('status', WhatsAppMessageStatus::PENDING)
            ->chunk(50, function ($recipients) use ($messagingService, $phone, $template, $waba, &$sentCount, &$failedCount, $campaign) {
                foreach ($recipients as $recipient) {
                    // Check if campaign was cancelled mid-send
                    if ($campaign->fresh()?->status === WhatsAppCampaignStatus::CANCELLED) {
                        return false;
                    }

                    try {
                        $message = $messagingService->sendTemplateMessage(
                            $phone,
                            $recipient->phone_number,
                            $template,
                            $recipient->template_params ?? [],
                        );

                        $recipient->markSent($message->wamid ?? '');
                        $sentCount++;

                        // Update campaign counts periodically
                        if ($sentCount % 50 === 0) {
                            $campaign->update(['sent_count' => $sentCount]);
                        }
                    } catch (\Throwable $e) {
                        $recipient->markFailed('SEND_ERROR', $e->getMessage());
                        $failedCount++;

                        Log::warning('WhatsApp campaign message failed', [
                            'campaign_id' => $campaign->id,
                            'recipient_id' => $recipient->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Rate limiting: ~80 msg/sec → sleep 13ms between messages
                    usleep(13000);
                }

                return true;
            });

        // Finalize campaign
        $campaign->update([
            'status' => WhatsAppCampaignStatus::COMPLETED,
            'completed_at' => now(),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);

        // Increment template usage
        $template->incrementUsage();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp campaign job failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);

        $this->campaign->update([
            'status' => WhatsAppCampaignStatus::FAILED,
            'completed_at' => now(),
        ]);
    }
}
