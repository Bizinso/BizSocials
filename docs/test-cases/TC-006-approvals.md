# TC-006: Approval Workflow Test Cases

**Feature:** Post Approval System
**Priority:** High
**Related Docs:** [API Contract - Approvals](../04_phase1_api_contract.md)

---

## Overview

Tests for single-level approval workflow, including approval requests, decisions, and notification flow. Phase-1 supports single-level approval only.

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency") - Approvals ENABLED
├── Approval Settings:
│   └── require_approval_for: ["EDITOR"]
│
├── Members:
│   ├── Owner: alice@acme.test (approver)
│   ├── Admin: admin@acme.test (approver)
│   ├── Editor: editor@acme.test (requires approval)
│   └── Viewer: viewer@acme.test
│
└── Posts:
    ├── post-pending: Editor's post, PENDING_APPROVAL
    ├── post-approved: Approved, SCHEDULED
    └── post-rejected: Rejected, DRAFT

WORKSPACE B - Approvals DISABLED
└── Members:
    └── Editor: editor-b@test.com (no approval needed)
```

---

## Unit Tests (Codex to implement)

### UT-006-001: ApprovalDecision status validation
- **File:** `tests/Unit/Models/ApprovalDecisionTest.php`
- **Description:** Verify valid statuses (APPROVED, REJECTED, REVISION_REQUESTED)
- **Status:** [ ] Pending

### UT-006-002: Approval required check
- **File:** `tests/Unit/Services/ApprovalServiceTest.php`
- **Description:** Verify approval requirement based on workspace settings and user role
- **Test Pattern:**
```php
public function test_editor_requires_approval_when_enabled(): void
{
    $workspace = Workspace::factory()->create([
        'settings' => ['require_approval_for' => ['EDITOR']]
    ]);
    $editor = User::factory()->create();
    $workspace->members()->attach($editor, ['role' => 'EDITOR']);

    $this->assertTrue($this->approvalService->requiresApproval($workspace, $editor));
}

public function test_admin_does_not_require_approval(): void
{
    $workspace = Workspace::factory()->create([
        'settings' => ['require_approval_for' => ['EDITOR']]
    ]);
    $admin = User::factory()->create();
    $workspace->members()->attach($admin, ['role' => 'ADMIN']);

    $this->assertFalse($this->approvalService->requiresApproval($workspace, $admin));
}
```
- **Status:** [ ] Pending

### UT-006-003: Post transitions to PENDING_APPROVAL
- **File:** `tests/Unit/Services/ApprovalServiceTest.php`
- **Description:** Verify post status changes when editor schedules
- **Status:** [ ] Pending

### UT-006-004: Approval decision records approver
- **File:** `tests/Unit/Models/ApprovalDecisionTest.php`
- **Description:** Verify decided_by_user_id is recorded
- **Status:** [ ] Pending

### UT-006-005: Approval comment storage
- **File:** `tests/Unit/Models/ApprovalDecisionTest.php`
- **Description:** Verify comments are stored with decision
- **Status:** [ ] Pending

### UT-006-006: Rejection requires reason
- **File:** `tests/Unit/Services/ApprovalServiceTest.php`
- **Description:** Verify rejection without reason fails
- **Status:** [ ] Pending

### UT-006-007: Cannot approve own post
- **File:** `tests/Unit/Services/ApprovalServiceTest.php`
- **Description:** Verify post author cannot approve their own post
- **Status:** [ ] Pending

### UT-006-008: Approval workspace scoping
- **File:** `tests/Unit/Models/ApprovalDecisionTest.php`
- **Description:** Verify approvals are scoped to workspace
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-006-001: Submit post for approval (auto)
- **File:** `tests/Feature/Api/V1/Approvals/SubmitForApprovalTest.php`
- **Setup:** Editor in workspace with approvals enabled
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts` with schedule
- **Expected:** 201 Created, status = PENDING_APPROVAL
- **Status:** [ ] Pending

### IT-006-002: Submit for approval - approvals disabled
- **File:** `tests/Feature/Api/V1/Approvals/SubmitForApprovalTest.php`
- **Setup:** Editor in workspace without approvals
- **Expected:** 201 Created, status = SCHEDULED (bypasses approval)
- **Status:** [ ] Pending

### IT-006-003: Submit for approval - admin bypasses
- **File:** `tests/Feature/Api/V1/Approvals/SubmitForApprovalTest.php`
- **Setup:** Admin submits post
- **Expected:** 201 Created, status = SCHEDULED (no approval needed)
- **Status:** [ ] Pending

### IT-006-004: List pending approvals
- **File:** `tests/Feature/Api/V1/Approvals/ListPendingTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/approvals/pending`
- **Expected:** 200 OK, list of posts pending approval
- **Status:** [ ] Pending

### IT-006-005: List pending approvals - viewer forbidden
- **File:** `tests/Feature/Api/V1/Approvals/ListPendingTest.php`
- **Setup:** Viewer tries to list
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-006-006: Approve post
- **File:** `tests/Feature/Api/V1/Approvals/ApprovePostTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/approve`
- **Request:** `{ "comment": "Looks good!" }`
- **Expected:** 200 OK, post status = SCHEDULED
- **Status:** [ ] Pending

### IT-006-007: Approve post - editor forbidden
- **File:** `tests/Feature/Api/V1/Approvals/ApprovePostTest.php`
- **Setup:** Editor tries to approve
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-006-008: Approve own post forbidden
- **File:** `tests/Feature/Api/V1/Approvals/ApprovePostTest.php`
- **Setup:** Admin tries to approve their own post
- **Expected:** 422 "Cannot approve your own post"
- **Status:** [ ] Pending

### IT-006-009: Reject post
- **File:** `tests/Feature/Api/V1/Approvals/RejectPostTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/reject`
- **Request:** `{ "reason": "Please revise the headline" }`
- **Expected:** 200 OK, post status = DRAFT
- **Status:** [ ] Pending

### IT-006-010: Reject post - reason required
- **File:** `tests/Feature/Api/V1/Approvals/RejectPostTest.php`
- **Request:** `{}` (no reason)
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-006-011: Request revision
- **File:** `tests/Feature/Api/V1/Approvals/RequestRevisionTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/request-revision`
- **Request:** `{ "comment": "Can you add more hashtags?" }`
- **Expected:** 200 OK, post status = DRAFT with revision request
- **Status:** [ ] Pending

### IT-006-012: Get post approval history
- **File:** `tests/Feature/Api/V1/Approvals/ApprovalHistoryTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts/{post_id}/approval-history`
- **Expected:** 200 OK, list of approval decisions
- **Status:** [ ] Pending

### IT-006-013: Re-submit after rejection
- **File:** `tests/Feature/Api/V1/Approvals/ResubmitTest.php`
- **Setup:** Previously rejected post
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/submit-for-approval`
- **Expected:** 200 OK, status = PENDING_APPROVAL again
- **Status:** [ ] Pending

### IT-006-014: Update workspace approval settings
- **File:** `tests/Feature/Api/V1/Approvals/SettingsTest.php`
- **Endpoint:** `PATCH /v1/workspaces/{workspace_id}`
- **Request:** `{ "settings": { "require_approval_for": ["EDITOR", "VIEWER"] } }`
- **Expected:** 200 OK, settings updated
- **Status:** [ ] Pending

### IT-006-015: Approval notification sent
- **File:** `tests/Feature/Api/V1/Approvals/NotificationTest.php`
- **Description:** Verify approvers receive notification when post submitted
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-006-001: Editor submit and admin approve flow
- **File:** `tests/e2e/approvals/approval-flow.spec.ts`
- **Steps:**
  1. Login as editor
  2. Create and schedule post
  3. Verify pending approval status shown
  4. Logout, login as admin
  5. View pending approvals
  6. Approve post
  7. Verify post now scheduled
- **Status:** [ ] Pending

### E2E-006-002: Rejection and resubmit flow
- **File:** `tests/e2e/approvals/rejection-flow.spec.ts`
- **Steps:**
  1. Admin rejects post with reason
  2. Editor receives notification
  3. Editor edits post
  4. Editor resubmits
  5. Admin approves
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-006-001: Approval email notifications
- **Steps:**
  1. Editor submits post for approval
  2. Check admin's email
  3. Verify notification received
  4. Verify email contains post preview and action links
- **Status:** [ ] Not tested

### MT-006-002: In-app approval notifications
- **Steps:**
  1. Submit post for approval
  2. Check notification bell for approvers
  3. Verify unread count increments
  4. Click notification, verify links to post
- **Status:** [ ] Not tested

### MT-006-003: Approval badge in calendar
- **Steps:**
  1. View calendar with pending approval posts
  2. Verify visual indicator for pending status
  3. Click post, verify approval actions available
- **Status:** [ ] Not tested

### MT-006-004: Bulk approval
- **Steps:**
  1. Have multiple posts pending
  2. Select multiple posts
  3. Bulk approve
  4. Verify all statuses updated
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-006-001: Cannot approve cross-workspace post
- **Attack:** Admin in Workspace A tries to approve Workspace B post
- **Expected:** 404 Not Found
- **Status:** [ ] Not tested

### ST-006-002: Role escalation via approval
- **Attack:** Editor manipulates request to bypass approval
- **Expected:** Server-side role check enforced
- **Status:** [ ] Not tested

### ST-006-003: Approval forgery
- **Attack:** Forge approval decision timestamp
- **Expected:** Server sets timestamp, cannot be overridden
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 8 | - | - | 8 |
| Integration | 15 | - | - | 15 |
| E2E | 2 | - | - | 2 |
| Manual | 4 | - | - | 4 |
| Security | 3 | - | - | 3 |
| **Total** | **32** | **-** | **-** | **32** |

---

**Last Updated:** February 2026
**Status:** Draft
