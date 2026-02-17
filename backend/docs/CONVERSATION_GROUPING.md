# Conversation Grouping System

## Overview

The conversation grouping system organizes inbox items (comments, mentions, messages) into logical conversation threads. This makes it easier for users to manage and respond to social media engagement by grouping related messages together.

## Architecture

### Database Schema

#### `inbox_conversations` Table
Stores conversation metadata and grouping information.

**Key Fields:**
- `id`: UUID primary key
- `workspace_id`: Links to workspace
- `social_account_id`: Links to social account
- `conversation_key`: Unique identifier for the conversation (e.g., "participant:johndoe", "post:abc123", "thread:xyz789")
- `subject`: Human-readable conversation title
- `participant_name`, `participant_username`: Information about the other party
- `message_count`: Number of messages in the conversation
- `first_message_at`, `last_message_at`: Timestamps for conversation activity
- `status`: Conversation status (active, resolved, archived)

#### `inbox_items` Table (Updated)
Added `conversation_id` field to link items to conversations.

### Models

#### `InboxConversation`
Represents a conversation thread with methods for:
- Managing conversation status (active, resolved, archived)
- Tracking message counts and timestamps
- Querying conversations by workspace, status, and activity

#### `InboxItem` (Updated)
Added `conversation()` relationship to link items to their conversation.

### Service: `ConversationGroupingService`

The core service that implements conversation threading logic.

## Thread Detection Algorithms

The service uses three algorithms in priority order to detect which conversation an inbox item belongs to:

### 1. Platform Thread ID Detection
**Priority: Highest**

Some platforms provide explicit thread/conversation IDs in their API responses. When available, these are used for the most accurate grouping.

**Conversation Key Format:** `thread:{platform_thread_id}`

**Example:**
```php
// Item metadata contains thread_id
$item->metadata = ['thread_id' => 'fb_thread_12345'];
// Results in conversation_key: "thread:fb_thread_12345"
```

### 2. Post-Based Grouping
**Priority: Medium**

Groups all comments on the same post into one conversation. This is useful for managing engagement on specific posts.

**Conversation Key Format:** `post:{post_id}`

**Example:**
```php
// Item is a comment on a post
$item->post_target_id = 'uuid-of-post';
// Results in conversation_key: "post:uuid-of-post"
```

### 3. Participant-Based Grouping
**Priority: Lowest (Fallback)**

Groups all messages from the same author into one conversation. This creates a direct message-style thread with each unique participant.

**Conversation Key Format:** `participant:{normalized_username}`

**Example:**
```php
// Item from a specific user
$item->author_username = 'JohnDoe';
// Results in conversation_key: "participant:johndoe"
```

## Usage

### Automatic Grouping

When new inbox items are created, they should be automatically grouped:

```php
use App\Services\Inbox\ConversationGroupingService;

$groupingService = app(ConversationGroupingService::class);

// Group a new inbox item
$conversation = $groupingService->groupIntoConversation($inboxItem);

// The item is now linked to a conversation
echo $inboxItem->conversation_id; // UUID of conversation
```

### Regrouping Existing Items

To migrate existing data or fix groupings:

```php
$groupingService = app(ConversationGroupingService::class);

// Regroup all items in a workspace
$stats = $groupingService->regroupAllItems($workspaceId);

// Returns:
// [
//     'total_items' => 150,
//     'grouped_items' => 148,
//     'new_conversations' => 42,
//     'errors' => 2,
// ]
```

### Getting Conversation Statistics

```php
$stats = $groupingService->getConversationStats($workspaceId);

// Returns:
// [
//     'total_conversations' => 42,
//     'active_conversations' => 35,
//     'resolved_conversations' => 5,
//     'archived_conversations' => 2,
//     'items_without_conversation' => 0,
// ]
```

## Querying Conversations

### Get All Active Conversations

```php
use App\Models\Inbox\InboxConversation;

$conversations = InboxConversation::forWorkspace($workspaceId)
    ->active()
    ->recentActivity()
    ->get();
```

### Get Conversation with Messages

```php
$conversation = InboxConversation::with('items')
    ->find($conversationId);

foreach ($conversation->items as $item) {
    echo $item->content_text;
}
```

### Get Conversations by Status

```php
$resolvedConversations = InboxConversation::forWorkspace($workspaceId)
    ->withStatus('resolved')
    ->get();
```

## Managing Conversation Status

### Mark as Resolved

```php
$conversation = InboxConversation::find($conversationId);
$conversation->markAsResolved();
```

### Archive Conversation

```php
$conversation->archive();
```

### Reopen Conversation

```php
$conversation->reopen();
```

## Integration Points

### Message Fetching Service

When fetching new messages from social platforms, automatically group them:

```php
// In MessageFetchingService
$inboxItem = InboxItem::create([...]);

$groupingService = app(ConversationGroupingService::class);
$groupingService->groupIntoConversation($inboxItem);
```

### Webhook Handlers

When receiving real-time messages via webhooks:

```php
// In WebhookHandlerService
$inboxItem = $this->processWebhookMessage($payload);

$groupingService = app(ConversationGroupingService::class);
$groupingService->groupIntoConversation($inboxItem);
```

## Best Practices

1. **Always group new items immediately** after creation to maintain consistency
2. **Use platform thread IDs when available** for the most accurate grouping
3. **Run periodic regrouping** if you update the detection algorithms
4. **Monitor items without conversations** using the stats method
5. **Consider conversation status** when displaying to users (hide archived, prioritize active)

## Future Enhancements

Potential improvements to the conversation grouping system:

1. **Smart merging**: Detect when multiple conversations should be merged
2. **Conversation splitting**: Allow users to manually split conversations
3. **AI-based grouping**: Use ML to detect conversation relationships
4. **Cross-platform threading**: Link conversations across different social platforms
5. **Conversation labels**: Add custom labels/tags to conversations
6. **Conversation search**: Full-text search across conversation content
