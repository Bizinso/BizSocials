<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Models\WhatsApp\WhatsAppPhoneNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ResetDailySendCountsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $count = WhatsAppPhoneNumber::where('daily_send_count', '>', 0)->count();

        WhatsAppPhoneNumber::where('daily_send_count', '>', 0)
            ->update(['daily_send_count' => 0]);

        Log::info('[ResetDailySendCountsJob] Reset daily send counts', [
            'phones_reset' => $count,
        ]);
    }
}
