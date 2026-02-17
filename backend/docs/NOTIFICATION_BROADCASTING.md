# Notification Broadcasting with Laravel Reverb

## Overview

This document describes the real-time notification broadcasting system implemented using Laravel Reverb for WebSocket connections. The system provides real-time updates for notifications, inbox messages, and post status changes.

## Architecture

### Components

1. **Laravel Reverb**: WebSocket server for real-time communication
2. **Broadcasting Events**: Laravel events that implement `ShouldBroadcast`
3. **NotificationBroadcastService**: Service layer for managing broadcasts
4. **Private Channels**: User and workspace-specific channels for secure delivery

### Event Flow

```
User Action → Service Layer → Create/Update Model → Broadcast Event → Laravel Reverb → WebSocket → Frontend
```

## Configuration

### Environment Variables

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=1
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Broadcasting Configuration

The broadcasting configuration is located in `config/broadcasting.php`:

```php
'default' => env('BROADCAST_CONNECTION', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
        ],
    ],
],
```

## Broadcast Events

### 1. NewNotification

Broadcasts when a new notification is created for a user.

**Channel**: `private-user.{user_id}`

**Event Name**: `notification.new`

**Payload**:
```json
{
  "id": "uuid",
  "type": "post_published",
  "title": "Post Published",
  "message": "Your post has been published successfully",
  "action_url": "/posts/123",
  "created_at": "2024-02-16T10:30:00Z"
}
```

**Usage**:
```php
use App\Events\Broadcast\NewNotification;

$notification = Notification::createForUser($user, ...);
broadcast(new NewNotification($notification));
```

### 2. InboxItemReceived

Broadcasts when a new inbox message is received from a social platform.

**Channel**: `private-workspace.{workspace_id}.inbox`

**Event Name**: `inbox.item_received`

**Payload**:
```json
{
  "id": "uuid",
  "platform": "facebook",
  "type": "comment",
  "author_name": "John Doe",
  "content_preview": "This is a great post!",
  "created_at": "2024-02-16T10:30:00Z"
}
```

**Usage**:
```php
use App\Events\Broadcast\InboxItemReceived;

$inboxItem = InboxItem::create([...]);
broadcast(new InboxItemReceived($inboxItem));
```

### 3. InboxMessageReplied

Broadcasts when a reply is sent to an inbox message.

**Channel**: `private-workspace.{workspace_id}.inbox`

**Event Name**: `inbox.message_replied`

**Payload**:
```json
{
  "inbox_item_id": "uuid",
  "reply_content": "Thank you for your feedback!",
  "replied_by": "Jane Smith",
  "replied_at": "2024-02-16T10:35:00Z"
}
```

**Usage**:
```php
use App\Events\Broadcast\InboxMessageReplied;

broadcast(new InboxMessageReplied($inboxItem, $replyContent, $user->name));
```

### 4. InboxMessageAssigned

Broadcasts when an inbox message is assigned to a team member.

**Channels**: 
- `private-workspace.{workspace_id}.inbox`
- `private-user.{assigned_user_id}`

**Event Name**: `inbox.message_assigned`

**Payload**:
```json
{
  "inbox_item_id": "uuid",
  "assigned_to_user_id": "uuid",
  "assigned_by_user_id": "uuid",
  "platform": "instagram",
  "author_name": "John Doe",
  "content_preview": "Question about your product",
  "assigned_at": "2024-02-16T10:40:00Z"
}
```

**Usage**:
```php
use App\Events\Broadcast\InboxMessageAssigned;

broadcast(new InboxMessageAssigned($inboxItem, $assignedTo->id, $assignedBy->id));
```

### 5. PostStatusChanged

Broadcasts when a post's status changes (published, failed, etc.).

**Channel**: `private-workspace.{workspace_id}.posts`

**Event Name**: `post.status_changed`

**Payload**:
```json
{
  "id": "uuid",
  "status": "published",
  "updated_at": "2024-02-16T10:45:00Z"
}
```

## NotificationBroadcastService

The `NotificationBroadcastService` provides a centralized service for managing all broadcast operations.

### Methods

#### broadcastNotification(Notification $notification): bool

Broadcasts a notification to a user's channel.

```php
$broadcastService = app(NotificationBroadcastService::class);
$broadcastService->broadcastNotification($notification);
```

#### broadcastInboxItemReceived(InboxItem $inboxItem): bool

Broadcasts when a new inbox item is received.

```php
$broadcastService->broadcastInboxItemReceived($inboxItem);
```

#### broadcastInboxMessageReplied(InboxItem $inboxItem, string $replyContent, User $repliedBy): bool

Broadcasts when a reply is sent to an inbox message.

```php
$broadcastService->broadcastInboxMessageReplied($inboxItem, $replyContent, $user);
```

#### broadcastInboxMessageAssigned(InboxItem $inboxItem, User $assignedTo, User $assignedBy): bool

Broadcasts when an inbox message is assigned.

```php
$broadcastService->broadcastInboxMessageAssigned($inboxItem, $assignedTo, $assignedBy);
```

#### broadcastPostStatusChanged(Post $post, string $oldStatus, string $newStatus): bool

Broadcasts when a post status changes.

```php
$broadcastService->broadcastPostStatusChanged($post, 'draft', 'published');
```

## Integration with Services

### NotificationService

The `NotificationService` automatically broadcasts notifications when they are created:

```php
public function send(User $user, NotificationType $type, string $title, string $message): Notification
{
    $notification = $this->createInAppNotification(...);
    
    // Automatically broadcasts via NewNotification event
    broadcast(new NewNotification($notification));
    
    return $notification;
}
```

### InboxReplyService

The `InboxReplyService` broadcasts when replies are sent:

```php
public function create(InboxItem $item, User $user, CreateReplyData $data): InboxReply
{
    $reply = InboxReply::create([...]);
    
    // Broadcast the reply event
    $this->broadcastService->broadcastInboxMessageReplied($item, $data->content_text, $user);
    
    return $reply;
}
```

### InboxService

The `InboxService` broadcasts when messages are assigned:

```php
public function assign(InboxItem $item, User $user, User $assignedBy): InboxItem
{
    $item->assignTo($user);
    
    // Broadcast the assignment event
    $this->broadcastService->broadcastInboxMessageAssigned($item, $user, $assignedBy);
    
    return $item;
}
```

### MessageFetchingService

The `MessageFetchingService` broadcasts when new messages are fetched:

```php
private function storeInboxItem(...): bool
{
    $inboxItem = InboxItem::create([...]);
    
    // Broadcast the new inbox item
    $this->broadcastService->broadcastInboxItemReceived($inboxItem);
    
    return true;
}
```

## Channel Authorization

Private channels require authorization. Define authorization logic in `routes/channels.php`:

```php
use Illuminate\Support\Facades\Broadcast;

// User channel - user can only listen to their own channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

// Workspace inbox channel - user must be a member of the workspace
Broadcast::channel('workspace.{workspaceId}.inbox', function ($user, $workspaceId) {
    return $user->workspaces()->where('workspaces.id', $workspaceId)->exists();
});

// Workspace posts channel - user must be a member of the workspace
Broadcast::channel('workspace.{workspaceId}.posts', function ($user, $workspaceId) {
    return $user->workspaces()->where('workspaces.id', $workspaceId)->exists();
});
```

## Running Laravel Reverb

### Development

Start the Reverb server:

```bash
php artisan reverb:start
```

Or with debug output:

```bash
php artisan reverb:start --debug
```

### Production

For production, use a process manager like Supervisor:

```ini
[program:reverb]
command=php /path/to/artisan reverb:start
directory=/path/to/project
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/logs/reverb.log
```

## Frontend Integration

### Connecting to Reverb

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Listening to Events

#### User Notifications

```javascript
Echo.private(`user.${userId}`)
    .listen('.notification.new', (event) => {
        console.log('New notification:', event);
        // Update UI with new notification
        showNotification(event);
    });
```

#### Inbox Messages

```javascript
Echo.private(`workspace.${workspaceId}.inbox`)
    .listen('.inbox.item_received', (event) => {
        console.log('New inbox item:', event);
        // Add to inbox list
        addInboxItem(event);
    })
    .listen('.inbox.message_replied', (event) => {
        console.log('Message replied:', event);
        // Update inbox item with reply
        updateInboxItemWithReply(event);
    })
    .listen('.inbox.message_assigned', (event) => {
        console.log('Message assigned:', event);
        // Update assignment status
        updateAssignment(event);
    });
```

#### Post Status Changes

```javascript
Echo.private(`workspace.${workspaceId}.posts`)
    .listen('.post.status_changed', (event) => {
        console.log('Post status changed:', event);
        // Update post status in UI
        updatePostStatus(event);
    });
```

## Testing

### Testing Broadcast Events

```php
use Illuminate\Support\Facades\Event;

test('notification is broadcasted when created', function () {
    Event::fake([NewNotification::class]);
    
    $user = User::factory()->create();
    $notification = Notification::createForUser($user, ...);
    
    Event::assertDispatched(NewNotification::class, function ($event) use ($notification) {
        return $event->notification->id === $notification->id;
    });
});
```

### Testing Broadcast Service

```php
test('broadcast service broadcasts inbox item received', function () {
    $broadcastService = app(NotificationBroadcastService::class);
    $inboxItem = InboxItem::factory()->create();
    
    $result = $broadcastService->broadcastInboxItemReceived($inboxItem);
    
    expect($result)->toBeTrue();
});
```

## Monitoring and Debugging

### Enable Debug Mode

```bash
php artisan reverb:start --debug
```

### Check Connection Status

```php
$broadcastService = app(NotificationBroadcastService::class);
$isEnabled = $broadcastService->isBroadcastingEnabled();
$connection = $broadcastService->getBroadcastConnection();
```

### Test Connection

```php
$broadcastService = app(NotificationBroadcastService::class);
$testResult = $broadcastService->testBroadcastConnection();
```

## Troubleshooting

### Broadcasts Not Received

1. Check Reverb server is running: `php artisan reverb:start`
2. Verify environment variables are set correctly
3. Check channel authorization in `routes/channels.php`
4. Verify frontend Echo configuration matches backend settings
5. Check browser console for WebSocket connection errors

### Connection Refused

1. Ensure Reverb port is not blocked by firewall
2. Verify `REVERB_HOST` and `REVERB_PORT` are correct
3. Check if another service is using the same port

### Events Not Broadcasting

1. Verify event implements `ShouldBroadcast` interface
2. Check `broadcastOn()` returns correct channel(s)
3. Ensure queue worker is running if using queued broadcasts
4. Check Laravel logs for broadcast errors

## Performance Considerations

1. **Queue Broadcasts**: For high-volume applications, queue broadcast events:
   ```php
   class NewNotification implements ShouldBroadcastNow
   {
       // Broadcasts immediately without queuing
   }
   ```

2. **Channel Optimization**: Use specific channels to reduce unnecessary broadcasts

3. **Payload Size**: Keep broadcast payloads small for better performance

4. **Connection Limits**: Monitor concurrent WebSocket connections

## Security Best Practices

1. **Always use private channels** for sensitive data
2. **Implement proper authorization** in `routes/channels.php`
3. **Use HTTPS/WSS** in production
4. **Validate user permissions** before broadcasting
5. **Sanitize broadcast data** to prevent XSS attacks
6. **Rate limit** broadcast events to prevent abuse

## Related Documentation

- [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [NotificationService Documentation](./NOTIFICATION_SERVICE.md)
- [Inbox System Documentation](./INBOX_SYSTEM.md)
