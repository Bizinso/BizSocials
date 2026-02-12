<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Events\Content\PostApproved;
use App\Events\Content\PostRejected;
use App\Events\Tenant\UserInvited;
use App\Models\Content\Post;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\ApprovalService;
use App\Services\Tenant\InvitationService;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->editor = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->owner, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->editor, WorkspaceRole::EDITOR);
});

describe('Approval events', function () {
    it('dispatches PostApproved event when post is approved', function () {
        Event::fake([PostApproved::class]);

        $post = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'status' => PostStatus::SUBMITTED,
        ]);

        $approvalService = app(ApprovalService::class);
        $approvalService->approve($post, $this->owner, 'Looks good');

        Event::assertDispatched(PostApproved::class, function (PostApproved $event) use ($post) {
            return $event->post->id === $post->id
                && $event->approver->id === $this->owner->id;
        });
    });

    it('dispatches PostRejected event when post is rejected', function () {
        Event::fake([PostRejected::class]);

        $post = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'status' => PostStatus::SUBMITTED,
        ]);

        $approvalService = app(ApprovalService::class);
        $approvalService->reject($post, $this->owner, 'Needs changes');

        Event::assertDispatched(PostRejected::class, function (PostRejected $event) use ($post) {
            return $event->post->id === $post->id
                && $event->reason === 'Needs changes';
        });
    });
});

describe('Invitation events', function () {
    it('dispatches UserInvited event when invitation is sent', function () {
        Event::fake([UserInvited::class]);

        $invitationService = app(InvitationService::class);
        $data = new \App\Data\Tenant\InviteUserData(
            email: 'newuser@example.com',
            role: TenantRole::MEMBER,
        );
        $invitationService->invite($this->tenant, $data, $this->owner);

        Event::assertDispatched(UserInvited::class, function (UserInvited $event) {
            return $event->invitation->email === 'newuser@example.com';
        });
    });
});
