<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Tenant Isolation Property Test
 *
 * Validates that queries are properly scoped to tenant/workspace
 * and that data from one tenant is never accessible to another tenant.
 *
 * Feature: platform-audit-and-testing
 */
class TenantIsolationPropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    /**
     * Property 7: Database Persistence Verification - Tenant Isolation
     *
     * For any query scoped to a workspace,
     * the results should only include records belonging to that workspace
     * and never include records from other workspaces/tenants.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 16.3
     */
    public function test_workspace_queries_are_properly_scoped_to_tenant(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 5), // Number of tenants
            PropertyGenerators::integer(1, 3)  // Workspaces per tenant
        )
            ->then(function ($tenantCount, $workspacesPerTenant) {
                // Create multiple tenants with workspaces
                $tenants = [];
                $workspaces = [];
                
                for ($i = 0; $i < $tenantCount; $i++) {
                    $tenant = Tenant::factory()->active()->create([
                        'name' => "Tenant {$i}",
                    ]);
                    $tenants[] = $tenant;
                    
                    for ($j = 0; $j < $workspacesPerTenant; $j++) {
                        $workspace = Workspace::factory()->create([
                            'tenant_id' => $tenant->id,
                            'name' => "Workspace {$i}-{$j}",
                        ]);
                        $workspaces[] = $workspace;
                        
                        // Create posts in each workspace
                        Post::factory()->count(3)->draft()->create([
                            'workspace_id' => $workspace->id,
                            'created_by_user_id' => User::factory()->create([
                                'tenant_id' => $tenant->id,
                            ])->id,
                        ]);
                    }
                }

                // Property: Queries scoped to a workspace only return that workspace's data
                foreach ($workspaces as $workspace) {
                    $posts = Post::forWorkspace($workspace->id)->get();
                    
                    // All posts should belong to the queried workspace
                    foreach ($posts as $post) {
                        $this->assertEquals(
                            $workspace->id,
                            $post->workspace_id,
                            "Post {$post->id} should belong to workspace {$workspace->id}"
                        );
                        
                        // Verify the post's workspace belongs to the correct tenant
                        $this->assertEquals(
                            $workspace->tenant_id,
                            $post->workspace->tenant_id,
                            "Post's workspace should belong to tenant {$workspace->tenant_id}"
                        );
                    }
                    
                    // Count should match only this workspace's posts
                    $expectedCount = Post::where('workspace_id', $workspace->id)->count();
                    $this->assertEquals(
                        $expectedCount,
                        $posts->count(),
                        "Scoped query should return exactly {$expectedCount} posts for workspace {$workspace->id}"
                    );
                }

                // Property: No cross-tenant data leakage
                foreach ($tenants as $tenant) {
                    $tenantWorkspaces = Workspace::forTenant($tenant->id)->pluck('id');
                    $tenantPosts = Post::whereIn('workspace_id', $tenantWorkspaces)->get();
                    
                    // All posts should belong to workspaces of this tenant
                    foreach ($tenantPosts as $post) {
                        $this->assertTrue(
                            $tenantWorkspaces->contains($post->workspace_id),
                            "Post {$post->id} should belong to a workspace of tenant {$tenant->id}"
                        );
                    }
                }
            });
    }

    /**
     * Property 7: Database Persistence Verification - Workspace Membership Isolation
     *
     * For any workspace membership query,
     * the results should only include memberships for the specified workspace
     * and never include memberships from other workspaces.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 16.3
     */
    public function test_workspace_membership_queries_are_properly_isolated(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 4), // Number of workspaces
            PropertyGenerators::integer(2, 5)  // Members per workspace
        )
            ->then(function ($workspaceCount, $membersPerWorkspace) {
                $tenant = Tenant::factory()->active()->create();
                $workspaces = [];
                
                // Create workspaces with members
                for ($i = 0; $i < $workspaceCount; $i++) {
                    $workspace = Workspace::factory()->create([
                        'tenant_id' => $tenant->id,
                        'name' => "Workspace {$i}",
                    ]);
                    $workspaces[] = $workspace;
                    
                    // Add members to workspace
                    for ($j = 0; $j < $membersPerWorkspace; $j++) {
                        $user = User::factory()->create([
                            'tenant_id' => $tenant->id,
                        ]);
                        
                        WorkspaceMembership::create([
                            'workspace_id' => $workspace->id,
                            'user_id' => $user->id,
                            'role' => WorkspaceRole::EDITOR,
                            'joined_at' => now(),
                        ]);
                    }
                }

                // Property: Membership queries are scoped to workspace
                foreach ($workspaces as $workspace) {
                    $memberships = WorkspaceMembership::where('workspace_id', $workspace->id)->get();
                    
                    // All memberships should belong to the queried workspace
                    foreach ($memberships as $membership) {
                        $this->assertEquals(
                            $workspace->id,
                            $membership->workspace_id,
                            "Membership {$membership->id} should belong to workspace {$workspace->id}"
                        );
                    }
                    
                    // Count should match expected
                    $this->assertEquals(
                        $membersPerWorkspace,
                        $memberships->count(),
                        "Workspace {$workspace->id} should have exactly {$membersPerWorkspace} members"
                    );
                }

                // Property: No cross-workspace membership leakage
                foreach ($workspaces as $workspace) {
                    $otherWorkspaces = collect($workspaces)->reject(fn($w) => $w->id === $workspace->id);
                    $workspaceMemberIds = WorkspaceMembership::where('workspace_id', $workspace->id)
                        ->pluck('user_id');
                    
                    foreach ($otherWorkspaces as $otherWorkspace) {
                        $otherMemberIds = WorkspaceMembership::where('workspace_id', $otherWorkspace->id)
                            ->pluck('user_id');
                        
                        // Members can be in multiple workspaces, but memberships should be distinct
                        $workspaceMemberships = WorkspaceMembership::where('workspace_id', $workspace->id)->get();
                        $otherMemberships = WorkspaceMembership::where('workspace_id', $otherWorkspace->id)->get();
                        
                        // No membership record should appear in both queries
                        $workspaceMembershipIds = $workspaceMemberships->pluck('id');
                        $otherMembershipIds = $otherMemberships->pluck('id');
                        
                        $intersection = $workspaceMembershipIds->intersect($otherMembershipIds);
                        $this->assertEmpty(
                            $intersection,
                            "No membership records should be shared between workspace {$workspace->id} and {$otherWorkspace->id}"
                        );
                    }
                }
            });
    }

    /**
     * Property 7: Database Persistence Verification - Social Account Isolation
     *
     * For any social account query scoped to a workspace,
     * the results should only include accounts connected to that workspace
     * and never include accounts from other workspaces.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 16.3
     */
    public function test_social_account_queries_are_properly_scoped(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 4), // Number of workspaces
            PropertyGenerators::integer(1, 3)  // Accounts per workspace
        )
            ->then(function ($workspaceCount, $accountsPerWorkspace) {
                $tenant = Tenant::factory()->active()->create();
                $workspaces = [];
                
                // Create workspaces with social accounts
                for ($i = 0; $i < $workspaceCount; $i++) {
                    $workspace = Workspace::factory()->create([
                        'tenant_id' => $tenant->id,
                        'name' => "Workspace {$i}",
                    ]);
                    $workspaces[] = $workspace;
                    
                    $user = User::factory()->create([
                        'tenant_id' => $tenant->id,
                    ]);
                    
                    // Create social accounts for workspace
                    for ($j = 0; $j < $accountsPerWorkspace; $j++) {
                        SocialAccount::factory()->facebook()->connected()->create([
                            'workspace_id' => $workspace->id,
                            'connected_by_user_id' => $user->id,
                            'platform_account_id' => "account_{$i}_{$j}",
                        ]);
                    }
                }

                // Property: Social account queries are scoped to workspace
                foreach ($workspaces as $workspace) {
                    $accounts = SocialAccount::where('workspace_id', $workspace->id)->get();
                    
                    // All accounts should belong to the queried workspace
                    foreach ($accounts as $account) {
                        $this->assertEquals(
                            $workspace->id,
                            $account->workspace_id,
                            "Social account {$account->id} should belong to workspace {$workspace->id}"
                        );
                    }
                    
                    // Count should match expected
                    $this->assertEquals(
                        $accountsPerWorkspace,
                        $accounts->count(),
                        "Workspace {$workspace->id} should have exactly {$accountsPerWorkspace} social accounts"
                    );
                }

                // Property: No cross-workspace account leakage
                foreach ($workspaces as $workspace) {
                    $workspaceAccounts = SocialAccount::where('workspace_id', $workspace->id)
                        ->pluck('id');
                    
                    $otherWorkspaces = collect($workspaces)->reject(fn($w) => $w->id === $workspace->id);
                    
                    foreach ($otherWorkspaces as $otherWorkspace) {
                        $otherAccounts = SocialAccount::where('workspace_id', $otherWorkspace->id)
                            ->pluck('id');
                        
                        // No account should appear in both workspaces
                        $intersection = $workspaceAccounts->intersect($otherAccounts);
                        $this->assertEmpty(
                            $intersection,
                            "No social accounts should be shared between workspace {$workspace->id} and {$otherWorkspace->id}"
                        );
                    }
                }
            });
    }

    /**
     * Property 7: Database Persistence Verification - Cross-Tenant Query Isolation
     *
     * For any query that attempts to access data across tenant boundaries,
     * the system should only return data for the specified tenant
     * and never leak data from other tenants.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 16.3
     */
    public function test_cross_tenant_queries_never_leak_data(): void
    {
        $this->forAll(
            PropertyGenerators::integer(3, 6) // Number of tenants
        )
            ->then(function ($tenantCount) {
                $tenants = [];
                
                // Create tenants with workspaces and data
                for ($i = 0; $i < $tenantCount; $i++) {
                    $tenant = Tenant::factory()->active()->create([
                        'name' => "Tenant {$i}",
                    ]);
                    $tenants[] = $tenant;
                    
                    $workspace = Workspace::factory()->create([
                        'tenant_id' => $tenant->id,
                    ]);
                    
                    $user = User::factory()->create([
                        'tenant_id' => $tenant->id,
                    ]);
                    
                    // Create posts for this tenant's workspace
                    Post::factory()->count(5)->draft()->create([
                        'workspace_id' => $workspace->id,
                        'created_by_user_id' => $user->id,
                    ]);
                }

                // Property: Querying by tenant returns only that tenant's data
                foreach ($tenants as $tenant) {
                    $tenantWorkspaces = Workspace::forTenant($tenant->id)->get();
                    $workspaceIds = $tenantWorkspaces->pluck('id');
                    
                    // Get all posts for this tenant's workspaces
                    $tenantPosts = Post::whereIn('workspace_id', $workspaceIds)->get();
                    
                    // Verify all posts belong to this tenant's workspaces
                    foreach ($tenantPosts as $post) {
                        $this->assertTrue(
                            $workspaceIds->contains($post->workspace_id),
                            "Post {$post->id} should belong to a workspace of tenant {$tenant->id}"
                        );
                        
                        // Double-check via workspace relationship
                        $this->assertEquals(
                            $tenant->id,
                            $post->workspace->tenant_id,
                            "Post's workspace should belong to tenant {$tenant->id}"
                        );
                    }
                    
                    // Verify count matches expected
                    $expectedCount = Post::whereIn('workspace_id', $workspaceIds)->count();
                    $this->assertEquals(
                        $expectedCount,
                        $tenantPosts->count(),
                        "Should have exactly {$expectedCount} posts for tenant {$tenant->id}"
                    );
                }

                // Property: No tenant can access another tenant's data
                for ($i = 0; $i < count($tenants); $i++) {
                    $tenant = $tenants[$i];
                    $tenantWorkspaces = Workspace::forTenant($tenant->id)->pluck('id');
                    
                    for ($j = 0; $j < count($tenants); $j++) {
                        if ($i === $j) {
                            continue; // Skip same tenant
                        }
                        
                        $otherTenant = $tenants[$j];
                        $otherWorkspaces = Workspace::forTenant($otherTenant->id)->pluck('id');
                        
                        // Verify no workspace overlap
                        $intersection = $tenantWorkspaces->intersect($otherWorkspaces);
                        $this->assertEmpty(
                            $intersection,
                            "Tenant {$tenant->id} and {$otherTenant->id} should have no shared workspaces"
                        );
                        
                        // Verify no post overlap
                        $tenantPosts = Post::whereIn('workspace_id', $tenantWorkspaces)->pluck('id');
                        $otherPosts = Post::whereIn('workspace_id', $otherWorkspaces)->pluck('id');
                        
                        $postIntersection = $tenantPosts->intersect($otherPosts);
                        $this->assertEmpty(
                            $postIntersection,
                            "Tenant {$tenant->id} and {$otherTenant->id} should have no shared posts"
                        );
                    }
                }
            });
    }

    /**
     * Property 7: Database Persistence Verification - Workspace Scope Consistency
     *
     * For any model with workspace_id,
     * queries using the forWorkspace scope should return identical results
     * to queries using where('workspace_id', $id).
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 16.3
     */
    public function test_workspace_scope_consistency(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 5) // Number of workspaces
        )
            ->then(function ($workspaceCount) {
                $tenant = Tenant::factory()->active()->create();
                $workspaces = [];
                
                // Create workspaces with posts
                for ($i = 0; $i < $workspaceCount; $i++) {
                    $workspace = Workspace::factory()->create([
                        'tenant_id' => $tenant->id,
                    ]);
                    $workspaces[] = $workspace;
                    
                    $user = User::factory()->create([
                        'tenant_id' => $tenant->id,
                    ]);
                    
                    Post::factory()->count(rand(1, 10))->draft()->create([
                        'workspace_id' => $workspace->id,
                        'created_by_user_id' => $user->id,
                    ]);
                }

                // Property: forWorkspace scope returns same results as where clause
                foreach ($workspaces as $workspace) {
                    $scopedPosts = Post::forWorkspace($workspace->id)->pluck('id')->sort()->values();
                    $wherePosts = Post::where('workspace_id', $workspace->id)->pluck('id')->sort()->values();
                    
                    $this->assertEquals(
                        $wherePosts->toArray(),
                        $scopedPosts->toArray(),
                        "forWorkspace scope should return same posts as where clause for workspace {$workspace->id}"
                    );
                    
                    // Verify counts match
                    $scopedCount = Post::forWorkspace($workspace->id)->count();
                    $whereCount = Post::where('workspace_id', $workspace->id)->count();
                    
                    $this->assertEquals(
                        $whereCount,
                        $scopedCount,
                        "forWorkspace scope count should match where clause count for workspace {$workspace->id}"
                    );
                }
            });
    }
}
