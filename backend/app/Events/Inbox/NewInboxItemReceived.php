<?php

declare(strict_types=1);

namespace App\Events\Inbox;

use App\Models\Inbox\InboxItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewInboxItemReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InboxItem $inboxItem,
    ) {}
}
