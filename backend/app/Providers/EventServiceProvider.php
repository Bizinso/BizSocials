<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Billing\PaymentFailed;
use App\Events\Billing\SubscriptionCreated;
use App\Events\Billing\TrialEnding;
use App\Events\Content\PostApproved;
use App\Events\Content\PostFailed;
use App\Events\Content\PostPublished;
use App\Events\Content\PostRejected;
use App\Events\Content\PostSubmittedForApproval;
use App\Events\Inbox\NewInboxItemReceived;
use App\Events\Social\AccountConnected;
use App\Events\Social\TokenExpiring;
use App\Events\Tenant\TenantCreated;
use App\Events\Tenant\UserInvited;
use App\Events\Workspace\MemberAdded;
use App\Events\Workspace\MemberRemoved;
use App\Listeners\Billing\NotifyPaymentFailed;
use App\Listeners\Billing\NotifySubscriptionCreated;
use App\Listeners\Billing\NotifyTrialEnding;
use App\Listeners\Content\NotifyApprovalNeeded;
use App\Listeners\Content\NotifyPostApproved;
use App\Listeners\Content\NotifyPostFailed;
use App\Listeners\Content\NotifyPostPublished;
use App\Listeners\Content\NotifyPostRejected;
use App\Listeners\Inbox\NotifyNewInboxItem;
use App\Listeners\Social\NotifyAccountConnected;
use App\Listeners\Social\NotifyTokenExpiring;
use App\Listeners\Tenant\SendInvitationEmail;
use App\Listeners\Workspace\NotifyMemberAdded;
use App\Listeners\Workspace\NotifyMemberRemoved;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PostPublished::class => [
            NotifyPostPublished::class,
        ],
        PostFailed::class => [
            NotifyPostFailed::class,
        ],
        PostSubmittedForApproval::class => [
            NotifyApprovalNeeded::class,
        ],
        PostApproved::class => [
            NotifyPostApproved::class,
        ],
        PostRejected::class => [
            NotifyPostRejected::class,
        ],
        TenantCreated::class => [
            // Listeners will be added in later steps (e.g., provisioning, welcome email)
        ],
        UserInvited::class => [
            SendInvitationEmail::class,
        ],
        MemberAdded::class => [
            NotifyMemberAdded::class,
        ],
        MemberRemoved::class => [
            NotifyMemberRemoved::class,
        ],
        SubscriptionCreated::class => [
            NotifySubscriptionCreated::class,
        ],
        PaymentFailed::class => [
            NotifyPaymentFailed::class,
        ],
        TrialEnding::class => [
            NotifyTrialEnding::class,
        ],
        AccountConnected::class => [
            NotifyAccountConnected::class,
        ],
        TokenExpiring::class => [
            NotifyTokenExpiring::class,
        ],
        NewInboxItemReceived::class => [
            NotifyNewInboxItem::class,
        ],
    ];
}
