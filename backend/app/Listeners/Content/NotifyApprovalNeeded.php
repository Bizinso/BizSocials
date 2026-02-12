<?php

declare(strict_types=1);

namespace App\Listeners\Content;

use App\Enums\Notification\NotificationType;
use App\Enums\Workspace\WorkspaceRole;
use App\Events\Content\PostSubmittedForApproval;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyApprovalNeeded implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PostSubmittedForApproval $event): void
    {
        $post = $event->post;
        $workspace = $post->workspace;

        if ($workspace === null) {
            return;
        }

        $adminMemberships = $workspace->memberships()
            ->with('user')
            ->whereIn('role', [WorkspaceRole::OWNER, WorkspaceRole::ADMIN])
            ->get();

        foreach ($adminMemberships as $membership) {
            $user = $membership->user;

            if ($user === null) {
                continue;
            }

            $this->notificationService->send(
                user: $user,
                type: NotificationType::POST_SUBMITTED,
                title: 'Post Awaiting Approval',
                message: "A post by {$event->submitter->name} requires your approval.",
                data: [
                    'post_id' => $post->id,
                    'workspace_id' => $workspace->id,
                    'submitter_id' => $event->submitter->id,
                    'submitter_name' => $event->submitter->name,
                ],
            );
        }
    }
}
