# TC-011: Multi-Tenancy Isolation Test Cases

**Feature:** Workspace Data Isolation
**Priority:** Critical
**Related Docs:** [Tenancy Enforcement](../07_saas_tenancy_enforcement.md)

---

## Overview

These tests verify that workspace data is completely isolated. **Failure of any of these tests is a security incident.**

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── ID: workspace-a-uuid
├── Owner: alice@acme.test
├── Posts: post-a1, post-a2, post-a3
├── Social Accounts: social-a1, social-a2
└── Inbox Items: inbox-a1, inbox-a2

WORKSPACE B ("Beta Brand")
├── ID: workspace-b-uuid
├── Owner: bob@beta.test
├── Posts: post-b1, post-b2
├── Social Accounts: social-b1
└── Inbox Items: inbox-b1

SHARED USER
└── shared@test.com (Editor in A, Viewer in B)
```

---

## Unit Tests (Codex to implement)

### UT-011-001: Global scope filters by workspace
- **File:** `tests/Unit/Models/Scopes/WorkspaceScopeTest.php`
- **Description:** Verify WorkspaceScope automatically filters queries
- **Test Pattern:**
```php
public function test_workspace_scope_filters_posts(): void
{
    // Set workspace context
    app()->instance('workspace', $workspaceA);

    // Query posts
    $posts = Post::all();

    // Should only return workspace A posts
    $this->assertTrue($posts->every(fn($p) => $p->workspace_id === $workspaceA->id));
}
```
- **Status:** [ ] Pending

### UT-011-002: BelongsToWorkspace trait sets workspace on create
- **File:** `tests/Unit/Models/Concerns/BelongsToWorkspaceTest.php`
- **Description:** Verify workspace_id is auto-set from context
- **Status:** [ ] Pending

### UT-011-003: Cannot create record without workspace context
- **File:** `tests/Unit/Models/Concerns/BelongsToWorkspaceTest.php`
- **Description:** Verify exception thrown if no workspace context
- **Status:** [ ] Pending

### UT-011-004: Workspace context resolver validates membership
- **File:** `tests/Unit/Middleware/WorkspaceContextTest.php`
- **Description:** Verify non-members get 403
- **Status:** [ ] Pending

### UT-011-005: Workspace context resolver validates existence
- **File:** `tests/Unit/Middleware/WorkspaceContextTest.php`
- **Description:** Verify non-existent workspace returns 404
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-011-001: User A cannot list Workspace B posts
- **File:** `tests/Feature/Tenancy/PostIsolationTest.php`
- **Setup:** Alice (Workspace A member)
- **Request:** `GET /v1/workspaces/{workspace_b_id}/posts`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-002: User A cannot access Workspace B post directly
- **File:** `tests/Feature/Tenancy/PostIsolationTest.php`
- **Request:** `GET /v1/workspaces/{workspace_a_id}/posts/{post_b_id}`
- **Expected:** 404 Not Found (post not in workspace A)
- **Status:** [ ] Pending

### IT-011-003: User A cannot create post in Workspace B
- **File:** `tests/Feature/Tenancy/PostIsolationTest.php`
- **Request:** `POST /v1/workspaces/{workspace_b_id}/posts`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-004: User A cannot use Workspace B social account
- **File:** `tests/Feature/Tenancy/PostIsolationTest.php`
- **Request:** Create post in A with target social_account_id from B
- **Expected:** 422 Validation Error or 404
- **Status:** [ ] Pending

### IT-011-005: User A cannot access Workspace B inbox
- **File:** `tests/Feature/Tenancy/InboxIsolationTest.php`
- **Request:** `GET /v1/workspaces/{workspace_b_id}/inbox`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-006: User A cannot reply to Workspace B inbox item
- **File:** `tests/Feature/Tenancy/InboxIsolationTest.php`
- **Request:** `POST /v1/workspaces/{workspace_a_id}/inbox/{inbox_b_id}/reply`
- **Expected:** 404 Not Found
- **Status:** [ ] Pending

### IT-011-007: User A cannot view Workspace B analytics
- **File:** `tests/Feature/Tenancy/AnalyticsIsolationTest.php`
- **Request:** `GET /v1/workspaces/{workspace_b_id}/analytics`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-008: User A cannot access Workspace B team members
- **File:** `tests/Feature/Tenancy/TeamIsolationTest.php`
- **Request:** `GET /v1/workspaces/{workspace_b_id}/members`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-009: User A cannot invite to Workspace B
- **File:** `tests/Feature/Tenancy/TeamIsolationTest.php`
- **Request:** `POST /v1/workspaces/{workspace_b_id}/invitations`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-010: User A cannot access Workspace B subscription
- **File:** `tests/Feature/Tenancy/BillingIsolationTest.php`
- **Request:** `GET /v1/workspaces/{workspace_b_id}/subscription`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-011-011: Shared user sees only Workspace A data when in A context
- **File:** `tests/Feature/Tenancy/SharedUserTest.php`
- **Setup:** Shared user, query Workspace A
- **Request:** `GET /v1/workspaces/{workspace_a_id}/posts`
- **Expected:** Only Workspace A posts returned
- **Status:** [ ] Pending

### IT-011-012: Shared user sees only Workspace B data when in B context
- **File:** `tests/Feature/Tenancy/SharedUserTest.php`
- **Setup:** Shared user, query Workspace B
- **Request:** `GET /v1/workspaces/{workspace_b_id}/posts`
- **Expected:** Only Workspace B posts returned
- **Status:** [ ] Pending

### IT-011-013: Shared user role enforced per workspace
- **File:** `tests/Feature/Tenancy/SharedUserTest.php`
- **Setup:** Shared user is Editor in A, Viewer in B
- **Test:** Can create posts in A, cannot create in B
- **Status:** [ ] Pending

### IT-011-014: ID manipulation attack prevented
- **File:** `tests/Feature/Tenancy/SecurityTest.php`
- **Request:** `GET /v1/workspaces/{workspace_a_id}/posts/{post_b_id}`
- **Expected:** 404 (not 403, to not leak existence)
- **Status:** [ ] Pending

### IT-011-015: Workspace ID in body ignored (use URL)
- **File:** `tests/Feature/Tenancy/SecurityTest.php`
- **Request:** POST to A with `workspace_id: B` in body
- **Expected:** Post created in A (URL takes precedence)
- **Status:** [ ] Pending

---

## Background Job Tests (Codex to implement)

### JT-011-001: PublishPostJob validates workspace
- **File:** `tests/Feature/Jobs/PublishPostJobTest.php`
- **Test:** Job with wrong workspace_id fails
- **Status:** [ ] Pending

### JT-011-002: SyncInboxJob scoped to workspace
- **File:** `tests/Feature/Jobs/SyncInboxJobTest.php`
- **Test:** Job only creates items for its workspace
- **Status:** [ ] Pending

### JT-011-003: GenerateReportJob scoped to workspace
- **File:** `tests/Feature/Jobs/GenerateReportJobTest.php`
- **Test:** Report only contains workspace data
- **Status:** [ ] Pending

### JT-011-004: Job fails if workspace suspended
- **File:** `tests/Feature/Jobs/WorkspaceValidationTest.php`
- **Test:** Jobs for suspended workspaces fail gracefully
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-011-001: Multi-workspace user switches correctly
- **File:** `tests/e2e/tenancy/workspace-switch.spec.ts`
- **Steps:**
  1. Login as shared user
  2. View Workspace A dashboard (see A data)
  3. Switch to Workspace B
  4. Verify sees B data only
- **Status:** [ ] Pending

### E2E-011-002: Direct URL to other workspace blocked
- **File:** `tests/e2e/tenancy/url-protection.spec.ts`
- **Steps:**
  1. Login as Alice (only in A)
  2. Navigate directly to /workspaces/B/posts
  3. Verify 403 error page
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-011-001: Database spot check - no cross-workspace references
- **Steps:**
  1. Query posts table
  2. For each post, verify foreign keys point to same workspace
  3. Check social_account_id belongs to post's workspace
- **SQL:**
```sql
SELECT p.id, p.workspace_id, sa.workspace_id as social_ws
FROM post_targets pt
JOIN posts p ON pt.post_id = p.id
JOIN social_accounts sa ON pt.social_account_id = sa.id
WHERE p.workspace_id != sa.workspace_id;
-- Should return 0 rows
```
- **Status:** [ ] Not tested

### MT-011-002: Audit log includes workspace context
- **Steps:**
  1. Perform various actions
  2. Check audit_logs table
  3. Verify workspace_id populated correctly
- **Status:** [ ] Not tested

### MT-011-003: Log files don't leak cross-workspace data
- **Steps:**
  1. Review application logs
  2. Verify workspace_id in log context
  3. Verify no data from other workspaces visible
- **Status:** [ ] Not tested

### MT-011-004: API responses don't leak workspace IDs
- **Steps:**
  1. Make various API calls
  2. Verify response doesn't include other workspace IDs
  3. Verify error messages don't reveal workspace existence
- **Status:** [ ] Not tested

### MT-011-005: Calendar view only shows workspace posts
- **Steps:**
  1. Login to Workspace A
  2. View calendar
  3. Verify only A posts shown
  4. Verify no B posts appear even with URL manipulation
- **Status:** [ ] Not tested

### MT-011-006: Analytics aggregations are workspace-scoped
- **Steps:**
  1. View Workspace A analytics
  2. Verify totals match A data only
  3. Cross-reference with database counts
- **Status:** [ ] Not tested

### MT-011-007: Report exports contain only workspace data
- **Steps:**
  1. Generate report in Workspace A
  2. Download and review
  3. Verify no Workspace B data present
- **Status:** [ ] Not tested

### MT-011-008: Search results are workspace-scoped
- **Steps:**
  1. Search for term that exists in both workspaces
  2. Verify only current workspace results
- **Status:** [ ] Not tested

### MT-011-009: Browser developer tools inspection
- **Steps:**
  1. Open Network tab
  2. Perform actions in Workspace A
  3. Inspect all API responses
  4. Verify no B workspace data in any response
- **Status:** [ ] Not tested

### MT-011-010: Concurrent user sessions isolated
- **Steps:**
  1. Login as Alice (Workspace A) in browser 1
  2. Login as Bob (Workspace B) in browser 2
  3. Perform parallel actions
  4. Verify no data leakage between sessions
- **Status:** [ ] Not tested

---

## Security Tests (Claude to execute)

### ST-011-001: SQL injection cannot bypass workspace filter
- **Attack:** Inject SQL in filter parameters
- **Test:** `GET /v1/workspaces/A/posts?filter[status]=DRAFT' OR workspace_id='B'`
- **Expected:** No Workspace B data returned
- **Status:** [ ] Not tested

### ST-011-002: Parameter pollution cannot bypass workspace
- **Attack:** Send multiple workspace_id parameters
- **Test:** `GET /v1/workspaces/A/posts?workspace_id=B`
- **Expected:** URL workspace (A) is used
- **Status:** [ ] Not tested

### ST-011-003: JWT manipulation cannot change workspace access
- **Attack:** Modify JWT claims
- **Expected:** Token rejected if tampered
- **Status:** [ ] Not tested

### ST-011-004: Timing attack resistance
- **Attack:** Measure response time for existent vs non-existent workspaces
- **Expected:** Similar response times (no timing leak)
- **Status:** [ ] Not tested

### ST-011-005: Mass assignment cannot set workspace_id
- **Attack:** Include workspace_id in POST body to different workspace
- **Expected:** Workspace from URL used, body ignored
- **Status:** [ ] Not tested

---

## Database Verification Queries

```sql
-- 1. Verify no orphaned posts (posts without valid workspace)
SELECT COUNT(*) FROM posts p
LEFT JOIN workspaces w ON p.workspace_id = w.id
WHERE w.id IS NULL;
-- Expected: 0

-- 2. Verify post targets reference same workspace
SELECT COUNT(*) FROM post_targets pt
JOIN posts p ON pt.post_id = p.id
JOIN social_accounts sa ON pt.social_account_id = sa.id
WHERE p.workspace_id != sa.workspace_id;
-- Expected: 0

-- 3. Verify inbox items belong to correct workspace
SELECT COUNT(*) FROM inbox_items ii
JOIN social_accounts sa ON ii.social_account_id = sa.id
WHERE ii.workspace_id != sa.workspace_id;
-- Expected: 0

-- 4. Verify approval decisions reference same workspace posts
SELECT COUNT(*) FROM approval_decisions ad
JOIN posts p ON ad.post_id = p.id
JOIN workspace_memberships wm ON ad.decided_by_user_id = wm.user_id
WHERE p.workspace_id != wm.workspace_id;
-- Expected: 0

-- 5. Count records per workspace (sanity check)
SELECT workspace_id, COUNT(*) as post_count FROM posts GROUP BY workspace_id;
SELECT workspace_id, COUNT(*) as inbox_count FROM inbox_items GROUP BY workspace_id;
```

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 5 | - | - | 5 |
| Integration | 15 | - | - | 15 |
| Job Tests | 4 | - | - | 4 |
| E2E | 2 | - | - | 2 |
| Manual | 10 | - | - | 10 |
| Security | 5 | - | - | 5 |
| **Total** | **41** | **-** | **-** | **41** |

---

## Incident Response

If any multi-tenancy test fails:

1. **STOP** - Halt all testing
2. **ISOLATE** - Identify scope of data exposure
3. **REPORT** - Escalate immediately
4. **FIX** - Priority 1 bug fix
5. **VERIFY** - Re-run all tenancy tests
6. **AUDIT** - Check for actual data leakage in logs

---

**Last Updated:** February 2026
**Status:** Draft
**Classification:** Security Critical
