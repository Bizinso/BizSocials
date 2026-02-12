<?php

declare(strict_types=1);

use App\Jobs\Content\PublishScheduledPostsJob;
use App\Jobs\Inbox\ArchiveOldInboxItemsJob;
use App\Jobs\Notification\CleanupOldNotificationsJob;
use App\Jobs\Social\RefreshExpiringTokensJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
|
| The following jobs are scheduled to run automatically at specified
| intervals. These jobs handle background processing for the BizSocials
| platform including content publishing, data syncing, and maintenance.
|
*/

// Every minute - check for scheduled posts ready to be published
Schedule::job(new PublishScheduledPostsJob)
    ->everyMinute()
    ->withoutOverlapping()
    ->name('publish-scheduled-posts')
    ->onOneServer();

// Every 15 minutes - sync inbox for all active workspaces
Schedule::command('inbox:sync-all')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->name('inbox-sync-all')
    ->onOneServer();

// Every 6 hours - fetch post metrics for analytics
Schedule::command('analytics:fetch-metrics')
    ->everySixHours()
    ->withoutOverlapping()
    ->name('analytics-fetch-metrics')
    ->onOneServer();

// Daily at 2:00 AM - cleanup old notifications
Schedule::job(new CleanupOldNotificationsJob)
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->name('cleanup-old-notifications')
    ->onOneServer();

// Daily at 3:00 AM - refresh expiring OAuth tokens
Schedule::job(new RefreshExpiringTokensJob)
    ->daily()
    ->at('03:00')
    ->withoutOverlapping()
    ->name('refresh-expiring-tokens')
    ->onOneServer();

// Daily at 4:00 AM - process pending data exports
Schedule::command('privacy:process-exports')
    ->daily()
    ->at('04:00')
    ->withoutOverlapping()
    ->name('process-pending-exports')
    ->onOneServer();

// Daily at 5:00 AM - process approved data deletions past grace period
Schedule::command('privacy:process-deletions')
    ->daily()
    ->at('05:00')
    ->withoutOverlapping()
    ->name('process-pending-deletions')
    ->onOneServer();

// Weekly on Sundays at 4:00 AM - archive old inbox items
Schedule::job(new ArchiveOldInboxItemsJob)
    ->weekly()
    ->sundays()
    ->at('04:00')
    ->withoutOverlapping()
    ->name('archive-old-inbox-items')
    ->onOneServer();
