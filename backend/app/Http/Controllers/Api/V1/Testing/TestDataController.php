<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Testing;

use App\Http\Controllers\Controller;
use App\Models\Content\Post;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Support\SupportTicket;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Test Data Controller
 * 
 * Provides endpoints for E2E tests to seed and cleanup test data.
 * Only available in non-production environments.
 */
class TestDataController extends Controller
{
    /**
     * Create a test user with optional workspace
     */
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string',
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'nullable|string|in:owner,admin,member',
            'with_workspace' => 'nullable|boolean',
            'workspace_name' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $validated['tenant_id'] ?? null,
            'email_verified_at' => now(),
        ]);

        $workspace = null;
        if ($validated['with_workspace'] ?? false) {
            $workspace = Workspace::create([
                'name' => $validated['workspace_name'] ?? 'Test Workspace',
                'tenant_id' => $user->tenant_id,
                'created_by' => $user->id,
            ]);

            // Add user as workspace member
            $workspace->members()->attach($user->id, [
                'role' => $validated['role'] ?? 'owner',
            ]);
        }

        return response()->json([
            'data' => [
                'user' => $user,
                'workspace' => $workspace,
            ],
        ], 201);
    }

    /**
     * Create test posts for a workspace
     */
    public function createPosts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'count' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|string|in:draft,scheduled,published,failed',
            'user_id' => 'required|exists:users,id',
        ]);

        $count = $validated['count'] ?? 5;
        $posts = [];

        for ($i = 0; $i < $count; $i++) {
            $posts[] = Post::create([
                'workspace_id' => $validated['workspace_id'],
                'created_by_user_id' => $validated['user_id'],
                'content_text' => "Test post content #{$i}",
                'status' => $validated['status'] ?? 'draft',
                'scheduled_at' => ($validated['status'] ?? 'draft') === 'scheduled' 
                    ? now()->addHours($i + 1) 
                    : null,
            ]);
        }

        return response()->json([
            'data' => $posts,
        ], 201);
    }

    /**
     * Create test inbox items for a workspace
     */
    public function createInboxItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'count' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|string|in:unread,read,resolved',
            'platform' => 'nullable|string|in:facebook,instagram,twitter,linkedin',
        ]);

        $count = $validated['count'] ?? 5;
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = InboxItem::create([
                'workspace_id' => $validated['workspace_id'],
                'platform' => $validated['platform'] ?? 'facebook',
                'platform_id' => 'test_' . uniqid(),
                'type' => 'comment',
                'content' => "Test inbox message #{$i}",
                'sender_name' => "Test User #{$i}",
                'sender_id' => 'test_sender_' . $i,
                'status' => $validated['status'] ?? 'unread',
                'received_at' => now()->subMinutes($i),
            ]);
        }

        return response()->json([
            'data' => $items,
        ], 201);
    }

    /**
     * Create test support tickets
     */
    public function createTickets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'user_id' => 'required|exists:users,id',
            'count' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|string|in:open,assigned,resolved,closed',
        ]);

        $count = $validated['count'] ?? 5;
        $tickets = [];

        for ($i = 0; $i < $count; $i++) {
            $tickets[] = SupportTicket::create([
                'tenant_id' => $validated['tenant_id'],
                'user_id' => $validated['user_id'],
                'subject' => "Test ticket #{$i}",
                'description' => "Test ticket description #{$i}",
                'status' => $validated['status'] ?? 'open',
                'priority' => 'medium',
            ]);
        }

        return response()->json([
            'data' => $tickets,
        ], 201);
    }

    /**
     * Create test social accounts for a workspace
     */
    public function createSocialAccounts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'platform' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'account_name' => 'required|string',
        ]);

        $account = SocialAccount::create([
            'workspace_id' => $validated['workspace_id'],
            'platform' => $validated['platform'],
            'platform_account_id' => 'test_' . uniqid(),
            'account_name' => $validated['account_name'],
            'access_token' => encrypt('test_token_' . uniqid()),
            'is_active' => true,
        ]);

        return response()->json([
            'data' => $account,
        ], 201);
    }

    /**
     * Cleanup test data by email pattern
     */
    public function cleanup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_pattern' => 'nullable|string',
            'workspace_id' => 'nullable|exists:workspaces,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $deleted = [
            'users' => 0,
            'workspaces' => 0,
            'posts' => 0,
            'inbox_items' => 0,
            'tickets' => 0,
            'social_accounts' => 0,
        ];

        DB::transaction(function () use ($validated, &$deleted) {
            // Cleanup by email pattern (e.g., 'e2e-test-%')
            if (isset($validated['email_pattern'])) {
                $users = User::where('email', 'like', $validated['email_pattern'])->get();
                
                foreach ($users as $user) {
                    // Delete related data
                    Post::where('created_by_user_id', $user->id)->delete();
                    SupportTicket::where('user_id', $user->id)->delete();
                    
                    // Delete workspaces created by this user
                    $workspaces = Workspace::where('created_by', $user->id)->get();
                    foreach ($workspaces as $workspace) {
                        InboxItem::where('workspace_id', $workspace->id)->delete();
                        SocialAccount::where('workspace_id', $workspace->id)->delete();
                        $workspace->delete();
                        $deleted['workspaces']++;
                    }
                    
                    $user->delete();
                    $deleted['users']++;
                }
            }

            // Cleanup specific workspace
            if (isset($validated['workspace_id'])) {
                $deleted['posts'] += Post::where('workspace_id', $validated['workspace_id'])->delete();
                $deleted['inbox_items'] += InboxItem::where('workspace_id', $validated['workspace_id'])->delete();
                $deleted['social_accounts'] += SocialAccount::where('workspace_id', $validated['workspace_id'])->delete();
                Workspace::where('id', $validated['workspace_id'])->delete();
                $deleted['workspaces']++;
            }

            // Cleanup specific user
            if (isset($validated['user_id'])) {
                $deleted['posts'] += Post::where('created_by_user_id', $validated['user_id'])->delete();
                $deleted['tickets'] += SupportTicket::where('user_id', $validated['user_id'])->delete();
            }
        });

        return response()->json([
            'message' => 'Test data cleaned up successfully',
            'deleted' => $deleted,
        ]);
    }

    /**
     * Reset database to clean state (use with caution!)
     */
    public function reset(Request $request): JsonResponse
    {
        // Only allow in testing environment
        if (app()->environment('production')) {
            abort(403, 'Not available in production');
        }

        DB::transaction(function () {
            // Truncate test-related tables
            DB::table('posts')->truncate();
            DB::table('inbox_items')->truncate();
            DB::table('support_tickets')->truncate();
            DB::table('social_accounts')->truncate();
            
            // Delete test users (keep seeded users)
            User::where('email', 'like', 'e2e-test-%')->delete();
            User::where('email', 'like', 'test-%')->delete();
        });

        return response()->json([
            'message' => 'Database reset successfully',
        ]);
    }
}
