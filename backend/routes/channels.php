<?php

declare(strict_types=1);

use App\Models\Workspace\WorkspaceMember;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register all broadcast channel authorization callbacks. These channels
| use private channels that require the user to be authenticated.
|
*/

// User-specific notifications
Broadcast::channel('user.{userId}', function ($user, string $userId) {
    return $user->id === $userId;
});

// Workspace-level events (post status changes)
Broadcast::channel('workspace.{workspaceId}', function ($user, string $workspaceId) {
    return WorkspaceMember::where('workspace_id', $workspaceId)
        ->where('user_id', $user->id)
        ->exists();
});

// Workspace inbox events
Broadcast::channel('workspace.{workspaceId}.inbox', function ($user, string $workspaceId) {
    return WorkspaceMember::where('workspace_id', $workspaceId)
        ->where('user_id', $user->id)
        ->exists();
});
