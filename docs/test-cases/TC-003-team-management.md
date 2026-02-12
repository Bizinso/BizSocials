# TC-003: Team Management Test Cases

**Feature:** Workspace Team & Invitations
**Priority:** High
**Related Docs:** [API Contract - Team](../04_phase1_api_contract.md)

---

## Overview

Tests for workspace memberships, role management, invitations, and team member operations.

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── Owner: alice@acme.test
├── Admin: admin@acme.test
├── Editor: editor@acme.test
└── Viewer: viewer@acme.test

PENDING INVITATIONS
├── pending@test.com (invited to A as Editor)
└── expired@test.com (expired invitation to A)

NON-MEMBER
└── outsider@test.com (not in any workspace)
```

---

## Unit Tests (Codex to implement)

### UT-003-001: WorkspaceMembership role validation
- **File:** `tests/Unit/Models/WorkspaceMembershipTest.php`
- **Description:** Verify only valid roles (OWNER, ADMIN, EDITOR, VIEWER) accepted
- **Status:** [ ] Pending

### UT-003-002: Invitation token generation
- **File:** `tests/Unit/Models/WorkspaceInvitationTest.php`
- **Description:** Verify unique token is generated
- **Test Pattern:**
```php
public function test_invitation_token_is_unique(): void
{
    $inv1 = WorkspaceInvitation::factory()->create();
    $inv2 = WorkspaceInvitation::factory()->create();
    $this->assertNotEquals($inv1->token, $inv2->token);
    $this->assertEquals(64, strlen($inv1->token));
}
```
- **Status:** [ ] Pending

### UT-003-003: Invitation expiry calculation
- **File:** `tests/Unit/Models/WorkspaceInvitationTest.php`
- **Description:** Verify invitation expires in 7 days
- **Status:** [ ] Pending

### UT-003-004: Invitation status transitions
- **File:** `tests/Unit/Models/WorkspaceInvitationTest.php`
- **Description:** Verify valid transitions (PENDING → ACCEPTED, PENDING → EXPIRED, PENDING → REVOKED)
- **Status:** [ ] Pending

### UT-003-005: Role hierarchy check
- **File:** `tests/Unit/Services/TeamServiceTest.php`
- **Description:** Verify role hierarchy (OWNER > ADMIN > EDITOR > VIEWER)
- **Status:** [ ] Pending

### UT-003-006: Cannot demote self below current role
- **File:** `tests/Unit/Services/TeamServiceTest.php`
- **Description:** Verify admins cannot make themselves viewers
- **Status:** [ ] Pending

### UT-003-007: Membership workspace scoping
- **File:** `tests/Unit/Models/WorkspaceMembershipTest.php`
- **Description:** Verify membership queries are workspace-scoped
- **Status:** [ ] Pending

### UT-003-008: Duplicate invitation prevention
- **File:** `tests/Unit/Services/InvitationServiceTest.php`
- **Description:** Verify cannot invite already-invited email
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-003-001: List workspace members
- **File:** `tests/Feature/Api/V1/Team/ListMembersTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/members`
- **Expected:** 200 OK, list of members with roles
- **Status:** [ ] Pending

### IT-003-002: List members - non-member forbidden
- **File:** `tests/Feature/Api/V1/Team/ListMembersTest.php`
- **Setup:** Outsider tries to list Workspace A members
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-003-003: Create invitation - owner
- **File:** `tests/Feature/Api/V1/Team/InvitationTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/invitations`
- **Request:**
```json
{
  "email": "newuser@test.com",
  "role": "EDITOR"
}
```
- **Expected:** 201 Created, invitation sent
- **Status:** [ ] Pending

### IT-003-004: Create invitation - admin
- **File:** `tests/Feature/Api/V1/Team/InvitationTest.php`
- **Expected:** 201 Created (admins can invite)
- **Status:** [ ] Pending

### IT-003-005: Create invitation - editor forbidden
- **File:** `tests/Feature/Api/V1/Team/InvitationTest.php`
- **Expected:** 403 Forbidden (editors cannot invite)
- **Status:** [ ] Pending

### IT-003-006: Create invitation - cannot invite as owner
- **File:** `tests/Feature/Api/V1/Team/InvitationTest.php`
- **Request:** `{ "email": "new@test.com", "role": "OWNER" }`
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-003-007: Create invitation - duplicate email
- **File:** `tests/Feature/Api/V1/Team/InvitationTest.php`
- **Setup:** User already has pending invitation
- **Expected:** 422 "Invitation already pending"
- **Status:** [ ] Pending

### IT-003-008: Create invitation - existing member
- **File:** `tests/Feature/Api/V1/Team/InvitationTest.php`
- **Setup:** Email already a workspace member
- **Expected:** 422 "User is already a member"
- **Status:** [ ] Pending

### IT-003-009: Accept invitation - valid token
- **File:** `tests/Feature/Api/V1/Team/AcceptInvitationTest.php`
- **Endpoint:** `POST /v1/invitations/{token}/accept`
- **Expected:** 200 OK, user added to workspace
- **Status:** [ ] Pending

### IT-003-010: Accept invitation - expired token
- **File:** `tests/Feature/Api/V1/Team/AcceptInvitationTest.php`
- **Expected:** 410 Gone "Invitation has expired"
- **Status:** [ ] Pending

### IT-003-011: Accept invitation - already accepted
- **File:** `tests/Feature/Api/V1/Team/AcceptInvitationTest.php`
- **Expected:** 410 Gone "Invitation already used"
- **Status:** [ ] Pending

### IT-003-012: Accept invitation - creates user if needed
- **File:** `tests/Feature/Api/V1/Team/AcceptInvitationTest.php`
- **Setup:** Invited email not registered
- **Expected:** Redirect to registration with invitation context
- **Status:** [ ] Pending

### IT-003-013: Revoke invitation
- **File:** `tests/Feature/Api/V1/Team/RevokeInvitationTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}/invitations/{invitation_id}`
- **Expected:** 200 OK, invitation revoked
- **Status:** [ ] Pending

### IT-003-014: Update member role - owner
- **File:** `tests/Feature/Api/V1/Team/UpdateMemberTest.php`
- **Endpoint:** `PATCH /v1/workspaces/{workspace_id}/members/{user_id}`
- **Request:** `{ "role": "ADMIN" }`
- **Expected:** 200 OK, role updated
- **Status:** [ ] Pending

### IT-003-015: Update member role - cannot change owner
- **File:** `tests/Feature/Api/V1/Team/UpdateMemberTest.php`
- **Setup:** Try to change owner's role
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-003-016: Remove member - owner removes editor
- **File:** `tests/Feature/Api/V1/Team/RemoveMemberTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}/members/{user_id}`
- **Expected:** 200 OK, member removed
- **Status:** [ ] Pending

### IT-003-017: Remove member - cannot remove owner
- **File:** `tests/Feature/Api/V1/Team/RemoveMemberTest.php`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-003-018: Remove member - cannot remove self as owner
- **File:** `tests/Feature/Api/V1/Team/RemoveMemberTest.php`
- **Expected:** 422 "Cannot remove yourself as owner"
- **Status:** [ ] Pending

### IT-003-019: Leave workspace - non-owner
- **File:** `tests/Feature/Api/V1/Team/LeaveWorkspaceTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/leave`
- **Expected:** 200 OK, membership removed
- **Status:** [ ] Pending

### IT-003-020: Leave workspace - owner forbidden
- **File:** `tests/Feature/Api/V1/Team/LeaveWorkspaceTest.php`
- **Expected:** 422 "Owner cannot leave. Transfer ownership first."
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-003-001: Complete invitation flow
- **File:** `tests/e2e/team/invitation-flow.spec.ts`
- **Steps:**
  1. Login as workspace owner
  2. Navigate to Team Settings
  3. Click "Invite Member"
  4. Enter email and select role
  5. Submit invitation
  6. Verify invitation appears in pending list
- **Status:** [ ] Pending

### E2E-003-002: Accept invitation as new user
- **File:** `tests/e2e/team/accept-invitation.spec.ts`
- **Steps:**
  1. Open invitation link (from email)
  2. Complete registration
  3. Verify redirected to workspace
  4. Verify correct role assigned
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-003-001: Invitation email content
- **Steps:**
  1. Create an invitation
  2. Check email received
  3. Verify email contains: inviter name, workspace name, accept link
  4. Verify link works
- **Status:** [ ] Not tested

### MT-003-002: Role permissions in UI
- **Steps:**
  1. Login as Viewer
  2. Verify cannot see Team Settings
  3. Login as Editor
  4. Verify cannot invite members
  5. Login as Admin
  6. Verify can invite but not delete workspace
- **Status:** [ ] Not tested

### MT-003-003: Member list pagination
- **Steps:**
  1. Create workspace with 50+ members
  2. Verify pagination works
  3. Verify search/filter works
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-003-001: Invitation token brute force
- **Attack:** Attempt to guess invitation tokens
- **Expected:** Tokens are cryptographically random, rate limiting applied
- **Status:** [ ] Not tested

### ST-003-002: Role escalation via API
- **Attack:** Editor tries to update own role to Admin
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

### ST-003-003: Cross-workspace invitation token use
- **Attack:** Use invitation token from Workspace A in Workspace B context
- **Expected:** Token only valid for its workspace
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 8 | - | - | 8 |
| Integration | 20 | - | - | 20 |
| E2E | 2 | - | - | 2 |
| Manual | 3 | - | - | 3 |
| Security | 3 | - | - | 3 |
| **Total** | **36** | **-** | **-** | **36** |

---

**Last Updated:** February 2026
**Status:** Draft
