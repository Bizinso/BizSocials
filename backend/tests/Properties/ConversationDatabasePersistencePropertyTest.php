<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Models\Inbox\InboxConversation;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\ConversationGroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Conversation Database Persistence Property Test
 *
 * Tests that conversation operations persist to the database correctly.
 *
 * Feature: platform-audit-and-testing
 */
class ConversationDatabasePersistencePropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    /**
     * Override the default iteration count to reduce memory usage.
     */
    protected function getPropertyTestIterations(): int
    {
        return 5; // Minimal iterations for testing
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Creation
     *
     * For any conversation creation operation, the conversation should be persisted
     * to the database and be retrievable with all its properties intact.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_creation_persists_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::string(3, 50),
            PropertyGenerators::string(3, 30)
        )
            ->then(function ($participantName, $participantUsername) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                // Create an inbox item
                $item = InboxItem::factory()->create([
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'author_name' => $participantName,
                    'author_username' => $participantUsername,
                    'conversation_id' => null,
                ]);

                // Group the item into a conversation
                $conversationService = app(ConversationGroupingService::class);
                $conversation = $conversationService->groupIntoConversation($item);

                // Verify the conversation exists in the database
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'participant_name' => $participantName,
                    'participant_username' => $participantUsername,
                    'status' => 'active',
                ]);

                // Verify we can retrieve the conversation from the database
                $retrievedConversation = InboxConversation::find($conversation->id);
                $this->assertNotNull($retrievedConversation);
                $this->assertEquals($participantName, $retrievedConversation->participant_name);
                $this->assertEquals($participantUsername, $retrievedConversation->participant_username);
                $this->assertEquals('active', $retrievedConversation->status);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Update
     *
     * For any conversation update operation (adding messages, status changes),
     * the changes should be persisted to the database and be immediately queryable.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_update_persists_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 10)
        )
            ->then(function ($messageCount) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                // Create a conversation
                $conversation = InboxConversation::factory()->create([
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'message_count' => 0,
                    'status' => 'active',
                ]);

                $initialCount = $conversation->message_count;

                // Add messages to the conversation
                for ($i = 0; $i < $messageCount; $i++) {
                    $item = InboxItem::factory()->create([
                        'workspace_id' => $workspace->id,
                        'social_account_id' => $socialAccount->id,
                        'conversation_id' => $conversation->id,
                    ]);
                    
                    $conversation->addMessage($item);
                }

                // Verify the message count is updated in the database
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'message_count' => $initialCount + $messageCount,
                ]);

                // Verify we can retrieve the updated conversation
                $retrievedConversation = InboxConversation::find($conversation->id);
                $this->assertNotNull($retrievedConversation);
                $this->assertEquals($initialCount + $messageCount, $retrievedConversation->message_count);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Status Transitions
     *
     * For any conversation status transition (active -> resolved -> archived),
     * the status change should be immediately persisted and queryable from the database.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_status_transitions_persist_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 5)
        )
            ->then(function ($id) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                // Create an active conversation
                $conversation = InboxConversation::factory()->create([
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'status' => 'active',
                ]);

                // Verify initial status
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'status' => 'active',
                ]);

                // Transition to RESOLVED
                $conversation->markAsResolved();

                // Verify status change is persisted
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'status' => 'resolved',
                ]);

                // Verify we can query by status
                $retrievedConversation = InboxConversation::find($conversation->id);
                $this->assertEquals('resolved', $retrievedConversation->status);

                // Transition to ARCHIVED
                $conversation->archive();

                // Verify archived status is persisted
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'status' => 'archived',
                ]);

                // Verify we can retrieve the archived conversation
                $retrievedConversation = InboxConversation::find($conversation->id);
                $this->assertEquals('archived', $retrievedConversation->status);

                // Reopen the conversation
                $conversation->reopen();

                // Verify reopened status is persisted
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'status' => 'active',
                ]);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Query Consistency
     *
     * For any conversation that exists in the database, querying it multiple times
     * should return consistent results.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_queries_return_consistent_results(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 5)
        )
            ->then(function ($queryCount) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                // Create a conversation
                $conversation = InboxConversation::factory()->create([
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'status' => 'active',
                ]);

                // Query the conversation multiple times
                $results = [];
                for ($i = 0; $i < $queryCount; $i++) {
                    $results[] = InboxConversation::find($conversation->id);
                }

                // Verify all queries returned the same conversation
                foreach ($results as $result) {
                    $this->assertNotNull($result);
                    $this->assertEquals($conversation->id, $result->id);
                    $this->assertEquals($conversation->participant_name, $result->participant_name);
                    $this->assertEquals($conversation->status, $result->status);
                    $this->assertEquals($conversation->conversation_key, $result->conversation_key);
                }
            });
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Grouping
     *
     * For any inbox item grouped into a conversation, the conversation_id should be
     * persisted on the item and the item should be retrievable through the conversation.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_grouping_persists_relationships(): void
    {
        $this->forAll(
            PropertyGenerators::string(3, 50)
        )
            ->then(function ($participantName) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                // Create an inbox item
                $item = InboxItem::factory()->create([
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'author_name' => $participantName,
                    'conversation_id' => null,
                ]);

                // Group the item into a conversation
                $conversationService = app(ConversationGroupingService::class);
                $conversation = $conversationService->groupIntoConversation($item);

                // Verify the item's conversation_id is persisted
                $this->assertDatabaseHas('inbox_items', [
                    'id' => $item->id,
                    'conversation_id' => $conversation->id,
                ]);

                // Verify we can retrieve the item through the conversation
                $retrievedConversation = InboxConversation::find($conversation->id);
                $conversationItems = $retrievedConversation->items;
                
                $this->assertNotNull($conversationItems);
                $this->assertTrue($conversationItems->contains('id', $item->id));
            });
    }

    /**
     * Property 7: Database Persistence Verification - Multiple Items Same Conversation
     *
     * For any multiple inbox items from the same participant, they should be grouped
     * into the same conversation and all relationships should be persisted.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_multiple_items_grouped_into_same_conversation(): void
    {
        $this->forAll(
            PropertyGenerators::string(3, 50),
            PropertyGenerators::integer(2, 5)
        )
            ->then(function ($participantUsername, $itemCount) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                $conversationService = app(ConversationGroupingService::class);
                $conversationIds = [];

                // Create multiple items from the same participant
                // Ensure no post_target_id or platform_post_id so they group by participant
                for ($i = 0; $i < $itemCount; $i++) {
                    $item = InboxItem::factory()->create([
                        'workspace_id' => $workspace->id,
                        'social_account_id' => $socialAccount->id,
                        'author_username' => $participantUsername,
                        'post_target_id' => null,
                        'platform_post_id' => null,
                        'conversation_id' => null,
                        'metadata' => null, // No thread_id in metadata
                    ]);

                    $conversation = $conversationService->groupIntoConversation($item);
                    $conversationIds[] = $conversation->id;
                }

                // Verify all items are grouped into the same conversation
                $uniqueConversationIds = array_unique($conversationIds);
                $this->assertCount(1, $uniqueConversationIds);

                $conversationId = $uniqueConversationIds[0];

                // Verify the conversation exists in the database
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversationId,
                    'workspace_id' => $workspace->id,
                ]);

                // Verify all items are linked to this conversation
                $conversation = InboxConversation::find($conversationId);
                $this->assertEquals($itemCount, $conversation->items()->count());
            });
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Metadata
     *
     * For any conversation with metadata, the metadata should be persisted as JSON
     * and be retrievable with all fields intact.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_metadata_persists_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::string(5, 50),
            PropertyGenerators::string(5, 50)
        )
            ->then(function ($metadataKey, $metadataValue) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);
                $socialAccount = SocialAccount::factory()->create([
                    'workspace_id' => $workspace->id,
                ]);

                // Create a conversation with metadata
                $metadata = [
                    $metadataKey => $metadataValue,
                    'test_field' => 'test_value',
                ];

                $conversation = InboxConversation::factory()->create([
                    'workspace_id' => $workspace->id,
                    'social_account_id' => $socialAccount->id,
                    'metadata' => $metadata,
                ]);

                // Verify the conversation exists with metadata
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                ]);

                // Verify we can retrieve the metadata
                $retrievedConversation = InboxConversation::find($conversation->id);
                $this->assertNotNull($retrievedConversation->metadata);
                $this->assertIsArray($retrievedConversation->metadata);
                $this->assertArrayHasKey($metadataKey, $retrievedConversation->metadata);
                $this->assertEquals($metadataValue, $retrievedConversation->metadata[$metadataKey]);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Conversation Scoping
     *
     * For any conversation, it should be properly scoped to its workspace and
     * not be retrievable when querying a different workspace.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 4.3
     */
    public function test_conversation_workspace_scoping_persists(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 5)
        )
            ->then(function ($id) {
                // Create two separate workspaces
                $user1 = User::factory()->create();
                $workspace1 = Workspace::factory()->create([
                    'tenant_id' => $user1->tenant_id,
                ]);
                $socialAccount1 = SocialAccount::factory()->create([
                    'workspace_id' => $workspace1->id,
                ]);

                $user2 = User::factory()->create();
                $workspace2 = Workspace::factory()->create([
                    'tenant_id' => $user2->tenant_id,
                ]);

                // Create a conversation in workspace1
                $conversation = InboxConversation::factory()->create([
                    'workspace_id' => $workspace1->id,
                    'social_account_id' => $socialAccount1->id,
                ]);

                // Verify the conversation is in workspace1
                $workspace1Conversations = InboxConversation::forWorkspace($workspace1->id)->get();
                $this->assertTrue($workspace1Conversations->contains('id', $conversation->id));

                // Verify the conversation is NOT in workspace2
                $workspace2Conversations = InboxConversation::forWorkspace($workspace2->id)->get();
                $this->assertFalse($workspace2Conversations->contains('id', $conversation->id));

                // Verify database has correct workspace_id
                $this->assertDatabaseHas('inbox_conversations', [
                    'id' => $conversation->id,
                    'workspace_id' => $workspace1->id,
                ]);
            });
    }
}
