# BizSocials — SaaS Tenancy Enforcement Rules

**Version:** 1.1
**Status:** MANDATORY — All developers must read before coding
**Date:** February 2026
**Classification:** Security & Compliance Critical

---

## 1. Purpose

This document defines the **non-negotiable tenancy rules** for BizSocials. These rules ensure complete data isolation between workspaces (tenants) and prevent cross-tenant data leakage.

**Violating these rules is a security incident.**

---

## 2. Core Tenancy Principle

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│   WORKSPACE = TENANT                                                │
│                                                                     │
│   Every piece of business data belongs to exactly ONE workspace.    │
│   Cross-workspace data access is IMPOSSIBLE by design.              │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Tenancy Scope by Entity

### 3.1 Workspace-Scoped Entities (Tenant Data)

These entities MUST always be accessed within workspace context:

| Entity | Tenant Column | Notes |
|--------|---------------|-------|
| WorkspaceMembership | workspace_id | User-workspace link |
| Invitation | workspace_id | Pending invites |
| SocialAccount | workspace_id | Connected platforms |
| Post | workspace_id | Content |
| PostMedia | (via Post) | Implicit via post_id |
| PostTarget | (via Post) | Implicit via post_id |
| ApprovalDecision | (via Post) | Implicit via post_id |
| InboxItem | workspace_id | Engagement |
| InboxReply | (via InboxItem) | Implicit via inbox_item_id |
| PostMetricSnapshot | (via PostTarget) | Implicit via post_target_id |
| ReportExport | workspace_id | Generated reports |
| Subscription | workspace_id | Billing |
| Invoice | (via Subscription) | Implicit via subscription_id |
| AiSuggestionLog | workspace_id | AI usage |
| AuditLog | workspace_id | Activity log |

### 3.2 User-Scoped Entities (Cross-Workspace)

These entities belong to users, not workspaces:

| Entity | Scope | Notes |
|--------|-------|-------|
| User | Global | Can belong to multiple workspaces |
| PasswordResetToken | User | Not workspace-specific |
| EmailVerificationToken | User | Not workspace-specific |
| Notification | User (with workspace context) | Delivered to user, references workspace |

### 3.3 System-Scoped Entities (Global)

| Entity | Scope | Notes |
|--------|-------|-------|
| Plan | System | Shared across all workspaces |

---

## 4. Mandatory Query Rules

### 4.1 Rule: Every Write Query MUST Include Workspace Scope

**WRONG:**
```
INSERT INTO posts (content_text, created_by_user_id) VALUES (...)
```

**CORRECT:**
```
INSERT INTO posts (workspace_id, content_text, created_by_user_id) VALUES (...)
```

### 4.2 Rule: Every Read Query MUST Filter by Workspace

**WRONG:**
```
SELECT * FROM posts WHERE id = :post_id
```

**CORRECT:**
```
SELECT * FROM posts WHERE id = :post_id AND workspace_id = :workspace_id
```

### 4.3 Rule: Updates and Deletes MUST Include Workspace Scope

**WRONG:**
```
UPDATE posts SET status = 'APPROVED' WHERE id = :post_id
```

**CORRECT:**
```
UPDATE posts SET status = 'APPROVED' WHERE id = :post_id AND workspace_id = :workspace_id
```

### 4.4 Rule: JOIN Queries Must Not Leak Across Tenants

**WRONG:**
```
SELECT p.*, s.account_name
FROM posts p
JOIN social_accounts s ON s.id = p.target_account_id
WHERE p.id = :post_id
-- Missing workspace filter on both tables!
```

**CORRECT:**
```
SELECT p.*, s.account_name
FROM posts p
JOIN social_accounts s ON s.id = p.target_account_id AND s.workspace_id = :workspace_id
WHERE p.id = :post_id AND p.workspace_id = :workspace_id
```

---

## 5. API Authorization Rules

### 5.1 Workspace Access Decision Tree

```
Request to /v1/workspaces/{workspace_id}/...
                    │
                    ▼
        ┌─────────────────────┐
        │ Is user authenticated?│
        └──────────┬──────────┘
                   │
          No ──────┴────── Yes
          │                 │
          ▼                 ▼
    ┌──────────┐   ┌─────────────────────┐
    │   401    │   │ Does workspace exist?│
    │UNAUTHED  │   └──────────┬──────────┘
    └──────────┘              │
                     No ──────┴────── Yes
                     │                 │
                     ▼                 ▼
               ┌──────────┐   ┌─────────────────────┐
               │   404    │   │ Is user a member?   │
               │NOT FOUND │   └──────────┬──────────┘
               └──────────┘              │
                                No ──────┴────── Yes
                                │                 │
                                ▼                 ▼
                          ┌──────────┐   ┌─────────────────────┐
                          │   403    │   │ Does role permit    │
                          │FORBIDDEN │   │ this action?        │
                          └──────────┘   └──────────┬──────────┘
                                                    │
                                           No ──────┴────── Yes
                                           │                 │
                                           ▼                 ▼
                                     ┌──────────┐     ┌──────────┐
                                     │   403    │     │ PROCEED  │
                                     │FORBIDDEN │     │          │
                                     └──────────┘     └──────────┘
```

### 5.2 Critical: 403 vs 404 Behavior

| Scenario | Response | Rationale |
|----------|----------|-----------|
| Workspace doesn't exist | 404 NOT_FOUND | Resource genuinely missing |
| Workspace exists, user not a member | 403 FORBIDDEN | Prevents enumeration attacks |
| Workspace exists, user is member, wrong role | 403 FORBIDDEN | Permission denied |

**Security Note:** Never return 404 for existing workspaces to non-members. This leaks information about which workspaces exist.

---

## 6. Background Job Tenancy Rules

### 6.1 Mandatory Rule: Jobs MUST Carry Workspace Context

**Every queued job MUST receive `workspace_id` as a required parameter.**

**WRONG:**
```
dispatch(new PublishPostJob($postId));
```

**CORRECT:**
```
dispatch(new PublishPostJob($workspaceId, $postId));
```

### 6.2 Job Implementation Requirements

Every job class MUST:

1. Accept `workspace_id` as constructor parameter
2. Validate workspace exists and is active before processing
3. Scope all queries to that workspace
4. Log workspace_id in all log entries
5. Never cross workspace boundaries

### 6.3 Job Types and Tenancy

| Job | Workspace Source | Validation |
|-----|------------------|------------|
| PublishPostJob | From post lookup | Verify post.workspace_id matches |
| SyncInboxJob | Explicit parameter | Verify social_account belongs to workspace |
| FetchMetricsJob | From post_target lookup | Verify chain back to workspace |
| GenerateReportJob | Explicit parameter | Verify user has access to workspace |
| SendNotificationJob | From notification lookup | Use notification.workspace_id for context |

### 6.4 Scheduled Jobs (Cron-Triggered)

Jobs triggered by scheduler (not user action) must:

1. Query for eligible workspaces
2. Dispatch individual jobs PER WORKSPACE
3. Never process multiple workspaces in single job execution

**Example: Scheduled Publishing**
```
// Scheduler triggers this every minute
// DO NOT: Fetch all due posts across all workspaces and process together
// DO:
1. Find all posts WHERE scheduled_at <= now AND status = 'SCHEDULED'
2. Group by workspace_id
3. Dispatch one PublishPostJob per post with its workspace_id
```

---

## 7. Data Isolation Verification Checklist

Use this checklist during code review:

### 7.1 For Every New Endpoint
- [ ] Workspace ID extracted from URL path
- [ ] User membership in workspace verified
- [ ] Role permission checked
- [ ] All queries include workspace_id filter
- [ ] Response contains only data from that workspace

### 7.2 For Every New Query
- [ ] WHERE clause includes workspace_id
- [ ] JOINs don't leak cross-tenant data
- [ ] Subqueries are workspace-scoped

### 7.3 For Every New Background Job
- [ ] workspace_id is a required constructor parameter
- [ ] Workspace is validated in handle() method
- [ ] All queries within job are workspace-scoped
- [ ] Logs include workspace_id for debugging

### 7.4 For Every New Model/Entity
- [ ] If workspace-scoped: has workspace_id column
- [ ] If workspace-scoped: has workspace relationship defined
- [ ] Query scopes/traits enforce workspace filtering

---

## 8. Global Query Scope (Implementation Pattern)

### 8.1 Recommended Approach

Implement a workspace scope that auto-applies to workspace-scoped models:

**Concept (not code):**
```
When workspace context is set (from authenticated request):
- All workspace-scoped models automatically filter by workspace_id
- Attempting to access data from another workspace throws exception
- This is defense-in-depth, not replacement for explicit filtering
```

### 8.2 Defense in Depth

Tenancy is enforced at multiple layers:

| Layer | Enforcement |
|-------|-------------|
| API Middleware | Validates membership, sets workspace context |
| Model/Repository | Global scope filters by workspace_id |
| Database (optional) | Row-level security policies |
| Background Jobs | Explicit workspace_id parameter |
| Logging | workspace_id in every log entry |

---

## 9. Forbidden Patterns

### 9.1 Never Do These

| Pattern | Why It's Wrong |
|---------|----------------|
| Query without workspace filter | Data leakage risk |
| Job without workspace parameter | Cannot enforce isolation |
| Admin "super" mode that bypasses tenancy | Attack vector |
| Sharing data between workspaces | Violates isolation principle |
| Caching data without workspace key | Cache poisoning across tenants |
| Global counters across workspaces | Information leakage |

### 9.2 Exception: System-Wide Admin Panel

If a system admin panel is needed later (for support):
- It must be a SEPARATE application
- Different authentication
- Different database user with explicit cross-tenant permission
- Full audit logging of every access
- **NOT in scope for Phase-1**

---

## 10. Workspace Context Resolver (Implementation Pattern)

The **Workspace Context Resolver** is the central mechanism that enforces tenancy throughout the request lifecycle. This names the pattern you must implement.

### 10.1 What It Does

```
┌─────────────────────────────────────────────────────────────────────┐
│                    WORKSPACE CONTEXT RESOLVER                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. EXTRACT    → Gets workspace_id from route parameter             │
│  2. VALIDATE   → Confirms workspace exists and is active            │
│  3. AUTHORIZE  → Verifies user is a member with sufficient role     │
│  4. BIND       → Sets workspace context for entire request          │
│  5. PROPAGATE  → Makes context available to models, jobs, logs      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 10.2 Where It Runs

| Layer | Usage |
|-------|-------|
| **Middleware** | Runs on every `/workspaces/{workspace_id}/*` route |
| **Models** | Global scope auto-filters by resolved workspace |
| **Jobs** | Receives workspace_id, re-resolves on execution |
| **Logs** | Automatically includes workspace_id in every entry |
| **Exceptions** | Includes workspace context in error reports |

### 10.3 Context Availability

Once resolved, workspace context is available via:
- Request helper: `currentWorkspace()` or `workspace()`
- Direct access: `$request->workspace`
- Model scope: Auto-applied to workspace-scoped models

### 10.4 Jobs Must Re-Resolve

Background jobs execute outside the HTTP request. They must:
1. Accept `workspace_id` as constructor parameter
2. Re-resolve workspace in `handle()` method
3. Fail if workspace no longer exists or is suspended

---

## 11. Testing Requirements

### 11.1 Multi-Tenant Test Scenarios

Every feature must be tested with:
- Two workspaces (Workspace A, Workspace B)
- User who is member of Workspace A only
- Verify user CANNOT access Workspace B data via any path

### 11.2 Test Cases for Every Endpoint

| Test | Expected Result |
|------|-----------------|
| Access own workspace | 200 OK with correct data |
| Access workspace user doesn't belong to | 403 FORBIDDEN |
| Access non-existent workspace | 404 NOT_FOUND |
| Query with manipulated ID from another workspace | 403 or 404, never data |

### 11.3 Background Job Tests

| Test | Expected Result |
|------|-----------------|
| Job receives correct workspace_id | Processes correctly |
| Job receives wrong workspace_id | Fails validation |
| Job data doesn't match workspace | Throws exception |

---

## 12. Local Multi-Tenant Test Recipe

Use this setup when testing locally to verify tenant isolation.

### 12.1 Test Data Setup

```
┌─────────────────────────────────────────────────────────────────────┐
│                         TEST ENVIRONMENT                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  WORKSPACE A ("Acme Agency")          WORKSPACE B ("Beta Brand")   │
│  ├── Owner: alice@test.com            ├── Owner: bob@test.com      │
│  ├── Admin: adrian@test.com           ├── Editor: eve@test.com     │
│  ├── Editor: emma@test.com            └── Social: @BetaOnFB        │
│  ├── Viewer: victor@test.com                                       │
│  └── Social: @AcmeOnFB, @AcmeOnIG                                  │
│                                                                     │
│  SHARED USER (belongs to both):                                    │
│  └── shared@test.com (Editor in A, Viewer in B)                    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 12.2 Isolation Test Checklist

Run these tests after every major feature:

| Test | Actor | Action | Expected |
|------|-------|--------|----------|
| **Posts** | alice@test.com | List posts in Workspace A | See Acme posts only |
| **Posts** | alice@test.com | GET /workspaces/{B_id}/posts | 403 Forbidden |
| **Posts** | alice@test.com | GET /workspaces/{B_id}/posts/{B_post_id} | 403 Forbidden |
| **Inbox** | bob@test.com | List inbox in Workspace B | See Beta comments only |
| **Inbox** | bob@test.com | Reply to Acme inbox item | 403 Forbidden |
| **Analytics** | shared@test.com | View Workspace A analytics | See Acme metrics |
| **Analytics** | shared@test.com | View Workspace B analytics | See Beta metrics |
| **Analytics** | shared@test.com | Export A report with B post IDs | Filtered out / error |
| **Jobs** | System | Publish Acme post | Only Acme tokens used |
| **Jobs** | System | Sync Beta inbox | Only Beta items created |

### 12.3 ID Manipulation Tests

Attempt to access resources by manipulating IDs:

| Test | Expected |
|------|----------|
| User A requests: `GET /workspaces/A/posts/B_POST_ID` | 404 (post not in workspace A) |
| User A requests: `POST /workspaces/A/posts/A_POST_ID/targets` with B's social_account_id | 400/404 (account not in workspace) |
| User A requests: `GET /workspaces/B/inbox/B_ITEM_ID` | 403 (not member of B) |

### 12.4 Database Verification

After running test scenarios, verify in database:
- No Workspace B post_ids appear in Workspace A tables
- No cross-workspace foreign key references exist
- Audit logs show correct workspace_id for each action

---

## 13. Incident Response

### 13.1 If Cross-Tenant Data Access Is Detected

1. **STOP** — Halt the affected feature immediately
2. **ASSESS** — Determine scope of data exposure
3. **FIX** — Patch the vulnerability
4. **AUDIT** — Review logs for actual exposure
5. **NOTIFY** — If customer data was exposed, follow breach protocol

### 13.2 This Is Not Paranoia

Multi-tenant SaaS data leakage is:
- A trust violation
- Potentially a compliance violation (GDPR, SOC2)
- A business-ending event if severe

---

## 14. Summary: The Three Laws of BizSocials Tenancy

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  1. A workspace's data SHALL NOT be accessible to other workspaces  │
│                                                                     │
│  2. Every query SHALL include workspace scope, unless it is         │
│     explicitly user-scoped or system-scoped                         │
│                                                                     │
│  3. Every background job SHALL carry and validate workspace_id      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 15. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Solution Architecture | Initial tenancy rules |
| 1.1 | Feb 2026 | Solution Architecture | Added: Workspace Context Resolver pattern (§10); Local Multi-Tenant Test Recipe (§12) |

---

**END OF SAAS TENANCY ENFORCEMENT RULES**
