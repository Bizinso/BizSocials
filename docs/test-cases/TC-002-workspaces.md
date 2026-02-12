# TC-002: Workspace Management Test Cases

**Feature:** Workspace Creation & Management
**Priority:** Critical
**Related Docs:** [API Contract - Workspaces](../04_phase1_api_contract.md), [Tenancy Enforcement](../07_saas_tenancy_enforcement.md)

---

## Overview

Tests for workspace CRUD operations, settings management, and workspace lifecycle (creation, suspension, deletion).

---

## Test Environment Setup

```
USER ALICE (alice@test.com)
├── Owns: Workspace A ("Acme Agency")
└── Member of: Workspace C (as Editor)

USER BOB (bob@test.com)
├── Owns: Workspace B ("Beta Brand")
└── No access to: Workspace A

USER CHARLIE (charlie@test.com)
└── No workspaces (new user)
```

---

## Unit Tests (Codex to implement)

### UT-002-001: Workspace slug generation
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify slug is auto-generated from name
- **Test Pattern:**
```php
public function test_slug_is_generated_from_name(): void
{
    $workspace = Workspace::factory()->create(['name' => 'Acme Agency']);
    $this->assertEquals('acme-agency', $workspace->slug);
}
```
- **Status:** [ ] Pending

### UT-002-002: Workspace slug uniqueness
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify duplicate names get unique slugs
- **Test Pattern:**
```php
public function test_duplicate_names_get_unique_slugs(): void
{
    $ws1 = Workspace::factory()->create(['name' => 'Acme Agency']);
    $ws2 = Workspace::factory()->create(['name' => 'Acme Agency']);
    $this->assertNotEquals($ws1->slug, $ws2->slug);
}
```
- **Status:** [ ] Pending

### UT-002-003: Workspace owner is auto-assigned on creation
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify creating user becomes owner
- **Status:** [ ] Pending

### UT-002-004: Workspace status transitions
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify valid status transitions (ACTIVE → SUSPENDED → ACTIVE, ACTIVE → DELETED)
- **Status:** [ ] Pending

### UT-002-005: Workspace cannot transition from DELETED
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify DELETED workspaces cannot be reactivated
- **Status:** [ ] Pending

### UT-002-006: Workspace settings JSON validation
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify settings schema is validated
- **Status:** [ ] Pending

### UT-002-007: Workspace timezone validation
- **File:** `tests/Unit/Requests/WorkspaceRequestTest.php`
- **Description:** Verify only valid timezones accepted
- **Status:** [ ] Pending

### UT-002-008: Workspace plan limits accessor
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify plan limits are correctly retrieved
- **Status:** [ ] Pending

### UT-002-009: Workspace member count calculation
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify member count is accurate
- **Status:** [ ] Pending

### UT-002-010: Workspace social account count
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify social account count respects plan limits
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-002-001: Create workspace success
- **File:** `tests/Feature/Api/V1/Workspaces/CreateWorkspaceTest.php`
- **Endpoint:** `POST /v1/workspaces`
- **Request:**
```json
{
  "name": "New Agency",
  "timezone": "America/New_York"
}
```
- **Expected:** 201 Created, workspace object returned, creator is owner
- **Status:** [ ] Pending

### IT-002-002: Create workspace - missing name
- **File:** `tests/Feature/Api/V1/Workspaces/CreateWorkspaceTest.php`
- **Request:** `{ "timezone": "UTC" }`
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-002-003: Create workspace - invalid timezone
- **File:** `tests/Feature/Api/V1/Workspaces/CreateWorkspaceTest.php`
- **Request:** `{ "name": "Test", "timezone": "Invalid/Zone" }`
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-002-004: List user's workspaces
- **File:** `tests/Feature/Api/V1/Workspaces/ListWorkspacesTest.php`
- **Endpoint:** `GET /v1/workspaces`
- **Setup:** User is member of 2 workspaces
- **Expected:** 200 OK, returns only user's workspaces
- **Status:** [ ] Pending

### IT-002-005: Get workspace details
- **File:** `tests/Feature/Api/V1/Workspaces/GetWorkspaceTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}`
- **Expected:** 200 OK, full workspace details including settings
- **Status:** [ ] Pending

### IT-002-006: Get workspace - non-member
- **File:** `tests/Feature/Api/V1/Workspaces/GetWorkspaceTest.php`
- **Setup:** Bob tries to access Workspace A
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-002-007: Update workspace - owner
- **File:** `tests/Feature/Api/V1/Workspaces/UpdateWorkspaceTest.php`
- **Endpoint:** `PATCH /v1/workspaces/{workspace_id}`
- **Request:** `{ "name": "Updated Name" }`
- **Expected:** 200 OK, workspace updated
- **Status:** [ ] Pending

### IT-002-008: Update workspace - admin
- **File:** `tests/Feature/Api/V1/Workspaces/UpdateWorkspaceTest.php`
- **Setup:** Admin user updates workspace
- **Expected:** 200 OK
- **Status:** [ ] Pending

### IT-002-009: Update workspace - editor (forbidden)
- **File:** `tests/Feature/Api/V1/Workspaces/UpdateWorkspaceTest.php`
- **Setup:** Editor tries to update workspace settings
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-002-010: Update workspace - viewer (forbidden)
- **File:** `tests/Feature/Api/V1/Workspaces/UpdateWorkspaceTest.php`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-002-011: Delete workspace - owner
- **File:** `tests/Feature/Api/V1/Workspaces/DeleteWorkspaceTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}`
- **Expected:** 200 OK, workspace soft-deleted
- **Status:** [ ] Pending

### IT-002-012: Delete workspace - non-owner (forbidden)
- **File:** `tests/Feature/Api/V1/Workspaces/DeleteWorkspaceTest.php`
- **Setup:** Admin (not owner) tries to delete
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-002-001: Complete workspace creation flow
- **File:** `tests/e2e/workspaces/create-workspace.spec.ts`
- **Steps:**
  1. Login as new user
  2. Click "Create Workspace"
  3. Fill in name, timezone
  4. Submit form
  5. Verify redirect to new workspace dashboard
  6. Verify workspace appears in workspace selector
- **Status:** [ ] Pending

### E2E-002-002: Workspace settings update
- **File:** `tests/e2e/workspaces/workspace-settings.spec.ts`
- **Steps:**
  1. Login as workspace owner
  2. Navigate to Settings
  3. Update workspace name
  4. Save changes
  5. Verify name updated in UI
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-002-001: Workspace creation limits
- **Steps:**
  1. Check user's plan workspace limit
  2. Create workspaces up to limit
  3. Attempt to create one more
  4. Verify appropriate error message
- **Status:** [ ] Not tested

### MT-002-002: Workspace switching
- **Steps:**
  1. Login as user with multiple workspaces
  2. Verify workspace selector shows all workspaces
  3. Switch between workspaces
  4. Verify data changes correctly
- **Status:** [ ] Not tested

### MT-002-003: Suspended workspace access
- **Steps:**
  1. Suspend a workspace (via admin or billing failure)
  2. Login as workspace member
  3. Verify read-only access message
  4. Verify cannot create/edit content
- **Status:** [ ] Not tested

### MT-002-004: Deleted workspace cleanup
- **Steps:**
  1. Delete a workspace
  2. Verify workspace no longer in user's list
  3. Verify direct URL access returns 404
  4. Verify data is soft-deleted in database
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-002-001: Workspace ID enumeration prevention
- **Attack:** Iterate through workspace IDs
- **Expected:** 403 for non-member, not 404 (consistent response)
- **Status:** [ ] Not tested

### ST-002-002: Cannot forge workspace ownership
- **Attack:** Include `owner_id` in create request
- **Expected:** Field ignored, creator becomes owner
- **Status:** [ ] Not tested

### ST-002-003: Workspace slug injection
- **Attack:** Include SQL/XSS in workspace name
- **Expected:** Properly escaped in slug, no injection
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 10 | - | - | 10 |
| Integration | 12 | - | - | 12 |
| E2E | 2 | - | - | 2 |
| Manual | 4 | - | - | 4 |
| Security | 3 | - | - | 3 |
| **Total** | **31** | **-** | **-** | **31** |

---

**Last Updated:** February 2026
**Status:** Draft
