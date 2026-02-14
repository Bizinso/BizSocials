BizSocials — User Flow Execution Tracker (MASTER)
This tracker is the single source of truth.
If a row is not ✅ DONE with evidence, the feature does not exist.
Tracker Columns (LOCK THIS STRUCTURE)
Phase	Flow ID	User Flow Name	Screen(s)	API(s)	DB Entities	Status	Tests	Audit	Notes
Status values (STRICT):
•	❌ Not Started
•	🟡 Partial (spec/code exists but not wired)
•	🟢 Complete (meets DoD)

BizSocials — User Flow Execution Tracker (MASTER)
PHASE 0 — SAAS DELIVERY FOUNDATION
Rule reminder
If a row is not 🟢 Complete with evidence, the feature does NOT exist.
 
🔹 Phase 0.1 — Tenant, Workspace & Org Model
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Dependencies	Status	Tests	Audit	DoD Verified	Notes
0	0.1.1	Tenant Creation Wizard	/register, /verify-email, /onboarding/setup	POST /auth/register, GET /auth/verify-email, POST /onboarding/organization	tenants, tenant_profiles, tenant_onboarding, tenant_usage, users	ResolveTenant fix, TenantCreated event	❌	❌	❌	❌	Fully specified, blocked by infra fixes
0	0.1.2	Workspace Creation	/onboarding/workspace, /settings/workspaces	POST /workspaces, GET /workspaces	workspaces, workspace_memberships	Tenant must exist, onboarding active	❌	❌	❌	❌	Auto-create first workspace + manual creation later
0	0.1.3	Team Creation	/settings/teams	CRUD /teams	teams, team_members	Workspace must exist	❌	❌	❌	❌	Logical grouping inside workspace
0	0.1.4	Role & Permission Management	/settings/roles	CRUD /roles, /permissions	roles, permissions, role_permission_map	Tenant + workspace context	❌	❌	❌	❌	Core RBAC foundation
0	0.1.5	“Who Can Do What” Visibility	/settings/permissions	GET /permissions/matrix	roles, permissions	Roles + permissions exist	❌	❌	❌	❌	UX transparency, reduces support load
 
🔹 Phase 0.2 — Authentication & Security
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Dependencies	Status	Tests	Audit	DoD Verified	Notes
0	0.2.1	Registration	/register	POST /auth/register	users, tenants	Email service	🟡	🟡	🟡	❌	Exists but incomplete (no org info, onboarding broken)
0	0.2.2	Email Verification	/verify-email	GET /auth/verify-email	users	Registration flow	🟡	🟡	🟡	❌	Redirect logic broken
0	0.2.3	Login	/login	POST /auth/login	users, tokens	None	🟢	🟢	🟢	🟢	Correct and stable
0	0.2.4	MFA Setup & Management	/settings/security	POST /auth/mfa/*	user_mfa	Authenticated user	🟡	❌	❌	❌	Backend exists, UX partial
0	0.2.5	Session Management	/settings/security/sessions	GET /sessions, DELETE /sessions/{id}	user_sessions	Authenticated user	🟡	❌	❌	❌	Incomplete UI + audit
0	0.2.6	Forgot / Reset Password	/forgot-password, /reset-password	POST /auth/forgot-password, POST /auth/reset-password	password_resets	Email service	❌	❌	❌	❌	Mandatory SaaS baseline
 
🔹 Phase 0.3 — Super Admin Platform Console
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Dependencies	Status	Tests	Audit	DoD Verified	Notes
0	0.3.1	Platform Dashboard	/admin/dashboard	GET /admin/dashboard	tenants, tenant_usage	Super admin auth	🟡	❌	❌	❌	Metrics visible, UX basic
0	0.3.2	Tenant Detail View	/admin/tenants/:id	GET /admin/tenants/{id}	tenants, users	Dashboard	🟡	❌	❌	❌	Read-only inspection
0	0.3.3	Tenant Suspend / Activate	—	POST /admin/tenants/{id}/suspend	tenants	Tenant detail	🟢	🟢	🟢	🟢	Correct, needs guardrails
0	0.3.4	Read-only Impersonation	—	POST /admin/impersonate	users, tokens	Super admin auth	🟡	❌	❌	❌	Must enforce RO strictly
0	0.3.5	Integration Health Board	/admin/integrations	GET /admin/integrations	logs, integrations	Event logging	❌	❌	❌	❌	Required before Phase 3+


 
PHASE 0 — SAAS DELIVERY FOUNDATION (TRACKER)
Phase 0.1 — Tenant, Workspace, Org Model
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Status	Tests	Audit	Notes
0	0.1.1	Tenant Creation Wizard	/onboarding/org	POST /tenants	tenants	❌	❌	❌	Blocking
0	0.1.2	Workspace Creation	/onboarding/workspace	POST /workspaces	workspaces	❌	❌	❌	Blocking
0	0.1.3	Team Creation	/settings/teams	CRUD /teams	teams, team_members	❌	❌	❌	Required
0	0.1.4	Role & Permission Mgmt	/settings/roles	CRUD /roles	roles, permissions	❌	❌	❌	Critical
0	0.1.5	“Who can do what” View	/settings/permissions	GET /permissions	roles, permissions	❌	❌	❌	UX gap
 
Phase 0.2 — Authentication & Security
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Status
0	0.2.1	Registration	/register	POST /auth/register	users	🟡
0	0.2.2	Email Verification	/verify-email	POST /auth/verify	users	🟡
0	0.2.3	Login	/login	POST /auth/login	users, tokens	🟢
0	0.2.4	MFA Setup	/settings/security	POST /auth/mfa	user_mfa	🟡
0	0.2.5	Session Management	/settings/security	GET /sessions	user_sessions	🟡
 
Phase 0.3 — Super Admin Platform Console
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Status
0	0.3.1	Platform Dashboard	/admin	GET /admin/dashboard	tenants	🟡
0	0.3.2	Tenant Detail View	/admin/tenants/:id	GET /admin/tenants/{id}	tenants	🟡
0	0.3.3	Tenant Suspend	—	POST /admin/tenants/{id}/suspend	tenants	🟢
0	0.3.4	Impersonation (RO)	—	POST /admin/impersonate	users	🟡
0	0.3.5	Integration Health	/admin/integrations	GET /admin/integrations	logs	❌
 
2️⃣ PHASE 0 — SCREEN-BY-SCREEN + API SPEC
Below is Phase 0.1.1 in full depth.
All other flows follow this exact structure.
 
FLOW 0.1.1 — Tenant Creation Wizard
🎯 Purpose
Create the legal + billing + compliance boundary for a customer.
 
UI SCREENS
Screen 1: Organization Setup
URL: /onboarding/org
Fields
•	Organization Name (required)
•	Logo (optional)
•	Timezone (required)
•	Industry (dropdown)
•	Country (required)
Validation
•	Org name unique per tenant
•	Timezone valid
•	Country required
Errors
•	Duplicate tenant name
•	Validation inline
•	Retry allowed
 
Screen 2: Workspace Setup
URL: /onboarding/workspace
Fields
•	Workspace Name
•	Purpose (Marketing / Support / Brand / Agency)
•	Default Approval Mode (auto / manual)
 
Screen 3: Confirmation
•	Summary view
•	“Create Organization” CTA
 
API SPEC
POST /api/v1/tenants
Request
{
  "name": "Acme Inc",
  "timezone": "Asia/Kolkata",
  "industry": "Retail",
  "country": "IN"
}
Response
{
  "tenant_id": "uuid",
  "status": "created"
}
Validations
•	Authenticated user
•	One tenant per user (initial)
•	Rate-limited
 
DATABASE
Table: tenants
•	id (uuid)
•	name
•	timezone
•	country
•	plan_id
•	status
•	created_at
 
AUDIT LOG
Event:
•	tenant.created
Payload:
•	user_id
•	tenant_id
•	ip
•	timestamp
 
TEST CASES
Unit
•	TenantService::create()
•	Duplicate tenant rejection
Integration
•	Tenant + Workspace created atomically
•	Rollback on failure
E2E
•	User completes wizard
•	Tenant appears in admin panel
 
DEFINITION OF DONE (DoD)
✔ Tenant exists
✔ Workspace auto-created
✔ Audit entry written
✔ UI handles retry
✔ Tests pass
 
3️⃣ CLAUDE PROMPTS (REUSABLE & SAFE)
MASTER SYSTEM PROMPT (USE ALWAYS)
You are working on BizSocials.

You must:
- Follow the BizSocials World-Class SaaS Implementation Plan
- Follow the BizSocials User Flow Execution Tracker
- Use only MIT / BSD / Apache-2.0 / ISC licensed libraries
- Respect multi-tenancy, workspace isolation, auditability

Do NOT assume functionality.
If something is missing, stop and flag it.
 
PHASE PROMPT TEMPLATE
Phase: <PHASE NUMBER + NAME>

Objective:
Complete all user flows in this phase as per tracker.

Rules:
- One user flow at a time
- Screen-by-screen
- API-by-API
- Tests required before marking DONE

Start with:
Flow ID: <ID>
Flow Name: <NAME>
 
FLOW PROMPT TEMPLATE (MOST IMPORTANT)
User Flow: <Flow ID + Name>

Tasks:
1. Describe the user journey step-by-step
2. List UI screens and components
3. Define API endpoints (request/response)
4. Define DB entities
5. Define validations & error states
6. Define audit events
7. Write unit test cases
8. Write E2E test cases

Stop if dependencies are missing.
 
FINAL EXECUTION RULES (LOCK THESE)
•	❌ No jumping phases
•	❌ No partial DONE
•	❌ No UI without API
•	❌ No API without audit
•	❌ No flow without tests

MASTER STARTING PROMPT FOR CLAUDE — BIZSOCIALS
You are working on a production-grade, multi-tenant SaaS platform called BizSocials.

IMPORTANT CONTEXT (READ CAREFULLY):

BizSocials is NOT a prototype.
It is a long-term, world-class SaaS product.

Your role is NOT to “build fast”.
Your role is to help COMPLETE the product correctly, safely, and verifiably.

You must strictly follow these constraints:

────────────────────────────────
A. CORE RULES (NON-NEGOTIABLE)
────────────────────────────────

1. DO NOT assume any functionality exists.
2. DO NOT infer behavior from file names or architecture docs.
3. Mark something as “implemented” ONLY if:
   - It is wired end-to-end
   - It produces real side effects (DB/UI/API)
   - It is reachable from a real user flow
4. If anything is missing, unclear, or partially implemented:
   - STOP
   - Explicitly flag it as a blocker
   - Do NOT silently work around it

────────────────────────────────
B. PRODUCT & ARCHITECTURE PRINCIPLES
────────────────────────────────

You MUST follow these BizSocials principles:

- BizSocials owns the full user experience
- No raw OAuth dumps
- Every integration uses guided wizards
- Everything configurable at Tenant + Workspace level
- Limits and compliance must be visible BEFORE failure
- Every action must be auditable, reversible, and recoverable
- Super Admin has full observability but no tenant data leakage

────────────────────────────────
C. LEGAL & LICENSING CONSTRAINTS
────────────────────────────────

You may ONLY use or suggest:
- MIT
- BSD (2-clause or 3-clause)
- Apache-2.0
- ISC

If no such option exists:
- Explicitly say: “NO SAFE OPEN-SOURCE OPTION AVAILABLE”

Do NOT suggest:
- GPL / AGPL
- SSPL
- BSL
- Elastic License
- Any non-commercial or source-available licenses

────────────────────────────────
D. DEVELOPMENT DISCIPLINE
────────────────────────────────

Work strictly USER-FLOW FIRST.

For every user flow you touch, you MUST produce:

1. Step-by-step user journey
2. Screen-by-screen UI specification
3. API-by-API contract (request/response)
4. Database entities involved
5. Validation rules
6. Error & edge states
7. Audit log events
8. Unit test cases
9. E2E (Playwright-style) test cases

A flow is NOT COMPLETE unless all 9 exist.

────────────────────────────────
E. CURRENT EXECUTION PLAN (LOCKED)
────────────────────────────────

We are following this order strictly:

PHASE 0 — SaaS Delivery Foundation (MANDATORY)
Nothing beyond Phase 0 may be implemented until Phase 0 is complete.

Phase 0 includes:
- Tenant creation
- Workspace creation
- Teams
- Roles & permissions
- Authentication & security
- Super Admin platform console

We are also using a User Flow Execution Tracker.
If a flow is not marked COMPLETE with evidence, it does not exist.

────────────────────────────────
F. YOUR FIRST TASK (START HERE)
────────────────────────────────

START WITH:

Phase: 0 — SaaS Delivery Foundation
Flow ID: 0.1.1
Flow Name: Tenant Creation Wizard

Your task:

1. Describe the COMPLETE user journey for Tenant Creation.
2. Define all UI screens (URLs, fields, validations).
3. Define all backend APIs (routes, payloads, responses).
4. Define database tables & fields involved.
5. Define validation and error scenarios.
6. Define audit log events.
7. Define unit test cases.
8. Define E2E test cases.
9. Explicitly list any missing dependencies or blockers.

IMPORTANT:
- Do NOT write code yet.
- Do NOT skip steps.
- Do NOT move to the next flow.
- End your response with a clear checklist titled:
  “Is Flow 0.1.1 READY TO IMPLEMENT? (Yes / No + reasons)”

Begin now.


✅ BizSocials — Flow 0.1.2: Workspace Creation
Phase: 0 — SaaS Delivery Foundation
Flow ID: 0.1.2
Flow Name: Workspace Creation
 
🎯 PURPOSE (LOCK THIS)
A Workspace represents an operational boundary inside a Tenant.
Examples:
•	Brand
•	Region
•	Department
•	Client (agency use-case)
•	Channel grouping (Marketing vs Support)
Everything in BizSocials (posts, inbox, analytics, WhatsApp, billing usage)
must belong to exactly one Workspace.
 
🧑‍💼 ACTORS
•	Primary: Tenant Owner / Tenant Admin
•	Secondary: Super Admin (read-only inspection only)
 
✅ PRECONDITIONS
•	Tenant exists
•	Tenant status = ACTIVE
•	User is:
o	Tenant OWNER or ADMIN
•	Tenant onboarding:
o	profile_completed = true
•	ResolveTenant middleware correctly binds current_tenant
 
❌ INVALID STATES (HARD BLOCK)
•	Tenant is SUSPENDED
•	User is MEMBER / VIEWER
•	Tenant onboarding not completed
•	Workspace limit reached (plan-based)
 
🧭 COMPLETE USER JOURNEY
ENTRY POINTS
1.	Onboarding flow
o	After Organization Setup (Flow 0.1.1)
2.	Settings
o	/settings/workspaces
3.	Top-bar Workspace Switcher
o	“➕ Create Workspace”
 
STEP 1 — Workspace Setup (Screen 1)
User fills workspace details.
System validates uniqueness & limits.
 
STEP 2 — Confirmation (Screen 2)
User reviews summary.
Clicks “Create Workspace”.
 
STEP 3 — Post Creation State
System:
•	Creates workspace
•	Adds creator as OWNER
•	Initializes workspace defaults
User is redirected to:
•	Workspace dashboard
 
🖥️ UI — SCREEN-BY-SCREEN SPEC
 
🟦 Screen 1: Workspace Creation
URL: /onboarding/workspace or /settings/workspaces/new
Fields
Field	Type	Required	Validation	Notes
Workspace Name	text	Yes	min:2, max:100	Unique per tenant
Workspace Type	select	Yes	enum	marketing, support, brand, agency, custom
Purpose / Description	textarea	No	max:255	Informational
Default Approval Mode	select	Yes	auto / manual	Content governance
Default Timezone	select	No	valid tz	Defaults to tenant timezone
Default Language	select	No	ISO code	For content & inbox
Set as Default Workspace	toggle	No	boolean	First workspace = true
 
UI RULES
•	Inline validation
•	Slug preview shown under name
•	Workspace limit warning (before submit)
•	“Cancel” returns to previous page
 
ERROR STATES
Scenario	UX Behavior
Duplicate name	Inline error
Workspace limit reached	Blocking modal + upgrade CTA
Network error	Retry banner
Permission denied	“You don’t have access”
 
🟦 Screen 2: Confirmation
URL: same (modal or stepper)
Shows:
•	Workspace name
•	Type
•	Approval mode
•	Default settings
CTA:
•	Create Workspace
 
🔌 API — COMPLETE CONTRACT
 
API 1: Create Workspace
Route: POST /api/v1/workspaces
Auth: Sanctum
Rate Limit: 20/min/user
Request
{
  "name": "Marketing Team",
  "type": "marketing",
  "description": "Handles all organic social content",
  "approval_mode": "manual",
  "timezone": "Asia/Kolkata",
  "language": "en",
  "is_default": false
}
 
Validation Rules
Field	Rules
name	required, string, min:2, max:100
type	required, in:marketing,support,brand,agency,custom
approval_mode	required, in:auto,manual
timezone	nullable, timezone
language	nullable, size:2
is_default	boolean
 
Authorization Rules
•	User role ∈ {OWNER, ADMIN}
•	Tenant status = ACTIVE
•	Workspace count < plan limit
 
Side Effects (ATOMIC TRANSACTION)
1.	Create workspace
2.	Generate unique slug (tenant-scoped)
3.	Create workspace_membership
o	user = creator
o	role = OWNER
4.	If is_default = true
o	Unset previous default
5.	Initialize workspace settings
6.	Fire WorkspaceCreated event
7.	Write audit log
 
Response (201 Created)
{
  "success": true,
  "message": "Workspace created successfully.",
  "data": {
    "workspace": {
      "id": "uuid",
      "name": "Marketing Team",
      "slug": "marketing-team",
      "type": "marketing",
      "approval_mode": "manual",
      "is_default": false
    }
  }
}
 
Error Responses
Code	Condition
401	Not authenticated
403	Insufficient role
409	Duplicate workspace name
422	Validation error
429	Rate limit
402	Workspace limit reached
 
API 2: List Workspaces
Route: GET /api/v1/workspaces
Returns:
•	All workspaces user is a member of
•	Includes role per workspace
 
API 3: Set Default Workspace
Route: POST /api/v1/workspaces/{id}/default
Rules:
•	Only one default per tenant
 
🗄️ DATABASE ENTITIES
 
Table: workspaces (EXISTS)
Column	Notes
id	UUID
tenant_id	FK
name	Unique per tenant
slug	URL-safe
type	enum
approval_mode	enum
timezone	nullable
language	nullable
is_default	boolean
created_by	user_id
 
Table: workspace_memberships (EXISTS)
Column	Notes
workspace_id	FK
user_id	FK
role	OWNER / ADMIN / MEMBER
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
workspace.created	User	workspace_id, tenant_id
workspace.default_changed	User	old_id, new_id
workspace.creation_failed	System	error reason
 
🧪 UNIT TEST CASES
WorkspaceService
•	✅ creates workspace
•	✅ generates unique slug
•	✅ prevents duplicate name
•	✅ enforces plan limits
•	✅ creates owner membership
•	✅ sets default workspace
•	✅ rolls back on failure
•	✅ blocks non-admin users
 
🌐 E2E (PLAYWRIGHT) TESTS
test('tenant owner creates a workspace', async () => {
  // navigate to /settings/workspaces
  // click "Create Workspace"
  // fill form
  // submit
  // verify workspace appears in switcher
});

test('member cannot create workspace', async () => {
  // login as MEMBER
  // attempt create
  // expect 403 UX
});

test('workspace limit enforced', async () => {
  // reach limit
  // attempt creation
  // expect upgrade modal
});
 
✅ DEFINITION OF DONE (DoD)
✔ Workspace created
✔ Membership created
✔ Audit log written
✔ Visible in workspace switcher
✔ Default logic correct
✔ Unit + E2E tests pass
 
🚧 BLOCKERS & DEPENDENCIES
BLOCKERS
•	ResolveTenant must bind current_tenant
•	Plan workspace limits must exist
DEPENDENCIES
•	Tenant exists
•	User authenticated
•	WorkspaceMembership table verified (done)
 
🔍 FINAL CHECK
Is Flow 0.1.2 READY TO IMPLEMENT?
✅ YES — with 2 prerequisites fixed
1.	ResolveTenant binding
2.	Plan limits enforcement confirmed


✅ BizSocials — Flow 0.1.3: Team Creation
Phase: 0 — SaaS Delivery Foundation
Flow ID: 0.1.3
Flow Name: Team Creation & Management
 
🎯 PURPOSE (LOCK THIS)
A Team is a logical grouping of users inside a Workspace.
Teams are used for:
•	Content ownership
•	Approval routing
•	Inbox assignment
•	WhatsApp agent routing
•	Performance analytics
•	Access scoping (future phases)
Teams do NOT replace roles.
Roles define what you can do.
Teams define where you operate.
 
🧑‍💼 ACTORS
•	Primary: Workspace OWNER / ADMIN
•	Secondary: Workspace MEMBER (read-only visibility)
•	System: BizSocials (audit, validation)
 
✅ PRECONDITIONS
•	Tenant exists and is ACTIVE
•	Workspace exists
•	User is member of workspace
•	User role ∈ {OWNER, ADMIN}
•	ResolveTenant + EnsureWorkspaceMember middleware active
 
❌ INVALID STATES (BLOCKING)
•	Workspace suspended
•	User role = MEMBER / VIEWER
•	Team limit exceeded (plan-based)
•	Duplicate team name within same workspace
 
🧭 COMPLETE USER JOURNEY
ENTRY POINTS
1.	Workspace Settings
/settings/teams
2.	Contextual CTA
o	Assigning team to content / inbox (later phases)
 
STEP 1 — View Teams List
User sees existing teams in the workspace.
 
STEP 2 — Create New Team
User clicks “Create Team”.
Fills team details.
 
STEP 3 — Assign Members (Optional)
User adds members now or later.
 
STEP 4 — Save & Activate
Team becomes immediately usable across the workspace.
 
🖥️ UI — SCREEN-BY-SCREEN SPEC
 
🟦 Screen 1: Teams List
URL: /settings/teams
Table Columns
Column	Description
Team Name	Display name
Description	Optional
Members	Count
Created By	User
Actions	View / Edit / Delete
Actions
•	➕ Create Team
•	Edit
•	Delete (if no dependencies)
•	View members
 
Empty State
•	Icon + text: “No teams yet”
•	CTA: Create your first team
 
🟦 Screen 2: Create / Edit Team
URL: /settings/teams/new
(or modal)
Fields
Field	Type	Required	Validation
Team Name	text	Yes	min:2, max:100
Description	textarea	No	max:255
Default Team	toggle	No	boolean
Assign Members	multi-select	No	workspace users
 
UI RULES
•	Team name uniqueness enforced live
•	Default team = auto-assigned where team not specified
•	Delete disabled if team in use (shown with tooltip)
 
ERROR STATES
Scenario	UX
Duplicate name	Inline error
Permission denied	Blocking alert
Team limit reached	Upgrade modal
Delete blocked	Dependency explanation
 
🔌 API — COMPLETE CONTRACT
 
API 1: Create Team
Route: POST /api/v1/workspaces/{workspace_id}/teams
Auth: Sanctum
Rate Limit: 30/min/user
Request
{
  "name": "Content Creators",
  "description": "Handles post creation",
  "is_default": false,
  "members": ["user_uuid_1", "user_uuid_2"]
}
 
Validation Rules
Field	Rules
name	required, string, min:2, max:100
description	nullable, max:255
is_default	boolean
members	array of valid workspace user IDs
 
Authorization Rules
•	User role ∈ {OWNER, ADMIN}
•	Workspace active
•	Team count < plan limit
 
Side Effects (ATOMIC)
1.	Create team
2.	Generate unique slug (workspace-scoped)
3.	Assign members (if provided)
4.	If is_default = true
o	Unset previous default team
5.	Fire TeamCreated event
6.	Write audit log
 
Response (201 Created)
{
  "success": true,
  "data": {
    "team": {
      "id": "uuid",
      "name": "Content Creators",
      "slug": "content-creators",
      "member_count": 2,
      "is_default": false
    }
  }
}
 
Error Responses
Code	Condition
401	Not authenticated
403	Insufficient permission
409	Duplicate team name
422	Validation error
402	Team limit reached
 
API 2: List Teams
Route: GET /api/v1/workspaces/{workspace_id}/teams
Returns:
•	Teams + member counts
•	Default flag
 
API 3: Update Team
Route: PUT /api/v1/teams/{id}
Rules:
•	Same validations as create
•	Cannot rename if dependencies locked (optional)
 
API 4: Delete Team
Route: DELETE /api/v1/teams/{id}
Rules:
•	Block if team assigned to:
o	Posts
o	Inbox rules
o	WhatsApp routing
•	Require confirmation
 
🗄️ DATABASE ENTITIES
 
Table: teams (EXISTS)
Column	Notes
id	UUID
workspace_id	FK
name	Unique per workspace
slug	URL-safe
description	nullable
is_default	boolean
created_by	user_id
 
Table: team_members (EXISTS)
Column	Notes
team_id	FK
user_id	FK
added_by	user_id
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
team.created	User	team_id, workspace_id
team.updated	User	changed_fields
team.deleted	User	team_id
team.member_added	User	team_id, user_id
team.member_removed	User	team_id, user_id
team.default_changed	User	old_id, new_id
 
🧪 UNIT TEST CASES
TeamService
•	✅ creates team
•	✅ enforces uniqueness
•	✅ assigns members
•	✅ enforces plan limits
•	✅ sets default team
•	✅ blocks unauthorized users
•	✅ prevents delete when in use
•	✅ rolls back on failure
 
🌐 E2E (PLAYWRIGHT) TESTS
test('admin creates a team and assigns members', async () => {
  // go to /settings/teams
  // create team
  // assign members
  // verify team in list
});

test('member cannot create team', async () => {
  // login as MEMBER
  // attempt access
  // expect permission error
});

test('cannot delete team in use', async () => {
  // assign team to content
  // attempt delete
  // expect blocking message
});
 
✅ DEFINITION OF DONE (DoD)
✔ Team created
✔ Members assigned
✔ Default team enforced
✔ Audit events logged
✔ Visible in team selector
✔ Unit + E2E tests pass
 
🚧 BLOCKERS & DEPENDENCIES
BLOCKERS
•	Plan limits must define max teams/workspace
DEPENDENCIES
•	Workspace exists
•	WorkspaceMembership exists
•	AuditLog service exists (verified)


✅ BizSocials — Flow 0.1.4: Role & Permission Management (RBAC)
Phase: 0 — SaaS Delivery Foundation
Flow ID: 0.1.4
Flow Name: Role & Permission Management
Status: ❌ Not Started (Spec Complete after this)
 
🎯 PURPOSE (LOCK THIS)
Provide clear, auditable, predictable access control across BizSocials.
RBAC must:
•	Be understandable by non-technical users
•	Prevent accidental privilege escalation
•	Scale cleanly across future modules (WhatsApp, Analytics, Billing)
•	Avoid per-user permission chaos
Rule:
Permissions are assigned to roles, never directly to users.
 
🧠 CORE RBAC MODEL (DO NOT CHANGE LATER)
Hierarchy (STRICT)
Tenant
 └── Workspace
      └── Role
           └── Permissions
                └── User (via WorkspaceMembership)
Scoping Rules
Item	Scope
Roles	Workspace-scoped
Permissions	Platform-defined
Role assignment	Per workspace
User	Can have different roles in different workspaces
 
🧑‍💼 ACTORS
•	Primary: Workspace OWNER
•	Secondary: Workspace ADMIN
•	Denied: MEMBER / VIEWER
 
✅ PREDEFINED SYSTEM ROLES (LOCK THESE)
These must always exist and cannot be deleted.
Role	Description
OWNER	Full control, billing, security
ADMIN	Operational control
MEMBER	Day-to-day work
VIEWER	Read-only
OWNER is immutable
ADMIN permissions configurable
MEMBER / VIEWER minimal by default
 
🧭 COMPLETE USER JOURNEY
ENTRY POINT
/settings/roles
 
STEP 1 — View Roles
User sees:
•	System roles (locked)
•	Custom roles (if any)
 
STEP 2 — Create Custom Role
User defines:
•	Role name
•	Base role (optional template)
•	Permissions
 
STEP 3 — Assign Role to Users
Roles applied via workspace membership.
 
STEP 4 — Update Role Permissions
Changes apply immediately to all assigned users.
 
STEP 5 — Delete Role (if allowed)
Only if:
•	Not system role
•	Not assigned to any user
 
🖥️ UI — SCREEN-BY-SCREEN SPEC
 
🟦 Screen 1: Roles List
URL: /settings/roles
Table Columns
Column	Description
Role Name	Display
Type	System / Custom
Users	Count
Actions	View / Edit / Delete
UI Rules
•	System roles show 🔒 icon
•	Delete disabled for system roles
•	Tooltip explains restrictions
 
🟦 Screen 2: Create / Edit Role
URL: /settings/roles/new
(or modal)
Fields
Field	Type	Required	Validation
Role Name	text	Yes	min:2, max:50
Clone From	select	No	existing role
Permissions	checkbox matrix	Yes	≥1 permission
 
Permission Matrix UI
Grouped by domain:
▸ Content
  ☐ content.view
  ☐ content.create
  ☐ content.approve
  ☐ content.publish

▸ Inbox
  ☐ inbox.view
  ☐ inbox.reply
  ☐ inbox.assign

▸ WhatsApp
  ☐ whatsapp.view
  ☐ whatsapp.send
  ☐ whatsapp.manage_templates

▸ Analytics
  ☐ analytics.view
  ☐ analytics.export

▸ Settings
  ☐ settings.view
  ☐ settings.manage_roles
 
UX RULES (CRITICAL)
•	OWNER permissions cannot be changed
•	If settings.manage_roles unchecked → user cannot access this screen
•	Warnings shown for dangerous combinations (e.g. publish without approve)
 
🔌 API — COMPLETE CONTRACT
 
API 1: List Roles
Route: GET /api/v1/workspaces/{workspace_id}/roles
Returns:
•	Roles
•	Permissions
•	User count per role
 
API 2: Create Role
Route: POST /api/v1/workspaces/{workspace_id}/roles
Request
{
  "name": "Content Manager",
  "permissions": [
    "content.view",
    "content.create",
    "content.schedule",
    "analytics.view"
  ]
}
 
Validation Rules
Field	Rules
name	required, unique per workspace
permissions	array, valid permission keys
 
Authorization Rules
•	User role ∈ {OWNER, ADMIN}
•	Must have settings.manage_roles
 
Side Effects
1.	Create role
2.	Attach permissions
3.	Fire RoleCreated
4.	Write audit log
 
API 3: Update Role
Route: PUT /api/v1/roles/{role_id}
Rules:
•	Cannot update system roles
•	Cannot remove permissions required by system invariants
 
API 4: Delete Role
Route: DELETE /api/v1/roles/{role_id}
Rules:
•	Block if role assigned
•	Block if system role
 
API 5: Assign Role to User
Route: PUT /api/v1/workspace-members/{id}/role
{
  "role": "admin"
}
 
🗄️ DATABASE ENTITIES
 
Table: roles (EXISTS)
Column	Notes
id	UUID
workspace_id	FK
name	string
is_system	boolean
created_by	user_id
 
Table: permissions (EXISTS)
Column	Notes
key	string (e.g. content.publish)
label	human-readable
domain	content / inbox / whatsapp
 
Table: role_permission_map (EXISTS)
Column	Notes
role_id	FK
permission_key	FK
 
Table: workspace_memberships (EXISTS)
Column	Notes
user_id	FK
role	string → role_id
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
role.created	User	role_id, permissions
role.updated	User	diff
role.deleted	User	role_id
role.assigned	User	user_id, role
role.revoked	User	user_id, role
 
🧪 UNIT TEST CASES
RoleService
•	✅ create role
•	✅ enforce uniqueness
•	✅ attach permissions
•	✅ prevent system role modification
•	✅ prevent deletion when assigned
•	✅ permission validation
•	✅ rollback on failure
 
🌐 E2E (PLAYWRIGHT) TESTS
test('admin creates a custom role', async () => {
  // go to /settings/roles
  // create role
  // assign permissions
  // save
});

test('member cannot access roles screen', async () => {
  // login as MEMBER
  // navigate to /settings/roles
  // expect 403
});

test('system role cannot be deleted', async () => {
  // attempt delete OWNER
  // expect blocked
});
 
✅ DEFINITION OF DONE (DoD)
✔ Roles visible
✔ Permissions enforced
✔ Role assignment works
✔ Audit events logged
✔ Unauthorized access blocked
✔ Unit + E2E tests pass
 
🚧 BLOCKERS & DEPENDENCIES
BLOCKERS
•	Permission catalog must be finalized (keys list)
DEPENDENCIES
•	WorkspaceMembership exists
•	AuditLog exists
•	Permission seeder exists


✅ BizSocials — Flow 0.1.5: “Who Can Do What” Visibility Matrix
Phase: 0 — SaaS Delivery Foundation
Flow ID: 0.1.5
Flow Name: Permission Visibility Matrix
Status: ❌ Not Started (Spec Complete after this)
 
🎯 PURPOSE (LOCK THIS)
Provide clear, read-only visibility into:
•	What each role can do
•	What each user can do (via their role)
•	Where permissions apply (workspace scope)
This flow is:
•	Read-only
•	Non-destructive
•	Non-configurational
No changes happen here.
This screen exists to remove fear, confusion, and mistakes.
 
🧠 CORE PRINCIPLES
1.	Visibility ≠ Control
2.	No permission edits
3.	No user role changes
4.	Zero side effects
If a user wants to change something:
→ Redirect to Role Management (0.1.4)
 
🧑‍💼 ACTORS
Actor	Access
Workspace OWNER	Full view
Workspace ADMIN	Full view
Workspace MEMBER	Read-only view
Workspace VIEWER	Read-only view
Super Admin	Read-only (impersonation mode)
 
✅ PRECONDITIONS
•	Workspace exists
•	User is workspace member
•	Roles + permissions exist
•	RBAC fully functional (Flow 0.1.4)
 
🧭 COMPLETE USER JOURNEY
ENTRY POINTS
1.	Settings → Permissions
/settings/permissions
2.	Inline link from:
o	Roles screen (“View permissions”)
o	Team screen (“Understand access”)
 
STEP 1 — Choose View Mode
User selects:
•	By Role
•	By User
(Default: By Role)
 
STEP 2 — Inspect Permissions
User reviews:
•	Permissions grouped by domain
•	Visual indicators (allowed / denied)
 
STEP 3 — Drill Down (Optional)
User clicks:
•	A permission → sees which roles have it
•	A role → sees which users are affected
 
🖥️ UI — SCREEN-BY-SCREEN SPEC
 
🟦 Screen 1: Permission Matrix
URL: /settings/permissions
 
🔀 Toggle: View Mode
•	🔘 Roles → Permissions (default)
•	🔘 Users → Permissions
 
🟦 Mode A: Roles → Permissions
Layout
Permission ↓ / Role →	Owner	Admin	Member	Viewer
content.view	✅	✅	✅	✅
content.create	✅	✅	✅	❌
content.publish	✅	✅	❌	❌
inbox.reply	✅	✅	✅	❌
billing.manage	✅	❌	❌	❌
 
Visual Rules
•	✅ Allowed
•	❌ Not allowed
•	🔒 System enforced (tooltip)
•	⚠️ Dangerous permission (tooltip)
 
🟦 Mode B: Users → Permissions
User Selector
•	Dropdown or searchable list
•	Shows user + role + workspace
 
Permission Breakdown
User: Anita Sharma
Role: Content Manager

Content
  ✅ View
  ✅ Create
  ❌ Publish

Inbox
  ✅ View
  ❌ Assign

Billing
  ❌ All
 
🟦 Permission Detail Drawer (Optional)
Click any permission:
Shows:
•	Description
•	Why it exists
•	Roles that include it
•	Where it applies
 
🔌 API — COMPLETE CONTRACT
 
API 1: Get Permission Matrix (By Role)
Route:
GET /api/v1/workspaces/{workspace_id}/permissions/matrix
Response
{
  "roles": ["owner", "admin", "member", "viewer"],
  "permissions": [
    {
      "key": "content.publish",
      "label": "Publish content",
      "domain": "content",
      "roles": {
        "owner": true,
        "admin": true,
        "member": false,
        "viewer": false
      },
      "is_system": true,
      "risk_level": "high"
    }
  ]
}
 
API 2: Get User Effective Permissions
Route:
GET /api/v1/workspaces/{workspace_id}/users/{user_id}/permissions
Response
{
  "user": {
    "id": "uuid",
    "name": "Anita Sharma",
    "role": "content_manager"
  },
  "permissions": {
    "content.view": true,
    "content.create": true,
    "content.publish": false,
    "billing.manage": false
  }
}
 
API 3: Permission Catalog (Read-only)
Route:
GET /api/v1/permissions/catalog
Purpose:
•	UI labels
•	Tooltips
•	Domain grouping
•	Risk classification
 
🗄️ DATABASE ENTITIES (READ-ONLY)
Uses existing tables:
•	roles
•	permissions
•	role_permission_map
•	workspace_memberships
🚫 No writes performed
 
📜 AUDIT LOG EVENTS
NONE (INTENTIONAL)
Reason:
•	Visibility-only
•	No state mutation
•	Prevents audit noise
Optional:
•	Page view analytics (non-audit)
 
🧪 UNIT TEST CASES
PermissionQueryService
•	✅ returns role-permission matrix
•	✅ resolves effective permissions per user
•	✅ respects workspace scoping
•	✅ hides tenant-internal permissions
•	✅ blocks cross-workspace access
 
🌐 E2E (PLAYWRIGHT) TESTS
test('admin views permission matrix by role', async () => {
  // navigate to /settings/permissions
  // verify table renders
});

test('member can view but not edit permissions', async () => {
  // login as member
  // verify read-only access
});

test('user permission view matches role definition', async () => {
  // select user
  // verify effective permissions match role
});
 
✅ DEFINITION OF DONE (DoD)
✔ Permissions visible by role
✔ Permissions visible by user
✔ No mutation possible
✔ Correct scoping enforced
✔ Tooltips explain meaning
✔ Unit + E2E tests pass
 
🚧 BLOCKERS & DEPENDENCIES
BLOCKERS
•	Permission catalog (keys + labels + risk level) must be finalized
DEPENDENCIES
•	Flow 0.1.4 complete
•	WorkspaceMembership exists
•	Roles & permissions seeded


✅ BizSocials — Flow 0.2.1: Registration (HARDENED & FINAL)
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.2 — Authentication & Security
Flow ID: 0.2.1
Flow Name: User Registration
Status: 🟡 Partial → ❌ NOT COMPLETE (after this spec: READY TO IMPLEMENT)
This flow is NOT just a form.
It is the root of identity, tenant ownership, security posture, and compliance.
 
🎯 PURPOSE (LOCK THIS)
Allow a new user to safely create:
•	Their identity
•	Their tenant boundary
•	Their initial security posture
While ensuring:
•	No account hijacking
•	No duplicate identities
•	No bypass of onboarding or verification
•	Full auditability
 
🧠 PRINCIPLES APPLIED
✔ Security before convenience
✔ No tenant ambiguity
✔ No silent auto-login without verification
✔ Predictable, deterministic system state
✔ Explicit next steps at all times
 
🧑‍💼 ACTORS
Actor	Description
Anonymous Visitor	Not authenticated
New User	Registering first account
System	Creates tenant + user
Super Admin	Observes via audit (later)
 
✅ PRECONDITIONS
•	User is NOT authenticated
•	Email is NOT already associated with:
o	An active user
o	A pending invitation (important edge case)
•	Registration rate limits not exceeded
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — Registration Form
User provides:
•	Full name
•	Email
•	Password
•	Confirm password
System:
•	Validates inputs
•	Creates User + Tenant (PENDING) in one transaction
•	Sends verification email
•	DOES NOT complete onboarding
•	DOES NOT create workspace yet
➡ Redirects to Verify Email Sent screen
 
STEP 2 — Verify Email Sent Screen
User sees:
•	Clear instruction
•	Resend option
•	Change email option
System:
•	Blocks access to app routes
•	Allows resend with cooldown
 
STEP 3 — Email Verification (Handled in Flow 0.2.2)
This flow ends here.
 
🖥️ UI SPECIFICATION
 
🟦 Screen 1: Registration
URL: /register
Status: EXISTS → MUST BE HARDENED
Fields
Field	Type	Required	Validation
Full Name	text	✅	min 2, max 255
Email	email	✅	valid, unique
Password	password	✅	min 8, strength rules
Confirm Password	password	✅	must match
 
Password Rules (MANDATORY)
•	Min 8 characters
•	At least:
o	1 uppercase
o	1 lowercase
o	1 number
Inline checklist UI required.
 
Actions
•	Primary: Create Account
•	Secondary: Log in
 
Error States
Condition	UI Response
Email already exists	Inline error
Email has pending invitation	Explicit message
Weak password	Inline checklist
Rate limit exceeded	Blocking message
Network error	Retry toast
 
🟦 Screen 2: Verify Email Sent
URL: /verify-email-sent
Status: ❌ DOES NOT EXIST (MUST CREATE)
Content
•	Icon / illustration
•	Message:
“We’ve sent a verification link to {email}”
•	Actions:
o	Resend (disabled 60s)
o	Change email
 
🔌 API SPECIFICATION
 
API 1 — Register User (HARDENED)
Route:
POST /api/v1/auth/register
Auth: Public
Rate Limit: 10/min/IP
 
Request
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass1",
  "password_confirmation": "SecurePass1"
}
 
Validation Rules
Field	Rules
name	required, string, min:2, max:255
email	required, email, unique:users,email
password	required, confirmed, Password::defaults()
 
Response (201)
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user_id": "uuid",
    "tenant_id": "uuid"
  }
}
⚠️ DO NOT return auth token here
 
🔥 SIDE EFFECTS (ATOMIC — MUST ALL SUCCEED)
1.	Create Tenant
o	status = pending
o	type = individual
o	slug = deterministic
2.	Create User
o	role_in_tenant = owner
3.	Set tenant.owner_user_id
4.	Create tenant_onboarding
o	step = account_created
5.	Fire:
o	Registered event
o	TenantCreated event
6.	Create audit logs:
o	tenant.created
o	user.registered
 
❌ ERROR RESPONSES
Code	Scenario
422	Validation
429	Rate limited
409	Pending invitation exists
500	Atomic failure (rollback)
 
🗄️ DATABASE ENTITIES
Table	Action
users	INSERT
tenants	INSERT
tenant_onboarding	INSERT
audit_logs	INSERT
🚫 No workspace yet
🚫 No plan assignment yet
 
📜 AUDIT LOG EVENTS
Event	Actor	Notes
tenant.created	System	status=pending
user.registered	User	role=owner
 
🧪 UNIT TEST CASES
AuthService::register()
•	✅ creates user
•	✅ creates tenant (pending)
•	🆕 does NOT auto-login
•	🆕 does NOT create workspace
•	🆕 assigns owner role
•	🆕 handles slug collision
•	🆕 blocks pending invitations
•	🆕 rolls back on failure
•	🆕 fires events
 
🌐 E2E TEST CASES (PLAYWRIGHT)
test('successful registration redirects to verify-email-sent', async () => {
  // fill form
  // submit
  // expect /verify-email-sent
});

test('cannot access app before verification', async () => {
  // register
  // try /app/dashboard
  // redirected to /verify-email-sent
});

test('duplicate email shows inline error', async () => {
  // register twice
});
 
🚧 BLOCKERS & FIXES REQUIRED
BLOCKERS
ID	Issue
B1	AuthService uses Tenant::factory()
B2	Token returned before verification
B3	No verify-email-sent screen
B4	No pending invitation check
 
✅ DEFINITION OF DONE (DoD)
✔ User created
✔ Tenant created (pending)
✔ No auth token returned
✔ Email sent
✔ Verify screen shown
✔ Audit logs written
✔ Tests passing
 
🔍 FINAL CHECK
Is Flow 0.2.1 READY TO IMPLEMENT?
❌ NO
Must fix first:
1.	Remove Tenant::factory() usage
2.	Block token issuance pre-verification
3.	Add verify-email-sent UI + route
4.	Add pending invitation guard



✅ BizSocials — Flow 0.2.2: Email Verification (SECURITY-CRITICAL)
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.2 — Authentication & Security
Flow ID: 0.2.2
Flow Name: Email Verification
Status: 🟡 Partial → ❌ NOT COMPLETE (spec complete after this)
This flow is the gatekeeper between:
•	an untrusted identity and
•	a real tenant boundary
If this flow is weak, everything above it is compromised.
 
🎯 PURPOSE (LOCK THIS)
Ensure that:
•	The registering user controls the email address
•	No unverified user can access:
o	Workspaces
o	APIs
o	Tokens
•	Tenant lifecycle transitions are explicit and auditable
 
🧠 CORE SECURITY PRINCIPLES
1.	No verification = no access
2.	Signed, expiring verification links
3.	Idempotent verification
4.	Explicit redirect to onboarding
5.	Abuse-safe resend mechanism
 
🧑‍💼 ACTORS
Actor	Description
Unverified User	Just registered
System	Validates link, updates state
Attacker	Tries replay / brute force
Super Admin	Observes via audit
 
✅ PRECONDITIONS
•	User exists
•	email_verified_at IS NULL
•	Tenant exists with status = pending
•	Verification email was sent in Flow 0.2.1
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — User clicks verification link
Link format (Laravel default, REQUIRED):
/verify-email/{id}/{hash}?expires=...&signature=...
 
STEP 2 — System validates link
Checks:
•	Signature valid
•	Link not expired
•	User ID exists
•	Hash matches email
 
STEP 3 — System updates state (atomic)
•	Mark user email verified
•	Transition tenant status → active
•	Update onboarding step
•	Write audit logs
 
STEP 4 — Redirect user
➡ Always redirect to:
/onboarding/setup
❌ Never redirect directly to dashboard
 
🖥️ UI SPECIFICATION
 
🟦 Screen 1: Email Verification Handler
URL: /verify-email/:id/:hash
Status: EXISTS → MUST BE FIXED
This is a logic screen, not a form.
 
States
✅ Success
•	Show spinner briefly
•	Redirect to /onboarding/setup
❌ Failure — Link Expired
•	Message: “This verification link has expired”
•	CTA: Resend email
❌ Failure — Already Verified
•	Message: “Your email is already verified”
•	CTA: Continue setup
❌ Failure — Invalid Link
•	Message: “Invalid verification link”
•	CTA: Request new email
 
🟦 Screen 2: Verify Email Sent (Reuse)
URL: /verify-email-sent
Used for:
•	Initial post-registration
•	Resend flows
•	Expired link recovery
 
🔌 API SPECIFICATION
 
API 1 — Verify Email (SIGNED)
Route:
GET /api/v1/auth/verify-email/{id}/{hash}
Auth: None (signed URL)
Middleware: signed, throttle:6,1
 
Side Effects (ALL MUST SUCCEED)
1.	Set users.email_verified_at = now
2.	Transition tenant:
o	pending → active
3.	Update tenant_onboarding:
o	mark email_verified
4.	Fire event:
o	UserEmailVerified
5.	Create audit logs:
o	user.email_verified
o	tenant.activated
 
Response
{
  "success": true,
  "message": "Email verified successfully."
}
 
Error Responses
Code	Scenario
403	Invalid or tampered link
410	Link expired
429	Too many attempts
500	Atomic failure (rollback)
 
API 2 — Resend Verification Email
Route:
POST /api/v1/auth/email/resend
Auth: Bearer token (unverified allowed)
Rate Limit: 1/min/user
 
Request
{}
 
Validation Rules
•	User authenticated
•	email_verified_at IS NULL
 
Side Effects
•	Send new verification email
•	Write audit log: user.verification_resent
 
Response
{
  "success": true,
  "message": "Verification email resent."
}
 
🗄️ DATABASE ENTITIES
Table	Action
users	UPDATE email_verified_at
tenants	UPDATE status
tenant_onboarding	UPDATE steps_completed
audit_logs	INSERT
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
user.email_verified	User	verified_at
tenant.activated	System	pending → active
user.verification_resent	User	timestamp
 
🧪 UNIT TEST CASES
VerifyEmailController
•	✅ verifies valid link
•	🆕 blocks expired link
•	🆕 blocks invalid signature
•	🆕 idempotent if already verified
•	🆕 transitions tenant status
•	🆕 updates onboarding step
•	🆕 writes audit logs
•	🆕 rate-limits resend
 
🌐 E2E (PLAYWRIGHT) TESTS
test('user verifies email and is redirected to onboarding', async () => {
  // extract verification link
  // visit link
  // expect redirect to /onboarding/setup
});

test('expired verification link shows resend option', async () => {
  // use expired link
  // expect error + resend CTA
});

test('unverified user cannot access app routes', async () => {
  // register
  // try /app/dashboard
  // redirect to /verify-email-sent
});
 
🚧 BLOCKERS & FIXES REQUIRED
BLOCKERS
ID	Issue
V1	VerifyEmailView redirects to dashboard
V2	Tenant status not updated on verification
V3	No resend verification API
V4	No onboarding step update
V5	No throttling on resend
 
✅ DEFINITION OF DONE (DoD)
✔ Email verified securely
✔ Tenant activated explicitly
✔ Onboarding updated
✔ Redirects to onboarding
✔ Resend protected
✔ Audit logs written
✔ Unit + E2E tests pass
✅ BizSocials — Flow 0.2.3: Login (AUTHENTICATION CORE)
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.2 — Authentication & Security
Flow ID: 0.2.3
Flow Name: Login
Status: 🟢 PARTIAL (backend exists) → ❌ NOT COMPLETE (security gaps)
Login is not “access”.
Login is identity validation + policy enforcement.
 
🎯 PURPOSE (LOCK THIS)
Ensure that:
•	Only verified, active users can authenticate
•	Sessions are tracked, revocable, and auditable
•	Tenant + onboarding state controls access
•	Login abuse is rate-limited and observable
 
🧠 SECURITY PRINCIPLES (NON-NEGOTIABLE)
1.	❌ No login if email not verified
2.	❌ No login if tenant is suspended
3.	❌ No bypass of onboarding guards
4.	✅ Session visibility & revocation
5.	✅ Explicit audit trail
 
🧑‍💼 ACTORS
Actor	Description
User	Registered account holder
System	Validates credentials
Attacker	Brute-force attempts
Super Admin	Observes patterns
 
✅ PRECONDITIONS
•	User exists
•	Email verification MAY or MAY NOT be complete
•	Tenant exists
•	Tenant status is pending | active | suspended
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — User visits Login screen
➡ /login
User enters:
•	Email
•	Password
 
STEP 2 — Credential validation
System verifies:
•	Email exists
•	Password matches hash
 
STEP 3 — Policy enforcement (CRITICAL)
Before issuing token:
Check	Result
Email verified?	❌ block if not
User status active?	❌ block if not
Tenant status active?	❌ block if suspended
MFA enabled?	🔁 trigger MFA
 
STEP 4 — Session creation
•	Create auth token
•	Record session metadata
•	Bind tenant context
 
STEP 5 — Redirect decision
Condition	Redirect
Email not verified	/verify-email-sent
Onboarding incomplete	/onboarding/setup
All good	/app/dashboard
 
🖥️ UI SPECIFICATION
 
🟦 Screen: Login
URL: /login
Status: EXISTS → MUST BE HARDENED
 
Fields
Field	Type	Required	Validation
Email	email	Yes	valid format
Password	password	Yes	min:8
 
Actions
•	Primary: “Sign In”
•	Secondary: “Forgot password?”
•	Link: “Create account”
 
Error States
Scenario	Message
Invalid credentials	“Invalid email or password.”
Email not verified	“Please verify your email to continue.”
Tenant suspended	“Your account is temporarily suspended.”
Rate limited	“Too many attempts. Try again later.”
 
🔌 API SPECIFICATION
 
API 1 — Login
Route:
POST /api/v1/auth/login
Auth: None
Rate Limit: 5 attempts/min/IP + user lockout
 
Request
{
  "email": "john@example.com",
  "password": "SecurePass1"
}
 
Validation Rules
Field	Rules
email	required, email
password	required
 
Policy Checks (MUST enforce)
1.	User exists
2.	Password correct
3.	email_verified_at IS NOT NULL
4.	users.status = active
5.	tenant.status = active
 
Success Response
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@example.com",
      "tenant_id": "uuid"
    },
    "token": "1|xxxxx",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
 
Error Responses
Code	Scenario
401	Invalid credentials
403	Email not verified
403	Tenant suspended
429	Rate limited
423	Account locked (after failures)
 
API 2 — Logout
Route:
POST /api/v1/auth/logout
Auth: Bearer token
 
Side Effects
•	Revoke current token
•	Update session record
•	Write audit log
 
Response
{
  "success": true,
  "message": "Logged out successfully."
}
 
🗄️ DATABASE ENTITIES
Table	Purpose
users	credential + status
tenants	policy check
personal_access_tokens	auth tokens
user_sessions	session tracking
audit_logs	login events
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
user.login_success	User	ip, device
user.login_failed	System	reason
user.logout	User	token_id
user.account_locked	System	threshold reached
 
🧪 UNIT TEST CASES
AuthController::login
•	✅ valid login returns token
•	🆕 blocks unverified email
•	🆕 blocks suspended tenant
•	🆕 rate limits after 5 failures
•	🆕 locks account after threshold
•	🆕 logs failed attempts
•	🆕 redirects onboarding incomplete users
 
🌐 E2E TESTS
test('verified user logs in and reaches dashboard', async () => {});

test('unverified user blocked and redirected', async () => {});

test('suspended tenant cannot login', async () => {});

test('login rate limit enforced', async () => {});
 
🚧 BLOCKERS & REQUIRED FIXES
ID	Issue
L1	Login does not block unverified emails
L2	Tenant suspension not checked
L3	No session tracking
L4	No login audit logs
L5	No account lockout
 
✅ DEFINITION OF DONE
✔ Verified-only login
✔ Tenant policy enforced
✔ Session tracked
✔ Redirect logic correct
✔ Audit logs written
✔ Tests pass


✅ BizSocials — Flow 0.2.4: MFA Setup (Multi-Factor Authentication)
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.2 — Authentication & Security
Flow ID: 0.2.4
Flow Name: MFA Setup (TOTP)
Status: ❌ Not Started (spec required before implementation)
MFA is not optional security polish.
It is account takeover prevention and a compliance baseline.
 
🎯 PURPOSE (LOCK THIS)
Enable Time-based One-Time Password (TOTP) MFA for users so that:
•	Account access requires something you know + something you have
•	MFA can be enabled, verified, enforced, rotated, and revoked
•	MFA state is visible, auditable, and recoverable
 
🔐 SECURITY & PRODUCT PRINCIPLES
1.	MFA is per-user, not per tenant
2.	MFA must be explicitly verified before activation
3.	Recovery options are mandatory
4.	MFA enforcement can be:
o	User-enabled
o	Tenant-enforced (later phase)
5.	MFA must survive:
o	Session expiry
o	Password change
o	Device change
 
🧑‍💼 ACTORS
Actor	Description
User	Enables MFA
System	Generates & verifies TOTP
Attacker	Attempts credential reuse
Admin (future)	Enforces MFA policy
 
✅ PRECONDITIONS
•	User is authenticated
•	Email is verified
•	User status = active
•	Tenant status = active
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — User opens Security Settings
➡ /settings/security
System shows:
•	Password status
•	Active sessions
•	MFA status (Disabled)
 
STEP 2 — Start MFA Setup
User clicks “Enable MFA”
System:
•	Generates TOTP secret
•	Generates QR code
•	Generates recovery codes (NOT ACTIVE YET)
 
STEP 3 — User scans QR code
User uses:
•	Google Authenticator
•	Microsoft Authenticator
•	Authy
(any RFC-6238 compatible app)
 
STEP 4 — User verifies MFA
User enters:
•	6-digit TOTP code
System:
•	Validates code
•	Activates MFA
•	Stores recovery codes (hashed)
 
STEP 5 — Confirmation
System:
•	Shows recovery codes once
•	Requires user to acknowledge saving them
•	Marks MFA as enabled
 
🖥️ UI SPECIFICATION
 
🟦 Screen: Security Settings
URL: /settings/security
Status: EXISTS → MFA SECTION MISSING
 
MFA Section States
❌ Disabled
•	“Protect your account with MFA”
•	CTA: Enable MFA
🟡 Setup in progress
•	QR Code
•	Manual secret
•	Input: 6-digit code
•	CTA: Verify & Enable
🟢 Enabled
•	Status: Enabled
•	Last verified date
•	Buttons:
o	Regenerate recovery codes
o	Disable MFA (requires password)
 
Error States
Scenario	Message
Invalid code	“Invalid authentication code.”
Code expired	“Code expired. Try again.”
Too many attempts	“Too many attempts. Try later.”
 
🔌 API SPECIFICATION
 
API 1 — Start MFA Setup
Route:
POST /api/v1/auth/mfa/setup
Auth: Bearer token
 
Response
{
  "success": true,
  "data": {
    "qr_code": "data:image/png;base64,...",
    "secret": "JBSWY3DPEHPK3PXP",
    "issuer": "BizSocials"
  }
}
 
Side Effects
•	Generate temporary MFA secret
•	Store encrypted (pending state)
 
API 2 — Verify & Enable MFA
Route:
POST /api/v1/auth/mfa/verify
 
Request
{
  "code": "123456"
}
 
Side Effects (TRANSACTIONAL)
1.	Validate TOTP
2.	Mark MFA enabled
3.	Generate recovery codes (10)
4.	Hash recovery codes
5.	Invalidate pending secret
6.	Write audit log
 
Response
{
  "success": true,
  "data": {
    "recovery_codes": [
      "ABCD-1234",
      "EFGH-5678"
    ]
  }
}
⚠️ Recovery codes shown ONCE ONLY
 
API 3 — Disable MFA
Route:
POST /api/v1/auth/mfa/disable
Requires: password confirmation
 
API 4 — Regenerate Recovery Codes
Route:
POST /api/v1/auth/mfa/recovery/regenerate
 
🗄️ DATABASE ENTITIES
Table: user_mfa (NEW)
Column	Type	Notes
id	uuid	PK
user_id	uuid	FK
secret	encrypted	TOTP secret
enabled_at	timestamp	null if disabled
recovery_codes	json	hashed
last_used_at	timestamp	—
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
user.mfa_setup_started	User	ip
user.mfa_enabled	User	method=totp
user.mfa_failed	System	invalid_code
user.mfa_disabled	User	confirmed
user.mfa_recovery_regenerated	User	—
 
🧪 UNIT TEST CASES
MFAService
•	🆕 generates valid TOTP secret
•	🆕 verifies correct code
•	🆕 rejects invalid code
•	🆕 enables MFA only after verification
•	🆕 hashes recovery codes
•	🆕 disables MFA with password confirmation
•	🆕 prevents reuse of recovery code
 
🌐 E2E TEST CASES
test('user enables MFA successfully', async () => {});

test('login requires MFA after enablement', async () => {});

test('invalid MFA code is rejected', async () => {});

test('recovery code works once only', async () => {});
 
🚧 BLOCKERS & DEPENDENCIES
ID	Item
M1	No user_mfa table
M2	No TOTP library wired
M3	Login flow does not challenge MFA
M4	No recovery code handling
✅ Allowed OSS (MIT / Apache-2.0)
•	spomky-labs/otphp (Apache-2.0)
•	bacon/bacon-qr-code (MIT)
 
✅ DEFINITION OF DONE
✔ MFA can be enabled
✔ MFA enforced during login
✔ Recovery codes functional
✔ Audit logs written
✔ Tests pass


✅ BizSocials — Flow 0.2.5: Session Management
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.2 — Authentication & Security
Flow ID: 0.2.5
Flow Name: Session Management
Status: ❌ Not Started (full spec below)
Session Management is not a UI nicety.
It is a security control, incident response tool, and compliance requirement.
 
🎯 PURPOSE (LOCK THIS)
Give users full visibility and control over where and how their account is logged in.
Session Management must allow a user to:
•	See all active sessions
•	Identify device, location, and last activity
•	Terminate individual sessions
•	Log out everywhere
•	Automatically invalidate sessions after:
o	Password change
o	MFA enable/disable
o	Suspicious activity (future phase)
 
🔐 SECURITY & PRODUCT PRINCIPLES
1.	Sessions are per device, not global
2.	Tokens must be revocable
3.	Session metadata must be human-readable
4.	Session termination must be immediate
5.	Session activity must be auditable
 
🧑‍💼 ACTORS
Actor	Description
User	Manages their sessions
System	Tracks & enforces sessions
Attacker	Uses stolen token
Admin (future)	Observes suspicious activity
 
✅ PRECONDITIONS
•	User is authenticated
•	User status = active
•	Tenant status = active
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — View Active Sessions
User navigates to:
➡ /settings/security
System displays:
•	Current session (highlighted)
•	Other active sessions
 
STEP 2 — Inspect Session Details
Each session shows:
•	Device (Browser + OS)
•	IP address
•	Approximate location
•	Last activity timestamp
•	Session status (Active / Current)
 
STEP 3 — Revoke a Single Session
User clicks “Log out” on a session.
System:
•	Invalidates that token
•	Disconnects the device immediately
•	Writes audit log
 
STEP 4 — Log Out From All Devices
User clicks “Log out from all sessions”
System:
•	Invalidates all tokens except current
•	Forces re-auth on other devices
 
STEP 5 — Automatic Revocation (System)
Triggered when:
•	Password changed
•	MFA enabled/disabled
•	Recovery codes regenerated
System:
•	Invalidates all sessions except current
•	Logs security event
 
🖥️ UI SPECIFICATION
 
🟦 Screen: Security Settings — Sessions Section
URL: /settings/security
Status: EXISTS → SESSION SECTION MISSING
 
Session List Table
Column	Description
Device	Browser + OS
Location	City, Country (IP-based)
IP Address	IPv4/IPv6
Last Activity	Relative time
Status	Current / Active
Action	Log out
 
UI STATES
🟢 Current Session
•	Label: “This device”
•	Logout button disabled
🟡 Other Sessions
•	Logout enabled
 
Confirmation Modals
•	Log out this session
•	Log out all sessions
Both require confirmation.
 
Error States
Scenario	Message
Session already expired	“This session is no longer active.”
Network error	“Unable to revoke session. Try again.”
 
🔌 API SPECIFICATION
 
API 1 — List Active Sessions
Route:
GET /api/v1/auth/sessions
Auth: Bearer token
 
Response
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "device": "Chrome on macOS",
      "ip": "49.xxx.xxx.xxx",
      "location": "Bangalore, IN",
      "last_activity_at": "2026-02-09T09:30:00Z",
      "is_current": true
    }
  ]
}
 
API 2 — Revoke Single Session
Route:
DELETE /api/v1/auth/sessions/{session_id}
 
Side Effects
1.	Invalidate token
2.	Remove session record
3.	Write audit log
 
API 3 — Revoke All Other Sessions
Route:
POST /api/v1/auth/sessions/revoke-others
 
Side Effects
•	Delete all sessions except current
•	Invalidate all related tokens
 
🗄️ DATABASE ENTITIES
Table: user_sessions (EXISTS — verified)
Column	Type	Notes
id	uuid	PK
user_id	uuid	FK
token_id	string	FK → personal_access_tokens
ip_address	string	—
user_agent	text	—
last_activity_at	timestamp	—
created_at	timestamp	—
🔒 Index required: (user_id, token_id)
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
user.session_created	System	ip, device
user.session_revoked	User	session_id
user.session_revoked_all	User	count
user.sessions_invalidated	System	reason
 
🧪 UNIT TEST CASES
SessionService
•	🆕 lists active sessions correctly
•	🆕 identifies current session
•	🆕 revokes specific session
•	🆕 revokes all except current
•	🆕 auto-revokes on password change
•	🆕 auto-revokes on MFA change
 
🌐 E2E TEST CASES (Playwright)
test('user sees all active sessions', async () => {});

test('user revokes another session', async () => {});

test('revoked session is logged out immediately', async () => {});

test('logout all sessions works', async () => {});
 
🚧 BLOCKERS & DEPENDENCIES
ID	Item
S1	No session list API
S2	Tokens not linked to sessions
S3	No session UI
S4	No audit events
 
✅ DEFINITION OF DONE
✔ Active sessions visible
✔ Sessions revocable individually
✔ “Logout all” works
✔ Automatic revocation works
✔ Audit logs written
✔ Tests pass


✅ BizSocials — Flow 0.3.1: Super Admin Platform Dashboard
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.3 — Super Admin Platform Console
Flow ID: 0.3.1
Flow Name: Platform Dashboard
Status: ❌ Not Started (full specification below)
This dashboard is the control tower of BizSocials.
It provides observability, not operational interference.
 
🎯 PURPOSE (LOCK THIS)
Provide Super Admins with a real-time, read-only global view of the BizSocials platform to:
•	Monitor platform health
•	Track tenant growth & activity
•	Detect risk, abuse, or incidents early
•	Observe billing & trial performance
•	Ensure zero tenant data leakage
🚫 Super Admins must NOT:
•	Edit tenant data
•	Access tenant content (posts, inbox, messages)
•	Perform tenant-level operations (except suspend/impersonate RO)
 
🧑‍💼 ACTORS
Actor	Description
Super Admin	Bizinso internal platform operator
System	Aggregates metrics
Tenant	Passive (observed, not controlled)
 
✅ PRECONDITIONS
•	User authenticated as super_admin
•	Platform role verified
•	Admin token scope valid
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — Access Platform Dashboard
➡ URL: /admin
System:
•	Authenticates admin
•	Loads aggregated platform metrics
•	Shows last refresh timestamp
 
STEP 2 — View Platform KPIs
Admin sees:
•	Tenant counts
•	Trial vs paid breakdown
•	Active users (rolling 24h / 7d)
•	Platform health indicators
 
STEP 3 — Drill Down (Read-Only)
Admin can:
•	Click “View tenants” → /admin/tenants
•	Click “View billing” → /admin/billing
•	Click “View integrations health” → /admin/integrations
🚫 No inline editing allowed
 
🖥️ UI SPECIFICATION
 
🟦 Screen: Platform Dashboard
URL: /admin
Access: Super Admin only
Status: ❌ DOES NOT EXIST (or partial placeholder)
 
Layout
┌───────────────────────────────────────────────┐
│ Platform Overview (Header)                    │
│ Last updated: 2 mins ago                      │
├───────────────────────────────────────────────┤
│ KPI Cards (Row)                               │
│ [Total Tenants] [Active Trials] [Paid]       │
│ [DAU] [WAU] [MAU]                             │
├───────────────────────────────────────────────┤
│ Platform Health                               │
│ - API uptime                                 │
│ - Queue backlog                              │
│ - Failed jobs (24h)                          │
│ - Webhook failures                           │
├───────────────────────────────────────────────┤
│ Risk & Alerts                                │
│ - Suspended tenants                          │
│ - Payment failures                           │
│ - Abuse flags                                │
├───────────────────────────────────────────────┤
│ Quick Links                                  │
│ - Tenants                                    │
│ - Billing                                    │
│ - Integrations                               │
│ - Feature Flags                              │
└───────────────────────────────────────────────┘
 
KPI CARDS (READ-ONLY)
KPI	Definition
Total Tenants	All non-deleted tenants
Active Tenants	Status = ACTIVE
Trial Tenants	trial_ends_at >= now
Paid Tenants	plan_id != null
DAU	Distinct users active in 24h
WAU	Distinct users active in 7d
MAU	Distinct users active in 30d
 
PLATFORM HEALTH SECTION
Metric	Source
API Uptime	Health checks
Queue Backlog	Horizon
Failed Jobs (24h)	jobs_failed
Webhook Failures	integration_logs
 
RISK & ALERTS
Alert Type	Example
Suspended tenants	status = SUSPENDED
Payment failures	last_invoice_failed
Abuse flags	rate-limit breaches
Compliance	WhatsApp quality warnings (future)
 
🔌 API SPECIFICATION
 
API 1 — Platform Dashboard Metrics
Route:
GET /api/v1/admin/dashboard
Auth: Super Admin token
Middleware: auth:admin, super_admin
 
Response
{
  "success": true,
  "data": {
    "tenants": {
      "total": 128,
      "active": 110,
      "trial": 32,
      "paid": 78,
      "suspended": 4
    },
    "users": {
      "dau": 420,
      "wau": 1890,
      "mau": 6240
    },
    "platform_health": {
      "api_uptime": "99.98%",
      "queue_backlog": 12,
      "failed_jobs_24h": 3,
      "webhook_failures_24h": 7
    },
    "billing": {
      "mrr": 185000,
      "payment_failures": 5
    },
    "alerts": [
      {
        "type": "tenant_suspended",
        "count": 4
      }
    ],
    "generated_at": "2026-02-09T12:30:00Z"
  }
}
 
Validations
•	Must be super admin
•	No tenant context
•	Cached for 60 seconds
 
🗄️ DATABASE ENTITIES (READ-ONLY)
Aggregates data from:
•	tenants
•	users
•	tenant_usage
•	subscriptions
•	invoices
•	jobs / failed_jobs
•	audit_logs
•	integration_logs
🚫 No writes allowed
 
📜 AUDIT LOG EVENTS
Event	Actor	Notes
admin.dashboard_viewed	Super Admin	timestamp
 
🧪 UNIT TEST CASES
AdminDashboardService
•	🆕 returns correct tenant counts
•	🆕 separates trial vs paid correctly
•	🆕 calculates DAU/WAU/MAU accurately
•	🆕 excludes soft-deleted tenants
•	🆕 denies non-super-admin access
 
🌐 E2E TEST CASES (Playwright)
test('super admin can access platform dashboard', async () => {});

test('non-admin is denied access', async () => {});

test('dashboard KPIs render correctly', async () => {});
 
🚧 BLOCKERS & DEPENDENCIES
ID	Item
A1	No AdminDashboardController
A2	No aggregated metrics service
A3	Admin auth middleware incomplete
A4	No admin UI layout
A5	No integration_logs table wired
 
✅ DEFINITION OF DONE
✔ Super admin-only access
✔ KPIs accurate and cached
✔ No tenant data leakage
✔ Read-only enforcement
✔ Audit logged
✔ Tests pass



✅ BizSocials — Flow 0.3.2: Super Admin Tenant Detail View
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.3 — Super Admin Platform Console
Flow ID: 0.3.2
Flow Name: Tenant Detail View
Status: ❌ Not Started (full specification below)
This view answers one question only:
“Is this tenant healthy, compliant, and safe?”
It is observability, not administration.
 
🎯 PURPOSE (LOCK THIS)
Allow Super Admins to:
•	Inspect a single tenant’s state
•	Diagnose issues (billing, onboarding, integrations)
•	Detect risk or abuse early
•	Take limited, explicit actions (suspend / unsuspend)
🚫 Super Admins must NOT:
•	View tenant content (posts, inbox, messages)
•	Edit tenant configuration
•	Access user data beyond identity-level metadata
 
🧑‍💼 ACTORS
Actor	Description
Super Admin	Platform operator
System	Aggregates tenant health
Tenant	Observed only
 
✅ PRECONDITIONS
•	Authenticated as super_admin
•	Valid admin session
•	Tenant exists and not hard-deleted
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — Access Tenant Detail
➡ URL: /admin/tenants/:tenant_id
System:
•	Validates admin access
•	Loads tenant summary + health indicators
•	Logs access event
 
STEP 2 — Review Tenant Overview
Admin sees:
•	Tenant identity & status
•	Onboarding progress
•	Plan & billing state
•	Usage summary
•	Risk indicators
 
STEP 3 — Inspect Modules (Read-Only Tabs)
Admin can switch between:
•	Overview
•	Onboarding
•	Billing
•	Usage
•	Integrations
•	Security & Audit
🚫 No edit controls except suspend
 
STEP 4 — Take Explicit Action (If Required)
Admin may:
•	Suspend tenant
•	Unsuspend tenant
Each action:
•	Requires confirmation
•	Is auditable
•	Is reversible
 
🖥️ UI SPECIFICATION
 
🟦 Screen: Tenant Detail View
URL: /admin/tenants/:id
Access: Super Admin only
Status: ❌ DOES NOT EXIST
 
Header Section
Field	Description
Tenant Name	Legal / display name
Tenant ID	UUID
Status Badge	Active / Trial / Suspended
Created At	Date
Trial Ends	If applicable
Plan	Free / Starter / Paid
 
Tabs & Content
 
TAB 1 — Overview
•	Tenant type (Individual / SMB / Enterprise)
•	Owner user (name + email)
•	Country / timezone
•	Created date
•	Last activity timestamp
 
TAB 2 — Onboarding
Step	Status
Account created	✅
Email verified	✅
Org setup	❌
First workspace	✅
First social account	❌
First post	❌
 
TAB 3 — Billing (READ-ONLY)
•	Current plan
•	Trial end date
•	Subscription status
•	Last invoice status
•	Payment failures count
 
TAB 4 — Usage
•	Posts published (period)
•	Messages handled
•	API calls
•	Storage used
 
TAB 5 — Integrations
•	Connected platforms
•	Status (healthy / error)
•	Token expiry warnings
•	Webhook failures
 
TAB 6 — Security & Audit
•	MFA adoption rate
•	Suspicious login attempts
•	Recent audit events
 
ACTION BAR (RESTRICTED)
Action	Rules
Suspend tenant	Requires confirmation
Unsuspend tenant	Requires confirmation
Impersonate (RO)	Next flow
 
🔌 API SPECIFICATION
 
API 1 — Get Tenant Detail
Route:
GET /api/v1/admin/tenants/{tenant_id}
Auth: Super Admin token
 
Response
{
  "success": true,
  "data": {
    "tenant": {
      "id": "uuid",
      "name": "Acme Corp",
      "type": "b2b_smb",
      "status": "active",
      "created_at": "2025-11-01",
      "trial_ends_at": "2025-11-15",
      "plan": "starter"
    },
    "owner": {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@acme.com"
    },
    "onboarding": {
      "completed_steps": ["account_created", "email_verified"],
      "progress": 40
    },
    "usage": {
      "posts": 120,
      "messages": 540,
      "api_calls": 2300
    },
    "billing": {
      "status": "trial",
      "last_invoice": "paid",
      "payment_failures": 0
    },
    "integrations": [
      {
        "platform": "meta",
        "status": "healthy",
        "expires_at": "2026-02-20"
      }
    ],
    "security": {
      "mfa_enabled_users": 2,
      "recent_login_failures": 1
    }
  }
}
 
API 2 — Suspend Tenant
Route:
POST /api/v1/admin/tenants/{tenant_id}/suspend
 
Side Effects
1.	Update tenant.status = SUSPENDED
2.	Invalidate all sessions
3.	Block all API access
4.	Write audit log
5.	Notify tenant owner (email)
 
API 3 — Unsuspend Tenant
Route:
POST /api/v1/admin/tenants/{tenant_id}/unsuspend
 
🗄️ DATABASE ENTITIES (READ-ONLY)
•	tenants
•	tenant_profiles
•	tenant_onboarding
•	tenant_usage
•	subscriptions
•	invoices
•	social_accounts
•	audit_logs
 
📜 AUDIT LOG EVENTS
Event	Actor	Details
admin.tenant_viewed	Super Admin	tenant_id
admin.tenant_suspended	Super Admin	reason
admin.tenant_unsuspended	Super Admin	—
 
🧪 UNIT TEST CASES
AdminTenantService
•	🆕 returns tenant summary correctly
•	🆕 hides tenant content
•	🆕 suspends tenant safely
•	🆕 blocks suspended tenant access
•	🆕 logs audit events
 
🌐 E2E TEST CASES
test('admin views tenant detail', async () => {});

test('admin suspends tenant', async () => {});

test('suspended tenant cannot log in', async () => {});
 
🚧 BLOCKERS & DEPENDENCIES
ID	Item
T1	No AdminTenantController
T2	Aggregation logic missing
T3	Suspend logic incomplete
T4	No admin tenant UI
T5	Missing audit coverage
 
✅ DEFINITION OF DONE
✔ Tenant details visible
✔ No tenant data leakage
✔ Suspend/unsuspend works
✔ Audit logged
✔ Tests pass


✅ BizSocials — Flow 0.3.3: Tenant Suspend / Unsuspend
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.3 — Super Admin Platform Console
Flow ID: 0.3.3
Flow Name: Tenant Suspend / Unsuspend
Current Tracker Status: 🟢 Marked complete earlier — MUST BE VERIFIED
Final Status After This Spec: 🟡 Partial (implementation exists but not compliant)
This flow is security-critical.
A single mistake here can:
•	Leak tenant data
•	Break compliance
•	Kill trust
 
🎯 PURPOSE (LOCK THIS)
Provide Super Admins with last-resort control to:
•	Immediately stop all tenant activity
•	Prevent data access
•	Halt billing and integrations
•	Preserve auditability and reversibility
This is not moderation.
This is platform safety enforcement.
 
🧑‍💼 ACTORS
Actor	Description
Super Admin	Platform operator
System	Enforces suspension
Tenant	Restricted subject
 
✅ PRECONDITIONS
•	User authenticated as Super Admin
•	Tenant exists
•	Tenant is not hard-deleted
•	Action requires explicit confirmation
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — Initiate Suspend
From:
•	Tenant Detail View (/admin/tenants/:id)
•	Or system alert
Admin clicks “Suspend Tenant”
 
STEP 2 — Confirmation Modal
Modal content:
•	Warning message
•	Explicit consequences
•	Optional reason input
•	Confirm / Cancel actions
 
STEP 3 — System Enforcement
On confirm:
•	Tenant status changes → SUSPENDED
•	All sessions invalidated
•	All API access blocked
•	Background jobs halted
•	Webhooks disabled
•	Tenant notified
 
STEP 4 — Unsuspend (If Needed)
Admin clicks “Unsuspend Tenant”
System:
•	Restores tenant to ACTIVE
•	Requires tenant re-authentication
•	Logs audit
 
🖥️ UI SPECIFICATION
 
🟦 Modal: Suspend Tenant
Triggered from: Tenant Detail View
Status: ❌ NOT VERIFIED
 
Content
•	Title: Suspend Tenant
•	Warning text:
“This will immediately block all access for this tenant.
Users will be logged out. Integrations will stop.”
Fields
Field	Required
Reason (textarea)	Optional
Actions
•	❌ Cancel
•	🔴 Suspend Tenant
 
🟦 Modal: Unsuspend Tenant
Content
•	Title: Unsuspend Tenant
•	Message:
“Tenant access will be restored. Users must log in again.”
Actions:
•	Cancel
•	Restore Access
 
🔌 API SPECIFICATION
 
API 1 — Suspend Tenant
Route:
POST /api/v1/admin/tenants/{tenant_id}/suspend
Auth: Super Admin token
 
Request
{
  "reason": "Compliance violation"
}
 
Side Effects (MUST be atomic)
1.	Update tenants.status = SUSPENDED
2.	Write suspended_at timestamp
3.	Invalidate all user tokens
4.	Block all new sessions
5.	Disable all integrations
6.	Pause background jobs
7.	Write audit log
8.	Send notification email
 
Response
{
  "success": true,
  "message": "Tenant suspended successfully"
}
 
API 2 — Unsuspend Tenant
Route:
POST /api/v1/admin/tenants/{tenant_id}/unsuspend
 
Side Effects
1.	Update tenants.status = ACTIVE
2.	Clear suspended_at
3.	Require re-authentication
4.	Resume integrations
5.	Write audit log
 
🛑 ENFORCEMENT POINTS (CRITICAL)
Suspension must block:
Layer	Enforcement
Auth	Login denied
API	403 Forbidden
Jobs	Halted
Webhooks	Ignored
UI	Locked screen
Tokens	Revoked
Middleware Required
EnsureTenantIsActive
 
🗄️ DATABASE CHANGES (REQUIRED)
Table: tenants
suspended_at TIMESTAMP NULL
suspension_reason TEXT NULL
 
📜 AUDIT LOG EVENTS
Event	Actor	Payload
admin.tenant_suspended	Super Admin	tenant_id, reason
admin.tenant_unsuspended	Super Admin	tenant_id
tenant.access_blocked	System	tenant_id
 
🧪 UNIT TEST CASES
TenantSuspensionService
•	🆕 suspends tenant atomically
•	🆕 revokes tokens
•	🆕 blocks access everywhere
•	🆕 unsuspends correctly
•	🆕 logs audit events
•	🆕 idempotent behavior
 
🌐 E2E TEST CASES
test('admin suspends tenant and users are logged out', async () => {});
test('suspended tenant cannot access API', async () => {});
test('admin unsuspends tenant and access resumes', async () => {});
 
🚧 VERIFIED STATE (IMPORTANT)
What EXISTS (from prior knowledge)
•	Tenant status enum exists
•	Basic suspend endpoint exists
What is MISSING / BROKEN
•	❌ No token revocation
•	❌ No job pause
•	❌ No webhook block
•	❌ No UI confirmation
•	❌ No audit completeness
 
❗ TRACKER UPDATE
Flow 0.3.3 Status:
🟡 PARTIAL — unsafe to trust
 
✅ DEFINITION OF DONE
✔ Tenant status enforced everywhere
✔ Sessions revoked
✔ Integrations stopped
✔ Audit logged
✔ Reversible
✔ Tests pass


🔐 BizSocials — Flow 0.3.4: Super Admin Impersonation (Read-Only)
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.3 — Super Admin Platform Console
Flow ID: 0.3.4
Flow Name: Super Admin Impersonation (Read-Only)
Tracker Status (before): 🟡 Partial
Tracker Status (after this spec): 🟡 Partial (spec complete, gaps identified)
⚠️ This flow is extremely sensitive.
If done wrong, it becomes a data-leak and trust-killer.
The ONLY acceptable mode is Read-Only Impersonation.
 
🎯 PURPOSE (LOCK THIS)
Allow Super Admins to observe a tenant’s real experience for:
•	Support
•	Debugging
•	Verification
•	Compliance review
WITHOUT:
•	Mutating data
•	Triggering integrations
•	Bypassing permissions
•	Hiding audit traces
This is observability, not control.
 
🧑‍💼 ACTORS
Actor	Role
Super Admin	Initiates impersonation
System	Enforces RO constraints
Tenant User	Impersonated identity
 
🔐 SECURITY GUARANTEES (NON-NEGOTIABLE)
Rule	Required
Read-only enforced at API	✅
All writes blocked	✅
Banner shown at all times	✅
Impersonation time-limited	✅
Every action audited	✅
Easy exit	✅
No background jobs	✅
No webhooks	✅
 
✅ PRECONDITIONS
•	Authenticated Super Admin
•	Tenant exists and is ACTIVE
•	Tenant not suspended
•	Explicit user selected (default = owner)
•	Impersonation duration defined (e.g. 30 min)
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — Initiate Impersonation
From:
•	/admin/tenants/:id
Admin clicks “Impersonate (Read-Only)”
 
STEP 2 — Impersonation Modal
Admin must:
•	Select user (dropdown)
•	Confirm read-only restrictions
•	Start session
 
STEP 3 — Enter Impersonation Mode
System:
•	Issues impersonation token
•	Redirects to tenant UI
•	Displays persistent banner
•	Enforces RO middleware
 
STEP 4 — Observe Tenant UI
Admin can:
•	Navigate UI
•	View data
•	Test flows visually
Admin cannot:
•	Save
•	Publish
•	Send
•	Delete
•	Configure
•	Trigger jobs
 
STEP 5 — Exit Impersonation
Admin clicks “Exit Impersonation”
System:
•	Revokes impersonation token
•	Redirects back to admin panel
•	Writes audit event
 
🖥️ UI SPECIFICATION
 
🟦 Impersonation Modal
Trigger: /admin/tenants/:id
Fields
Field	Required
User selector	Yes
Duration (15/30/60 min)	Yes
Reason (textarea)	Optional
Warnings
“You are entering READ-ONLY mode.
All actions are logged.”
Actions
•	Cancel
•	Start Impersonation
 
🟥 Persistent Impersonation Banner (CRITICAL)
Visible on every screen
Content:
🔒 READ-ONLY IMPERSONATION MODE
Tenant: Acme Corp
User: John Doe
Ends in: 27 minutes
[ Exit Impersonation ]
 
🔌 API SPECIFICATION
 
API 1 — Start Impersonation
Route
POST /api/v1/admin/impersonate
Auth
•	Super Admin token
Request
{
  "tenant_id": "uuid",
  "user_id": "uuid",
  "duration_minutes": 30,
  "reason": "Investigating publishing issue"
}
 
Side Effects (ATOMIC)
1.	Validate super admin
2.	Validate tenant ACTIVE
3.	Validate user belongs to tenant
4.	Create impersonation session
5.	Issue scoped token (read_only = true)
6.	Set expiry timestamp
7.	Write audit log
 
Response
{
  "success": true,
  "impersonation_token": "imp_XXXXX",
  "expires_at": "2026-02-09T12:30:00Z",
  "redirect_url": "/app/dashboard"
}
 
API 2 — End Impersonation
Route
POST /api/v1/admin/impersonate/exit
Auth
•	Impersonation token
 
Side Effects
1.	Revoke impersonation token
2.	Clear session
3.	Write audit log
 
🛑 READ-ONLY ENFORCEMENT (MOST IMPORTANT)
Middleware: EnsureReadOnlyImpersonation
Rules:
•	❌ Block all POST/PUT/PATCH/DELETE
•	❌ Block file uploads
•	❌ Block background job dispatch
•	❌ Block webhooks
•	❌ Block external calls
Response
{
  "error": "READ_ONLY_MODE",
  "message": "Action not allowed during impersonation."
}
 
🗄️ DATABASE ENTITIES (NEW)
Table: admin_impersonation_sessions
id UUID PK
admin_user_id UUID
tenant_id UUID
user_id UUID
read_only BOOLEAN DEFAULT true
reason TEXT
started_at TIMESTAMP
expires_at TIMESTAMP
ended_at TIMESTAMP NULL
 
📜 AUDIT LOG EVENTS
Event	Actor	Payload
admin.impersonation_started	Super Admin	tenant_id, user_id
admin.impersonation_ended	Super Admin	tenant_id
admin.readonly_action_blocked	System	route, method
 
🧪 UNIT TEST CASES
ImpersonationService
•	🆕 creates RO token
•	🆕 enforces expiry
•	🆕 blocks writes
•	🆕 validates tenant/user
•	🆕 revokes token
•	🆕 logs audit
 
🌐 E2E TEST CASES
test('admin impersonates tenant in read-only mode', async () => {});
test('write action is blocked during impersonation', async () => {});
test('banner is visible on all screens', async () => {});
test('exit impersonation restores admin context', async () => {});
 
🚧 VERIFIED STATE
EXISTS
•	Basic impersonation endpoint (assumed)
•	Admin auth separation
❌ MISSING / RISKY
•	No read-only enforcement
•	No banner
•	No expiry
•	No audit completeness
•	No token scoping
 
❗ TRACKER UPDATE
Flow 0.3.4 Status:
🟡 PARTIAL — NOT SAFE
 
✅ DEFINITION OF DONE
✔ RO enforced everywhere
✔ Banner visible
✔ Expiry enforced
✔ Audit complete
✔ Exit works
✔ Tests pass

🧠 BizSocials — Flow 0.3.5: Integration Health Board
Phase: 0 — SaaS Delivery Foundation
Phase Section: 0.3 — Super Admin Platform Console
Flow ID: 0.3.5
Flow Name: Integration Health Board
Tracker Status (before): ❌ Not Started
Tracker Status (after this spec): 🟡 Partial (spec complete, implementation missing)
This flow is non-negotiable for a SaaS like BizSocials.
Without it, failures look “random”, support becomes reactive, and trust erodes.
 
🎯 PURPOSE (LOCK THIS)
Give Super Admins real-time, centralized visibility into the health of:
•	All external integrations (Meta, Instagram, LinkedIn, X, WhatsApp, Email, Payments, AI, Webhooks)
•	Across all tenants
•	With early warning before user-facing failure
This is observability, not configuration.
 
🧑‍💼 ACTORS
Actor	Role
Super Admin	Observes & investigates
System	Emits health signals
Integrations	External dependencies
 
🔐 CORE PRINCIPLES (NON-NEGOTIABLE)
•	Read-only for Super Admin
•	No tenant data leakage
•	Aggregated + drill-down views
•	Clear severity levels
•	Actionable signals, not raw logs
•	Time-based trend visibility
 
🧭 COMPLETE USER JOURNEY
 
STEP 1 — Access Health Board
URL:
/admin/integrations
Super Admin opens Integration Health Board
 
STEP 2 — Global Overview
System displays:
•	All supported platforms
•	Overall status per platform
•	Affected tenant count
•	Active incidents
 
STEP 3 — Drill Down (Per Integration)
Admin clicks on a platform (e.g. Meta)
System shows:
•	Auth failures
•	Token expiry trends
•	API error rates
•	Webhook delivery issues
•	Affected tenants (IDs only, no data)
 
STEP 4 — Drill Down (Per Tenant, Read-Only)
Admin can:
•	View tenant-level integration status
•	See last successful sync
•	See error categories
Admin cannot:
•	Reconnect
•	Modify config
•	Trigger retries
 
🖥️ UI SPECIFICATION
 
🟦 Screen: Integration Health Board
URL: /admin/integrations
 
Top Summary Cards
Card	Description
Healthy Integrations	Count
Degraded	Warning-level
Down	Critical
Affected Tenants	Count
 
Integration Table
Integration	Status	Affected Tenants	Error Rate	Last Incident
Meta	🔴 Down	14	32%	10 min ago
WhatsApp	🟡 Degraded	3	6%	1 hr ago
LinkedIn	🟢 Healthy	0	0%	—
Actions
•	View Details
 
🟦 Integration Detail View (Drawer or Page)
Shows:
•	Status timeline (last 24h / 7d)
•	Error category breakdown
•	Token expiry distribution
•	Webhook delivery stats
•	Rate limit breaches
 
🟦 Tenant Impact List (Sanitized)
Tenant ID	Status	Last Success	Error Type
tnt_xxx	Token expired	2h ago	auth_expired
❗ No tenant names, no content, no PII
 
🔌 API SPECIFICATION
 
API 1 — Get Integration Health Overview
Route
GET /api/v1/admin/integrations/health
Auth
•	Super Admin
 
Response
{
  "summary": {
    "healthy": 5,
    "degraded": 2,
    "down": 1,
    "affected_tenants": 17
  },
  "integrations": [
    {
      "key": "meta",
      "status": "down",
      "affected_tenants": 14,
      "error_rate": 0.32,
      "last_incident_at": "2026-02-09T10:12:00Z"
    }
  ]
}
 
API 2 — Get Integration Detail
Route
GET /api/v1/admin/integrations/{integration_key}
 
Response
{
  "integration": "meta",
  "status": "down",
  "metrics": {
    "auth_failures": 42,
    "rate_limit_hits": 17,
    "webhook_failures": 9
  },
  "timeline": [...],
  "affected_tenants": [
    {
      "tenant_id": "uuid",
      "status": "auth_failed",
      "last_success_at": "2026-02-09T08:00:00Z"
    }
  ]
}
 
🗄️ DATABASE ENTITIES (NEW)
Table: integration_health_snapshots
id UUID PK
integration_key VARCHAR(50)
status VARCHAR(20) -- healthy / degraded / down
error_rate FLOAT
affected_tenants INT
captured_at TIMESTAMP
 
Table: tenant_integration_health
id UUID PK
tenant_id UUID
integration_key VARCHAR(50)
status VARCHAR(20)
last_success_at TIMESTAMP
error_code VARCHAR(50)
metadata JSON
updated_at TIMESTAMP
 
⚙️ HEALTH SIGNAL SOURCES
Collected from:
•	OAuth token refresh jobs
•	API call failures
•	Webhook delivery logs
•	Rate limit handlers
•	Background job failures
All aggregated, not raw logs.
 
📜 AUDIT LOG EVENTS
Event	Actor	Payload
admin.integration_health_viewed	Super Admin	integration_key
system.integration_degraded	System	integration_key
system.integration_down	System	integration_key
 
🧪 UNIT TEST CASES
IntegrationHealthService
•	🆕 aggregates health correctly
•	🆕 calculates error rates
•	🆕 isolates tenant data
•	🆕 flags degraded/down status
•	🆕 handles missing signals
 
🌐 E2E TEST CASES
test('super admin views integration health board', async () => {});
test('integration detail shows sanitized tenant list', async () => {});
test('no tenant data leakage in admin view', async () => {});
 
🚧 VERIFIED STATE
EXISTS
•	Error logs exist
•	Background jobs exist
❌ MISSING
•	No health aggregation
•	No admin UI
•	No snapshot tables
•	No APIs
•	No alerts
 
❗ TRACKER UPDATE
Flow 0.3.5 Status:
🟡 PARTIAL — NOT IMPLEMENTED
 
✅ DEFINITION OF DONE
✔ Health snapshots generated
✔ Admin UI shows overview + drill-down
✔ No tenant data leakage
✔ Audit logs recorded
✔ Tests pass
 
🔍 FINAL CHECK
Is Flow 0.3.5 READY TO IMPLEMENT?
❌ NO
Blockers
1.	Health aggregation service missing
2.	Snapshot tables missing
3.	Admin UI missing
4.	No alert thresholds defined
 
🏁 PHASE 0.3 COMPLETE — STATUS
Flow	Status
0.3.1 Platform Dashboard	🟡
0.3.2 Tenant Detail View	🟡
0.3.3 Tenant Suspend	🟡
0.3.4 Impersonation (RO)	🟡
0.3.5 Integration Health	🟡



🎯 PHASE 1 PURPOSE (LOCK THIS)
Create a single, normalized integration layer that:
•	Abstracts all social platforms
•	Drives UI behavior dynamically
•	Enforces permissions & limits centrally
•	Prevents raw OAuth chaos
•	Enables future platforms without rewrites
Phase 1 answers one question:
“What does this platform support, and under what rules?”
 
🧠 PHASE 1 CONCEPTUAL MODEL (VERY IMPORTANT)
There are THREE distinct layers people often mix up (don’t):
1.	Platform Registry → What a platform can do
2.	Platform App Registry → How BizSocials connects to it
3.	Tenant Social Accounts → What a tenant has connected
If these are not cleanly separated → maintenance hell.
 
📦 PHASE 1 — USER FLOW EXECUTION TRACKER (DRAFT)
(We will fully spec each like Phase 0)
Phase 1.1 — Platform Registry (Internal, Super Admin)
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Status	Tests	Audit	Notes
1	1.1.1	Platform Registry Management	/admin/platforms	CRUD /admin/platforms	social_platforms	❌	❌	❌	FOUNDATIONAL
1	1.1.2	Platform Capability Matrix	/admin/platforms/:id	GET /admin/platforms/{id}	social_platforms	❌	❌	❌	Drives UI
 
Phase 1.2 — Platform App Registry (Super Admin Only)
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Status	Tests	Audit	Notes
1	1.2.1	Platform App Setup	/admin/platform-apps	CRUD /admin/platform-apps	platform_apps	❌	❌	❌	Tenants NEVER create apps
1	1.2.2	App Credential Rotation	/admin/platform-apps/:id	POST /rotate	platform_apps	❌	❌	❌	Security critical
 
Phase 1.3 — Tenant Social Account Model
Phase	Flow ID	User Flow Name	Screens	APIs	DB Entities	Status	Tests	Audit	Notes
1	1.3.1	Social Account Discovery	/app/social/connect	GET /social/discover	social_accounts	❌	❌	❌	Post-OAuth
1	1.3.2	Social Account Configuration	/app/social/configure	POST /social/accounts	social_account_configs	❌	❌	❌	UX gap today
1	1.3.3	Social Account Health	/app/social/accounts	GET /social/accounts	social_accounts	❌	❌	❌	Token expiry, errors
 
🧱 PHASE 1 — CORE DATABASE ENTITIES (PREVIEW)
These tables must exist before Meta/WhatsApp flows.
1️⃣ social_platforms
key (meta, whatsapp, youtube, x)
name
capabilities (publish, inbox, analytics, ads)
auth_type (oauth, api_key)
requires_review (bool)
compliance_level
limits_schema (json)
status
2️⃣ platform_apps
platform_key
app_id
app_secret (encrypted)
scopes
redirect_urls
review_status
created_by (super_admin)
3️⃣ social_accounts
tenant_id
workspace_id
platform_key
platform_account_id
display_name
status (connected / expired / error)
token_metadata (encrypted)
connected_at
4️⃣ social_account_configs
social_account_id
posting_enabled
inbox_enabled
analytics_enabled
approval_required
assigned_team_id
rate_limits
notification_prefs
 
🔐 NON-NEGOTIABLE RULES FOR PHASE 1
Lock these now or regret later:
1.	❌ Tenants NEVER create platform apps
2.	❌ OAuth ≠ connected account
3.	❌ Token ≠ permission
4.	❌ Platform capability ≠ tenant entitlement
5.	❌ UI must be driven by platform registry
6.	❌ No hard-coded platform logic in UI
 
🧪 TESTING EXPECTATION (PHASE 1)
For every flow:
•	Unit: capability resolution
•	Integration: OAuth → discovery → config
•	E2E: guided wizard (no raw redirect)
If any of these is missing → ❌ NOT DONE.
 
🧭 HOW WE SHOULD PROCEED (RECOMMENDED)
Step 1 (Next Message)
👉 Fully spec Flow 1.1.1 — Platform Registry Management
Exactly like:
•	Flow 0.1.1
•	Screen-by-screen
•	API-by-API
•	DB + audit + tests
Step 2
Flow 1.1.2 → Capability Matrix
Step 3
Flow 1.2.x → Platform App Registry
Only after that do we touch Meta / WhatsApp.


PHASE 1 — Flow 1.1.1: Platform Registry Management
This flow defines what BizSocials supports as a platform.
Nothing downstream (OAuth, publishing, inbox, analytics) is allowed to bypass this layer.
 
1️⃣ PURPOSE (LOCK THIS)
Create a central, authoritative registry of all external platforms supported by BizSocials that:
•	Normalizes platform capabilities
•	Drives UI visibility dynamically
•	Controls permissions, limits, and compliance
•	Enables safe extensibility (Threads, Bluesky, future platforms)
•	Prevents platform-specific logic leakage into product flows
Audience
•	Super Admin (Bizinso internal only)
Non-Audience
•	❌ Tenants
•	❌ Workspace admins
•	❌ Developers bypassing UI
 
2️⃣ ACTOR & ACCESS CONTROL
Actor	Access
Super Admin	Full CRUD
Tenant Owner	❌ None
Workspace Admin	❌ None
API Key	❌ None
Authorization rule:
role == SUPER_ADMIN
 
3️⃣ COMPLETE USER JOURNEY
Preconditions
•	Super Admin is authenticated
•	Super Admin dashboard is accessible
•	No platform registry assumptions exist
 
STEP 1 — View Platform Registry
URL
/admin/platforms
What user sees
•	List of all supported platforms
•	Status & capability overview
•	Ability to add/edit platforms
 
STEP 2 — Create New Platform
Super Admin clicks “Add Platform”
 
STEP 3 — Configure Platform Capabilities
Super Admin defines:
•	What this platform can do
•	How it authenticates
•	Whether it requires compliance review
•	What limits apply
 
STEP 4 — Save & Activate Platform
Platform becomes available for:
•	App registration (Flow 1.2.x)
•	Tenant onboarding (Phase 2)
 
Postconditions
•	Platform is registered
•	Capabilities are normalized
•	UI can dynamically render platform options
•	Audit trail is created
 
4️⃣ UI SCREENS (SCREEN-BY-SCREEN)
 
Screen 1: Platform Registry List
URL
/admin/platforms
Table Columns
Column	Description
Platform	Name + icon
Key	Internal identifier
Auth Type	OAuth / API Key
Capabilities	Publish, Inbox, Analytics, Ads
Compliance	Required / Optional
Status	Active / Disabled
Created At	Timestamp
Actions	View / Edit / Disable
Primary CTA
+ Add Platform
Empty State
“No platforms registered yet. Add one to begin integrations.”
 
Screen 2: Create / Edit Platform
URL
/admin/platforms/create
/admin/platforms/:id/edit
 
Core Fields
Field	Type	Required	Validation
Platform Name	text	✅	min:2, max:50
Platform Key	slug	✅	unique, lowercase
Description	textarea	❌	max:255
Auth Type	select	✅	oauth, api_key
Compliance Level	select	✅	none, standard, strict
Requires Review	toggle	✅	boolean
Status	toggle	✅	active / disabled
 
Capability Toggles (CRITICAL)
Capability	Meaning
Publishing	Can create posts
Inbox	Can read/reply to messages
Analytics	Can fetch metrics
Ads	Paid ads management
Webhooks	Supports inbound events
Templates	Requires pre-approved templates
Media Upload	Supports media
Capabilities stored as normalized JSON, not booleans in code.
 
Limits Schema (JSON Editor)
{
  "posts_per_day": 100,
  "messages_per_minute": 20,
  "media_size_mb": 100,
  "api_rate_limit": "platform-defined"
}
Validation:
•	Must be valid JSON
•	Keys must be snake_case
•	Values must be numeric or string
 
Screen 3: Platform Detail (Read-Only)
URL
/admin/platforms/:id
Displays:
•	Platform metadata
•	Capabilities
•	Limit definitions
•	Apps using this platform (read-only)
•	Last updated
•	Audit history
 
5️⃣ API SPECIFICATION (API-BY-API)
 
API 1: List Platforms
GET /api/v1/admin/platforms
Auth
•	Bearer token (Super Admin)
Response
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "key": "meta",
      "name": "Meta (Facebook & Instagram)",
      "auth_type": "oauth",
      "capabilities": ["publish", "inbox", "analytics"],
      "compliance_level": "strict",
      "status": "active"
    }
  ]
}
 
API 2: Create Platform
POST /api/v1/admin/platforms
Request
{
  "name": "Meta",
  "key": "meta",
  "auth_type": "oauth",
  "capabilities": ["publish", "inbox", "analytics"],
  "compliance_level": "strict",
  "requires_review": true,
  "limits_schema": {
    "posts_per_day": 100
  }
}
Validations
•	key unique
•	capabilities ∈ allowed set
•	limits_schema valid JSON
 
API 3: Update Platform
PUT /api/v1/admin/platforms/{id}
Rules:
•	key is immutable after creation
•	disabling platform auto-disables tenant onboarding (future phase)
 
API 4: Disable Platform
POST /api/v1/admin/platforms/{id}/disable
Effects:
•	New tenant connections blocked
•	Existing accounts remain (read-only warning later)
 
6️⃣ DATABASE ENTITY
Table: social_platforms
Column	Type	Notes
id	uuid	PK
key	varchar(50)	unique
name	varchar(100)	display
description	text	optional
auth_type	varchar(20)	oauth/api_key
capabilities	json	normalized
compliance_level	varchar(20)	enum
requires_review	boolean	
limits_schema	json	
status	varchar(20)	active/disabled
created_by	uuid	super admin
created_at	timestamp	
updated_at	timestamp	
 
7️⃣ VALIDATION & ERROR SCENARIOS
Scenario	Result
Duplicate platform key	422
Invalid capability	422
Invalid JSON	422
Non-admin access	403
Disable platform in use	Allowed (warn)
 
8️⃣ AUDIT LOG EVENTS
Event	Actor	Payload
platform.created	Super Admin	{ platform_key, capabilities }
platform.updated	Super Admin	diff
platform.disabled	Super Admin	reason
 
9️⃣ TEST CASES
Unit Tests
•	Platform key uniqueness
•	Capability normalization
•	JSON schema validation
•	Status transitions
 
Integration Tests
•	Admin can create platform
•	Tenant cannot access endpoint
•	Disabled platform not returned to tenants
 
E2E (Admin)
test('super admin creates platform registry entry', async () => {
  // Login as super admin
  // Navigate to /admin/platforms
  // Click Add Platform
  // Fill details
  // Save
  // Verify appears in list
})
 
🔟 DEPENDENCIES & BLOCKERS
Required (Must Exist)
•	Super Admin auth
•	AuditLog service
•	Admin router guard
None External
•	No OAuth
•	No third-party calls
 
✅ DEFINITION OF DONE (DoD)
✔ Platform appears in admin registry
✔ Capabilities drive UI (later flows)
✔ Audit entry created
✔ Invalid configs rejected
✔ Tests pass
✔ No tenant access


PHASE 1 — Flow 1.1.2: Platform Capability Matrix
If Flow 1.1.1 answers “what platforms exist”,
Flow 1.1.2 answers “what each platform can and cannot do — precisely, visibly, and enforceably.”
This flow is read-only but foundational.
It drives UI rendering, feature gating, validation, and error prevention across the entire product.
 
1️⃣ PURPOSE (LOCK THIS)
Provide a clear, normalized, explorable capability matrix that:
•	Shows what features each platform supports
•	Explains constraints and conditions per capability
•	Prevents unsupported actions before they happen
•	Acts as the single source of truth for:
o	UI enable/disable logic
o	Backend validation
o	Onboarding guidance
o	Error messaging
No screen, API, or workflow is allowed to “guess” platform behavior.
 
2️⃣ ACTORS & ACCESS
Actor	Access
Super Admin	Read (full)
Product / Support	Read (via admin)
Tenants	❌ No direct access
System	Uses internally
Tenants will benefit from this, but never see raw matrix data.
 
3️⃣ COMPLETE USER JOURNEY
Preconditions
•	Platforms exist in registry (Flow 1.1.1)
•	Capabilities are defined per platform
 
STEP 1 — Open Capability Matrix
URL
/admin/platforms/:id/capabilities
Super Admin selects a platform → clicks “Capabilities”
 
STEP 2 — View Capability Grid
System renders:
•	Capabilities vs constraints
•	Read-only, structured
•	With human-readable explanations
 
STEP 3 — Inspect Capability Details
Admin expands a capability to see:
•	Preconditions
•	Restrictions
•	Platform quirks
•	Known limitations
 
Postconditions
•	Admin understands exactly what the platform supports
•	No changes are made here (edit happens in Flow 1.1.1)
•	System has a canonical capability contract
 
4️⃣ UI SPECIFICATION
 
🟦 Screen: Platform Capability Matrix
URL
/admin/platforms/:id/capabilities
 
Header Section
Displays:
•	Platform name + icon
•	Auth type
•	Compliance level
•	Status
 
Capability Matrix Table
Capability	Supported	Conditions	Notes
Publish Posts	✅	Requires approved app	Character limits vary
Schedule Posts	✅	Max 30 days ahead	—
Inbox (DMs)	❌	—	Not supported by API
Comments	✅	Page only	—
Analytics	🟡 Partial	Delayed metrics	24–48h lag
Media Upload	✅	Max 100MB	Video < 60s
Legend:
•	✅ Supported
•	🟡 Partial
•	❌ Not Supported
 
Expandable Capability Row
When expanded:
Shows
•	Required permissions
•	Required scopes
•	Rate limits
•	Approval requirements
•	Known platform errors
•	UI implications
Example:
Capability: Publish Posts

Requires:
- OAuth scopes: pages_manage_posts
- App review: Yes
- Media constraints: JPG/PNG/MP4

Limitations:
- First comment only supported for Instagram
- No link previews for Stories
 
5️⃣ DATA MODEL (READ-ONLY VIEW)
No new tables are created here.
This flow projects structured data from social_platforms.capabilities + limits_schema.
However, internally we standardize the shape.
 
Canonical Capability Schema (Internal)
{
  "capability": "publish",
  "supported": true,
  "level": "full",
  "conditions": {
    "requires_review": true,
    "allowed_content_types": ["text", "image", "video"],
    "max_video_duration_sec": 60
  },
  "limits": {
    "posts_per_day": 100
  },
  "notes": "Stories do not support links"
}
This schema is:
•	Used by UI
•	Used by API validation
•	Used by onboarding wizards
•	Used by error messaging
 
6️⃣ API SPECIFICATION
 
API 1: Get Platform Capability Matrix
GET /api/v1/admin/platforms/{id}/capabilities
Auth
•	Super Admin
 
Response
{
  "platform": {
    "id": "uuid",
    "key": "meta",
    "name": "Meta"
  },
  "capabilities": [
    {
      "key": "publish",
      "supported": true,
      "level": "full",
      "conditions": {
        "requires_review": true,
        "content_types": ["text", "image", "video"]
      },
      "limits": {
        "posts_per_day": 100
      },
      "notes": "Some formats are platform-specific"
    }
  ]
}
 
API RULES (IMPORTANT)
•	No mutation allowed
•	Derived strictly from registry data
•	If a capability is missing → treated as ❌ unsupported
•	Never infer defaults
 
7️⃣ VALIDATION & ERROR HANDLING
Scenario	Behavior
Platform not found	404
No capabilities defined	Show empty matrix
Invalid capability config	Flag internally, do not crash UI
Non-admin access	403
 
8️⃣ AUDIT LOG EVENTS
This flow is read-only, but access is still auditable.
Event	Actor	Payload
admin.platform_capabilities_viewed	Super Admin	platform_key
 
9️⃣ TEST CASES
 
Unit Tests
•	Capability normalization logic
•	Partial vs full support resolution
•	Missing capability handling
•	Limits schema mapping
 
Integration Tests
•	Registry → matrix projection correctness
•	Disabled platform still viewable
•	Non-admin blocked
 
E2E (Admin)
test('super admin views platform capability matrix', async () => {
  // Navigate to /admin/platforms
  // Click a platform
  // Open Capabilities tab
  // Verify matrix renders
  // Expand a capability
})
 
🔟 DEPENDENCIES & BLOCKERS
Must Exist
•	social_platforms table
•	Capability schema enforcement
•	Admin auth & routing
No External Dependencies
•	No OAuth
•	No third-party APIs
 
✅ DEFINITION OF DONE (DoD)
✔ Capability matrix visible
✔ Conditions & limits displayed
✔ No mutation allowed
✔ Used as source of truth for later flows
✔ Audit event logged
✔ Tests pass


PHASE 1 — Flow 1.2.1: Platform App Setup (Super Admin)
This flow defines how BizSocials securely owns OAuth apps for every external platform.
If this is done wrong → token leaks, tenant cross-contamination, compliance violations.
If done right → unlimited tenants, zero chaos.
This flow is Super-Admin only.
Tenants will never touch this layer.
 
1️⃣ PURPOSE (LOCK THIS)
Provide a secure, centralized registry where:
•	BizSocials creates and manages official platform apps
•	OAuth credentials are never exposed to tenants
•	Each app is mapped to:
o	Platform
o	Capabilities
o	Review status
o	Environment (dev / staging / prod)
•	Downstream onboarding flows consume this configuration safely
Tenants connect accounts.
BizSocials owns the apps.
 
2️⃣ ACTORS & ACCESS
Actor	Access
Super Admin (Bizinso)	Full CRUD
Support / Ops	Read-only
Tenants	❌ No access
System	Uses for OAuth flows
 
3️⃣ COMPLETE USER JOURNEY
Preconditions
•	Platform exists in registry (Flow 1.1.1)
•	Capability matrix exists (Flow 1.1.2)
 
STEP 1 — View Platform Apps
Super Admin navigates to platform → Apps tab
 
STEP 2 — Create New Platform App
Super Admin clicks “Add App”
Provides:
•	App name
•	Environment
•	Client ID
•	Client Secret
•	Scopes
•	Redirect URLs
•	Review status
 
STEP 3 — Validate & Save
System:
•	Encrypts secrets
•	Validates scopes vs capabilities
•	Verifies redirect URL format
•	Stores app as inactive by default
 
STEP 4 — Activate App
Super Admin explicitly activates the app
Only one active app per platform per environment
 
Postconditions
•	OAuth app is safely stored
•	Ready for tenant onboarding flows
•	No tenant has direct access
 
4️⃣ UI SPECIFICATION
 
🟦 Screen: Platform App Registry
URL
/admin/platforms/:id/apps
 
App List Table
App Name	Environment	Status	Review	Last Updated	Actions
Meta Prod	Production	Active	Approved	Feb 10	View
Meta Dev	Development	Inactive	Pending	Feb 2	Edit
Badges:
•	Active / Inactive
•	Approved / Pending / Rejected
•	Dev / Staging / Prod
 
🟦 Screen: Add / Edit Platform App
URL
/admin/platforms/:id/apps/new
/admin/platforms/:id/apps/:appId/edit
 
Fields
Field	Type	Required	Notes
App Name	Text	Yes	Internal label
Environment	Select	Yes	dev / staging / prod
Client ID	Text	Yes	Stored encrypted
Client Secret	Password	Yes	Encrypted, masked
Scopes	Multi-select	Yes	From capability matrix
Redirect URLs	Multi-input	Yes	Must match platform
Review Status	Select	Yes	pending / approved
Notes	Textarea	No	Internal
 
Validations
•	Redirect URLs must be HTTPS (except localhost for dev)
•	Scopes ⊆ platform supported scopes
•	Only one ACTIVE app per platform + environment
•	Cannot activate if review_status ≠ approved
 
🟦 App Detail View (Read-Only)
Shows:
•	Masked secrets
•	Scope list
•	Linked platform
•	Activation history
•	Used by X tenants (count only)
 
5️⃣ DATABASE DESIGN
 
Table: platform_apps
Column	Type	Notes
id	uuid	PK
platform_id	uuid	FK → social_platforms
name	varchar	Internal name
environment	enum	dev / staging / prod
client_id	text	Encrypted
client_secret	text	Encrypted
scopes	json	Approved scopes
redirect_urls	json	Allowed redirects
review_status	enum	pending / approved / rejected
is_active	boolean	Only one active per env
metadata	json	Platform notes
created_at	timestamp	—
updated_at	timestamp	—
Indexes
•	unique(platform_id, environment, is_active=true)
 
6️⃣ API SPECIFICATION
 
API 1: List Platform Apps
GET /api/v1/admin/platforms/{id}/apps
Response
{
  "apps": [
    {
      "id": "uuid",
      "name": "Meta Prod",
      "environment": "prod",
      "is_active": true,
      "review_status": "approved"
    }
  ]
}
 
API 2: Create Platform App
POST /api/v1/admin/platforms/{id}/apps
Request
{
  "name": "Meta Production App",
  "environment": "prod",
  "client_id": "xxx",
  "client_secret": "yyy",
  "scopes": ["pages_manage_posts"],
  "redirect_urls": ["https://app.bizsocials.com/oauth/meta/callback"],
  "review_status": "approved"
}
Side Effects
•	Encrypt secrets
•	Save inactive
•	Audit log
 
API 3: Activate Platform App
POST /api/v1/admin/platforms/{id}/apps/{appId}/activate
Rules:
•	Deactivate existing active app (same env)
•	Require approved review_status
 
7️⃣ VALIDATION & ERROR STATES
Scenario	Response
Invalid scopes	422
Duplicate active app	409
Unapproved app activation	403
Non-admin access	403
 
8️⃣ AUDIT LOG EVENTS
Event	Actor	Payload
platform_app.created	Super Admin	app_id, platform
platform_app.updated	Super Admin	changed_fields
platform_app.activated	Super Admin	environment
platform_app.deactivated	System	previous_app
 
9️⃣ TEST CASES
 
Unit Tests
•	Scope validation
•	Single-active-app enforcement
•	Encryption at rest
 
Integration Tests
•	App activation swaps correctly
•	Secrets never returned in API
•	Review status enforced
 
E2E (Admin)
test('super admin creates and activates platform app', async () => {
  // Create app
  // Attempt activation without approval → fail
  // Approve → activate
  // Verify active badge
})
 
🔟 DEPENDENCIES & BLOCKERS
Required
•	Platform registry (1.1.1)
•	Capability matrix (1.1.2)
•	Encryption service
•	Admin auth
Explicitly NOT Allowed
•	Tenant-defined apps
•	Raw credential exposure
•	Environment guessing
 
✅ DEFINITION OF DONE (DoD)
✔ Apps are securely stored
✔ Secrets encrypted and masked
✔ Only one active app per env
✔ Capability-aware scopes
✔ Audit trail exists
✔ Tenants cannot access apps


PHASE 1 — Flow 1.2.2: OAuth Redirect & Token Exchange (System-Owned)
This flow connects Platform Apps (owned by BizSocials) to Tenant Social Accounts, without ever exposing credentials or raw OAuth artifacts.
 
1️⃣ PURPOSE (LOCK THIS HARD)
This flow is responsible for:
•	Initiating OAuth using BizSocials-owned Platform Apps
•	Exchanging authorization codes securely
•	Normalizing and storing tokens
•	Creating tenant-scoped social accounts
•	Validating access before activation
•	Ensuring zero credential leakage
Tenants never see:
•	Client ID
•	Client Secret
•	Raw access tokens
•	OAuth payloads
 
2️⃣ ACTORS & TRUST BOUNDARIES
Actor	Role
Tenant User	Initiates connection
BizSocials Backend	Executes OAuth
Platform OAuth Server	External
Super Admin	Observability only
Trust boundaries
•	OAuth happens server-side
•	Tokens stored encrypted
•	Platform Apps resolved by environment
 
3️⃣ COMPLETE USER JOURNEY
Preconditions
•	Platform exists (1.1.1)
•	Capability matrix exists (1.1.2)
•	Active Platform App exists for environment (1.2.1)
•	Tenant + workspace exist (Phase 0)
 
STEP 1 — Tenant Clicks “Connect Platform”
Tenant user clicks:
Connect → Meta / WhatsApp / X / YouTube
 
STEP 2 — System Generates OAuth Redirect
Backend:
•	Resolves active Platform App
•	Builds OAuth URL
•	Embeds signed state payload
Tenant is redirected to platform OAuth screen.
 
STEP 3 — Platform Authorization
User authorizes requested permissions on platform UI.
 
STEP 4 — OAuth Callback (System-Owned)
Platform redirects to:
/oauth/{platform}/callback
System:
•	Validates state
•	Exchanges code for tokens
•	Fetches account metadata
 
STEP 5 — Account Discovery
System fetches:
•	Pages
•	Channels
•	Numbers (WhatsApp)
•	Profiles
Stores unactivated records.
 
STEP 6 — Health Validation
System performs:
•	Token validity test
•	Permission coverage test
•	API probe
 
STEP 7 — Await Tenant Configuration
Accounts remain DISABLED until Flow 2.x onboarding completes.
 
4️⃣ UI TOUCHPOINTS (TENANT SIDE)
OAuth UI is never embedded. Always redirect.
 
Screen: Redirect Notice
URL
/app/w/:id/connect/{platform}
Content:
•	What BizSocials will access
•	Why permissions are required
•	“Continue to {Platform}” CTA
 
Screen: Return Status
URL
/app/w/:id/connect/{platform}/status
States:
•	Success (accounts discovered)
•	Partial permissions
•	Failed exchange
•	Retry allowed
 
5️⃣ API & ROUTE SPECIFICATION
 
API 1: Initiate OAuth Redirect
POST /api/v1/social/oauth/{platform}/redirect
Request
{
  "workspace_id": "uuid",
  "requested_capabilities": ["publish", "inbox"]
}
Response
{
  "redirect_url": "https://platform.com/oauth?...state=abc"
}
Side Effects
•	Resolve platform app
•	Generate signed state
•	Log intent
 
API 2: OAuth Callback (INTERNAL)
GET /oauth/{platform}/callback
Query
?code=xxx&state=yyy
Side Effects
1.	Validate state
2.	Exchange code → token
3.	Encrypt & store tokens
4.	Fetch account list
5.	Create social_account records (inactive)
6.	Audit logs
 
API 3: Get Discovered Accounts
GET /api/v1/social/accounts/discovered
Returns tenant-scoped discovered accounts awaiting configuration.
 
6️⃣ DATABASE ENTITIES
 
Table: social_accounts
Column	Notes
id	uuid
tenant_id	FK
workspace_id	FK
platform	meta / whatsapp / x
platform_account_id	External ID
display_name	Page / channel
status	discovered / active / error
capabilities	json
connected_at	timestamp
 
Table: social_account_tokens
Column	Notes
social_account_id	FK
access_token	encrypted
refresh_token	encrypted
expires_at	timestamp
scope	json
 
Table: oauth_states
Column	Notes
state	signed payload
tenant_id	FK
expires_at	TTL
 
7️⃣ VALIDATION & ERROR STATES
Scenario	Handling
State mismatch	Abort + log
Expired state	Retry
Partial permissions	Flag
Token exchange failure	Error + retry
API probe failure	Mark unhealthy
 
8️⃣ AUDIT EVENTS
Event	Payload
oauth.initiated	tenant, platform
oauth.callback_received	platform
token.exchange_success	account_id
token.exchange_failed	error
social_account.discovered	count
 
9️⃣ TEST CASES
 
Unit Tests
•	State signing/validation
•	Token encryption
•	Capability mapping
 
Integration Tests
•	OAuth redirect URL correctness
•	Token exchange mock
•	Account discovery normalization
 
E2E (Mocked OAuth)
test('tenant completes oauth and sees discovered accounts', async () => {
  // Initiate connect
  // Mock provider callback
  // Verify accounts listed as "Pending setup"
})
 
🔟 SECURITY & COMPLIANCE RULES (NON-NEGOTIABLE)
•	Tokens NEVER returned to frontend
•	Secrets NEVER logged
•	State signed + expiring
•	Tenant isolation enforced at query level
•	Admins see counts only, never tokens
 
✅ DEFINITION OF DONE (DoD)
✔ OAuth redirect works end-to-end
✔ Tokens exchanged & encrypted
✔ Accounts discovered but inactive
✔ Errors visible and retryable
✔ Audit trail complete
PHASE 1 — Flow 1.2.3: Social Account Activation & Configuration (Tenant-Owned)
OAuth only proves permission.
This flow turns permission into intentional, governed usage.
 
1️⃣ PURPOSE (WHY THIS FLOW EXISTS)
This flow allows a tenant to:
•	Decide which discovered accounts to activate
•	Bind each account to a workspace
•	Explicitly enable capabilities (publish, inbox, analytics)
•	Assign ownership (team / approvers)
•	Validate readiness before activation
•	Prevent accidental posting, inbox exposure, or compliance violations
Nothing is active by default. Ever.
 
2️⃣ ACTORS & PERMISSIONS
Actor	Role
Tenant Owner	Full control
Workspace Admin	Can activate within workspace
Member	Read-only
Super Admin	Observability only
Permission Required
social_accounts.manage
 
3️⃣ COMPLETE USER JOURNEY
Preconditions
•	OAuth completed successfully (Flow 1.2.2)
•	Discovered accounts exist
•	Tenant has ≥1 workspace
•	Platform capability matrix exists
 
STEP 1 — View Discovered Accounts
User navigates to:
/app/w/:workspaceId/social-accounts
Sees list of discovered (inactive) accounts.
 
STEP 2 — Select Account to Activate
User clicks:
Configure → Facebook Page “Acme Corp”
 
STEP 3 — Configuration Wizard (MANDATORY)
Wizard enforces explicit decisions.
 
STEP 4 — Validation & Readiness Check
System validates:
•	Token validity
•	Permission coverage
•	Workspace ownership
•	Capability compatibility
 
STEP 5 — Activate Account
If validation passes:
•	Account becomes ACTIVE
•	Features unlocked
•	Webhooks registered
•	Usage tracking begins
 
4️⃣ UI SCREENS (SCREEN-BY-SCREEN)
 
Screen 1: Social Accounts Landing
URL
/app/w/:id/social-accounts
Table Columns
•	Platform
•	Account Name
•	Status (Discovered / Active / Error)
•	Capabilities
•	Workspace
•	Last Sync
•	Actions
Primary CTA
Activate Account
 
Screen 2: Activation Wizard — Step 1 (Workspace & Ownership)
Fields
•	Workspace (required)
•	Assigned Team (optional)
•	Primary Owner (required)
Validation:
•	Workspace must belong to tenant
•	Owner must be workspace member
 
Screen 3: Step 2 — Capability Selection
Toggles (per capability matrix)
•	Publishing
•	Inbox
•	Analytics
•	Ads (future)
Rules:
•	Disabled if platform does not support
•	Warning if partial permissions
 
Screen 4: Step 3 — Governance Rules
Options
•	Approval required before publish (yes/no)
•	Allowed posting hours
•	Default inbox SLA
•	Auto-assign inbox conversations
 
Screen 5: Step 4 — Notifications & Limits
•	Token expiry warnings
•	Rate limit visibility
•	Compliance warnings
 
Screen 6: Review & Activate
Summary + Activate Account CTA
 
5️⃣ API-BY-API SPECIFICATION
 
API 1: Get Discovered Accounts
GET /api/v1/social/accounts/discovered
Returns inactive accounts only.
 
API 2: Activate Social Account
POST /api/v1/social/accounts/{id}/activate
Request
{
  "workspace_id": "uuid",
  "team_id": "uuid",
  "enabled_capabilities": ["publish", "inbox"],
  "approval_required": true,
  "posting_hours": {
    "from": "09:00",
    "to": "18:00",
    "timezone": "Asia/Kolkata"
  },
  "notification_preferences": {
    "token_expiry": true,
    "rate_limit": true
  }
}
Response
{
  "success": true,
  "status": "active"
}
 
Side Effects (TRANSACTIONAL)
1.	Validate tenant + workspace ownership
2.	Validate token + permissions
3.	Update social_accounts.status = active
4.	Persist config
5.	Register webhooks
6.	Initialize usage counters
7.	Fire activation event
8.	Audit log
 
6️⃣ DATABASE ENTITIES
 
Table: social_account_configs
Column	Notes
social_account_id	FK
workspace_id	FK
enabled_capabilities	json
approval_required	boolean
posting_hours	json
assigned_team_id	FK
notification_prefs	json
 
Table: social_account_usage
Column	Notes
social_account_id	FK
period_start	date
metric	string
value	bigint
 
7️⃣ VALIDATIONS & ERROR STATES
Scenario	Behavior
Token expired	Block activation
Missing permission	Warn + allow partial
Workspace mismatch	403
Already active	409
Webhook failure	Activate with warning
 
8️⃣ AUDIT EVENTS
Event	Payload
social_account.activated	account_id, workspace
social_account.configured	capabilities
webhook.registered	platform
activation.failed	reason
 
9️⃣ TEST CASES
 
Unit Tests
•	Capability validation
•	Config persistence
•	Status transitions
 
Integration Tests
•	Activation transaction
•	Webhook registration mock
•	Usage initialization
 
E2E (Playwright)
test('tenant activates discovered account', async () => {
  // OAuth done
  // Navigate to discovered accounts
  // Configure + activate
  // Verify account shows ACTIVE
})
 
🔟 SECURITY & GOVERNANCE GUARANTEES
•	No activation without workspace binding
•	Capabilities opt-in only
•	Approval enforced at publish time
•	Full audit trail
 
✅ DEFINITION OF DONE (DoD)
✔ Account inactive → active via wizard
✔ Workspace-bound
✔ Capabilities enforced
✔ Webhooks live
✔ Audit complete


PHASE 2 — Flow 2.1: Tenant Onboarding Wizard (End-to-End)
This flow ensures no tenant ever lands in a dead-end,
and no capability is unlocked without context, intent, and readiness.
 
1️⃣ PURPOSE (LOCK THIS)
The Tenant Onboarding Wizard must:
•	Guide a new tenant from account creation → first value
•	Enforce correct order of setup
•	Surface what’s missing and why
•	Be resumable, auditable, and idempotent
•	Act as the single source of onboarding truth
This is not optional UX.
If this flow fails, activation, retention, and trust fail.
 
2️⃣ ACTORS & ENTRY CONDITIONS
Actors
Actor	Role
Tenant Owner	Primary driver
Workspace Admin	Can assist
Super Admin	Observability only
Entry Conditions
•	Tenant exists
•	User authenticated
•	Tenant onboarding NOT completed
 
3️⃣ ONBOARDING STEPS (LOCKED ORDER)
Steps are state-driven, not time-driven.
Step	Key	Mandatory
1	account_created	✅
2	email_verified	✅
3	organization_setup	✅
4	first_workspace_created	✅
5	tool_selection	❌
6	first_social_account_connected	❌
7	first_post_created	❌
8	invite_team	❌
9	tour_completed	❌
Only Step 1–4 are blocking.
 
4️⃣ COMPLETE USER JOURNEY
 
STEP 0 — Forced Entry
Any attempt to access:
/app/*
If:
tenant.onboarding_completed_at IS NULL
➡ Redirect to:
/onboarding
 
STEP 1 — Welcome & Progress Overview
URL
/onboarding
UI
•	Welcome message
•	Progress bar
•	Checklist with statuses
•	“Continue setup” CTA
 
STEP 2 — Organization Setup
(delegates to Flow 0.1.1)
•	Company details
•	Legal boundary
•	Profile completion
 
STEP 3 — Workspace Setup
(delegates to Flow 0.1.2)
•	Default workspace creation
•	Purpose selection
•	Approval defaults
 
STEP 4 — Tool Selection (NEW)
Tenant selects which modules to enable:
•	Social Publishing
•	Inbox
•	WhatsApp
•	Analytics
This does not connect accounts, only sets intent.
 
STEP 5 — Connect First Social Account
(delegates to Flow 1.2.x)
Guided:
•	Platform explanation
•	OAuth
•	Discovery
•	Activation
 
STEP 6 — First Action (Value Moment)
Depending on enabled tool:
•	Create first post or
•	View inbox or
•	Send WhatsApp template
 
STEP 7 — Invite Team (Optional)
Invite users with roles.
 
STEP 8 — Completion
•	Mark onboarding complete
•	Persist timestamp
•	Show “You’re live” screen
 
5️⃣ UI SCREENS (DETAILED)
 
Screen 1: Onboarding Hub
URL
/onboarding
Components
•	Progress bar
•	Checklist (clickable)
•	“Resume” CTA
•	Skip optional steps
 
Screen 2: Tool Selection
URL
/onboarding/tools
Options
•	Publishing
•	Inbox
•	WhatsApp
•	Analytics
Rules:
•	WhatsApp requires additional compliance later
•	Can skip all
 
Screen 3: Completion
URL
/onboarding/complete
Content:
•	Success message
•	Next suggested actions
•	Go to Dashboard
 
6️⃣ API-BY-API SPECIFICATION
 
API 1: Get Onboarding Status
GET /api/v1/onboarding/status
Response
{
  "current_step": "organization_setup",
  "completed_steps": [
    "account_created",
    "email_verified"
  ],
  "is_complete": false,
  "progress": 25
}
 
API 2: Update Onboarding Step
POST /api/v1/onboarding/step
Request
{
  "step": "tool_selection",
  "metadata": {
    "tools": ["publishing", "inbox"]
  }
}
 
API 3: Complete Onboarding
POST /api/v1/onboarding/complete
Side Effects
•	Set tenant.onboarding_completed_at
•	Fire event
•	Audit log
 
7️⃣ DATABASE ENTITIES
Table: tenant_onboarding (already exists)
Used as:
•	State machine
•	Progress tracker
•	Recovery anchor
 
8️⃣ VALIDATION & ERROR STATES
Scenario	Behavior
Step skipped	Block if mandatory
Step repeated	Idempotent
Partial completion	Resume
Abandoned onboarding	Flag after 30 days
Role mismatch	Block non-owner
 
9️⃣ AUDIT EVENTS
Event	Notes
onboarding.started	tenant
onboarding.step_completed	step
onboarding.completed	timestamp
onboarding.abandoned	system
 
🔟 TEST CASES
 
Unit Tests
•	Step transition rules
•	Progress calculation
•	Completion logic
 
Integration Tests
•	Redirect enforcement
•	Step delegation integrity
 
E2E (Playwright)
test('tenant completes onboarding end-to-end', async () => {
  // Login
  // Redirect to /onboarding
  // Complete org + workspace
  // Select tools
  // Connect social account
  // Reach dashboard
})
 
🔒 GOVERNANCE GUARANTEES
•	No dashboard without onboarding
•	No feature without intent
•	No silent skips
•	Full auditability
 
✅ DEFINITION OF DONE (DoD)
✔ Forced onboarding enforced
✔ Progress resumable
✔ Delegates to existing flows
✔ Completion persisted
✔ Audit trail complete


PHASE 2 — Flow 2.2: First-Time Value Journey (Post-Onboarding Activation)
Onboarding gets users in.
First-time value keeps them using.
 
1️⃣ PURPOSE (LOCK THIS)
This flow must:
•	Convert setup into real usage
•	Guide users to a meaningful first success
•	Prevent blank screens and confusion
•	Adapt based on enabled tools
•	Be repeatable, observable, and measurable
If this flow fails, churn is guaranteed.
 
2️⃣ ENTRY CONDITIONS
User enters this flow when:
•	Tenant onboarding is completed
•	At least one workspace exists
•	At least one tool is enabled (from Flow 2.1)
 
3️⃣ ACTIVATION PATHS (DETERMINISTIC)
Activation path is computed, not chosen arbitrarily.
Priority	Condition	Activation Path
1	Social Publishing enabled	Create First Post
2	Inbox enabled	Respond to First Message
3	WhatsApp enabled	Send First Template
4	Analytics only	View First Dashboard
Fallback	No tools enabled	Enable a Tool CTA
 
4️⃣ COMPLETE USER JOURNEY
 
STEP 1 — Activation Gate
User lands on:
/app/dashboard
System evaluates:
•	Enabled tools
•	Connected social accounts
•	Existing activity
➡ Redirects to best activation path
 
STEP 2 — Guided First Action
User sees:
•	Contextual explanation
•	Guided UI (inline hints)
•	Pre-filled defaults
Example:
“Let’s publish your first post — it takes under 2 minutes.”
 
STEP 3 — Successful Action
System confirms:
•	Post scheduled/published
•	Message sent/replied
•	Dashboard loaded
User sees success state, not just completion.
 
STEP 4 — Reinforcement
System suggests:
•	Next logical action
•	Short checklist
•	Optional tour tips
 
5️⃣ UI SCREENS (PATH-WISE)
 
Path A: First Post Creation
URL
/app/w/:id/posts/create?first=true
UI:
•	Simplified editor
•	One platform pre-selected
•	Inline validation
•	Disable advanced options initially
Success:
•	Celebration state
•	“View Calendar” CTA
 
Path B: First Inbox Reply
URL
/app/w/:id/inbox?first=true
UI:
•	Highlight one conversation
•	Suggested reply
•	Auto-assign to user
Success:
•	“You replied to your first message” banner
 
Path C: First WhatsApp Message
URL
/app/w/:id/whatsapp/templates?first=true
UI:
•	Sample template
•	Compliance notice
•	Send to test number
 
Path D: Analytics View
URL
/app/w/:id/analytics?first=true
UI:
•	Empty state explanation
•	“Connect account” CTA if needed
 
6️⃣ API INVOLVEMENT
No new APIs required.
This flow orchestrates existing APIs.
But must record activation events.
 
API: Record Activation Event
POST /api/v1/activation/events
{
  "event": "first_post_created",
  "workspace_id": "uuid"
}
 
7️⃣ DATABASE TRACKING
Table: tenant_activation_metrics (NEW)
Column	Purpose
tenant_id	FK
metric	first_post_created, first_reply
occurred_at	timestamp
 
8️⃣ VALIDATION & ERROR STATES
Scenario	Behavior
No connected account	Redirect to connect
Action fails	Explain why + retry
Permission missing	Show role info
Rate limit	Explain + delay
 
9️⃣ AUDIT & TELEMETRY
Audit Events
•	activation.path_selected
•	activation.completed
Product Metrics
•	Time to first value (TTFV)
•	Drop-off point
•	Most successful activation path
 
🔟 TEST CASES
 
Unit Tests
•	Activation path selection logic
 
Integration Tests
•	Event recording
•	Path redirection
 
E2E (Playwright)
test('new tenant publishes first post', async () => {
  // Complete onboarding
  // Redirected to create post
  // Publish
  // See success banner
})
 
🔒 GOVERNANCE GUARANTEES
•	No forced publishing
•	No auto-posting
•	Always reversible
•	Fully auditable
 
✅ DEFINITION OF DONE (DoD)
✔ Activation path chosen deterministically
✔ First success achieved
✔ User reinforced
✔ Metrics recorded


PHASE 3 — META PLATFORM (Facebook · Instagram · Messenger)
Status: BLOCKING PHASE (Must be flawless)
Meta is not “an integration”.
It is three platforms + one policy engine + one compliance system.
If Phase 3 is weak:
•	Accounts disconnect
•	Publishing fails silently
•	WhatsApp approval becomes impossible later
•	Tenants lose trust
 
🔐 PHASE 3 — NON-NEGOTIABLE PRINCIPLES (LOCK THESE)
1.	No raw OAuth dumps
2.	No auto-publishing without confirmation
3.	No hidden permission loss
4.	No silent token expiry
5.	Every Meta action must be traceable
6.	Meta ≠ Facebook Page only
7.	Instagram ≠ Facebook dependency leakage
8.	Messenger ≠ Publishing capability
 
📦 PHASE 3 SCOPE (LOCKED)
Meta Phase covers:
Capability	Included
Facebook Pages	✅
Instagram Business	✅
Messenger Inbox	✅
Publishing	✅
Scheduling	✅
Inbox (comments + DMs)	✅
Analytics (basic)	✅
Re-auth & Recovery	✅
Permission Drift Detection	✅
❌ Ads Manager
❌ Commerce
❌ Business Verification
❌ WhatsApp (Phase 4)
 
PHASE 3 — FLOW MAP
We will implement 6 flows, strictly in order.
Flow ID	Flow Name
3.1	Meta Platform Connection Wizard
3.2	Facebook Page Publishing
3.3	Instagram Publishing
3.4	Unified Meta Inbox
3.5	Meta Analytics
3.6	Token Health, Reauth & Drift Recovery
 
🔵 FLOW 3.1 — Meta Platform Connection Wizard (CRITICAL)
This is the most important flow in Phase 3.
 
🎯 Purpose
Safely connect:
•	Facebook Pages
•	Instagram Business Accounts
•	Messenger Inbox
With:
•	Explicit consent
•	Predictable permissions
•	Recoverable failures
 
1️⃣ USER JOURNEY
1.	User clicks “Connect Meta Account”
2.	BizSocials explains what will happen
3.	OAuth redirect to Meta
4.	User grants permissions
5.	BizSocials fetches available assets
6.	User selects what to connect
7.	BizSocials validates access
8.	Accounts activated + monitored
 
2️⃣ UI SCREENS
 
Screen 1 — Meta Introduction
URL
/app/w/:id/social-accounts/connect/meta
Content:
•	What BizSocials will access
•	Why each permission is required
•	What BizSocials will NOT do
•	Compliance notice
CTA:
•	Continue to Meta
 
Screen 2 — OAuth Redirect (External)
Handled by Meta
(BizSocials shows “Redirecting to Meta…” overlay)
 
Screen 3 — Account Discovery
URL
/app/w/:id/social-accounts/meta/select
List:
•	Facebook Pages
•	Connected Instagram Business accounts
•	Messenger availability
Columns:
•	Name
•	Type
•	Permissions status
•	Eligible features
User selects:
•	Pages
•	IG accounts
•	Inbox enablement
 
Screen 4 — Configuration
URL
/app/w/:id/social-accounts/meta/configure
Fields:
•	Workspace
•	Default team
•	Enable:
o	Publishing
o	Inbox
o	Analytics
•	Approval required toggle
 
Screen 5 — Health Check
URL
/app/w/:id/social-accounts/meta/verify
Checks:
•	Token validity
•	Page publish test (draft)
•	Inbox access
•	Webhook handshake
 
Screen 6 — Success / Warning
•	Connected successfully
•	Or partially connected (with explanation)
 
3️⃣ API SPEC (FLOW 3.1)
 
API: Start Meta OAuth
GET /api/v1/integrations/meta/connect
Returns:
{
  "redirect_url": "https://facebook.com/oauth?..."
}
 
API: OAuth Callback
GET /api/v1/integrations/meta/callback
Side effects:
•	Store access token (encrypted)
•	Fetch assets
•	DO NOT auto-activate
 
API: Discover Assets
GET /api/v1/integrations/meta/assets
Returns:
•	Pages
•	IG accounts
•	Permissions per asset
 
API: Activate Assets
POST /api/v1/integrations/meta/activate
Request:
{
  "assets": [
    {
      "platform": "facebook",
      "account_id": "123",
      "features": ["publish","inbox"]
    }
  ]
}
 
4️⃣ DATABASE ENTITIES
social_accounts
•	platform = meta_facebook | meta_instagram
•	status
•	token_health
•	permission_snapshot
social_account_configs
•	publishing_enabled
•	inbox_enabled
•	analytics_enabled
•	approval_required
integration_tokens
•	encrypted_token
•	expires_at
•	refreshable
•	scopes
 
5️⃣ VALIDATION & ERRORS
Error	UX Behavior
Permission denied	Explain what’s missing
Partial permissions	Warn + limit features
Token expired	Block activation
Page already connected	Show ownership
 
6️⃣ AUDIT EVENTS
•	meta.oauth.initiated
•	meta.oauth.completed
•	meta.asset.activated
•	meta.asset.failed
 
7️⃣ TEST CASES
Unit
•	Permission mapping
•	Asset eligibility
Integration
•	OAuth callback handling
•	Token storage
E2E
test('connect facebook page successfully', async () => {
  // Start wizard
  // Mock Meta OAuth
  // Select page
  // Verify activation
})
 
✅ DoD — Flow 3.1
✔ Meta OAuth works
✔ Assets selectable
✔ Permissions visible
✔ Tokens monitored
✔ Audit logs written
 
🔒 BLOCKERS CHECK
Blocker	Status
Platform Registry (Phase 1)	MUST be complete
Social Account tables	Must exist
Token encryption	Must exist
Webhook infra	Needed


PHASE 3 — Flow 3.2: Facebook Page Publishing
This flow governs how content leaves BizSocials and enters the public internet.
There is zero tolerance for silent failure, accidental posting, or permission leakage.
 
1️⃣ PURPOSE (LOCK THIS)
Enable safe, predictable, auditable publishing to Facebook Pages with:
•	Manual + scheduled publishing
•	Approval workflows
•	Clear previews
•	Platform-specific validation
•	Guaranteed observability
If this flow fails:
•	Tenants lose trust
•	Brands get embarrassed
•	BizSocials gets churned
 
2️⃣ ENTRY CONDITIONS (STRICT)
This flow is accessible ONLY if:
✔ Meta connection completed (Flow 3.1)
✔ At least one Facebook Page connected
✔ Page has PUBLISH_PAGES permission
✔ Publishing enabled in social_account_configs
✔ User has publish permission in workspace
If any condition fails, user must see WHY.
 
3️⃣ COMPLETE USER JOURNEY
 
STEP 1 — Open Composer
URL
/app/w/:workspaceId/posts/create
User selects:
•	Platform: Facebook Page
System:
•	Loads FB-specific constraints
•	Pre-fills defaults (if first post)
 
STEP 2 — Compose Post
User enters:
•	Post text
•	Media (optional)
•	Link (optional)
System validates live.
 
STEP 3 — Preview
User sees:
•	Exact Facebook feed preview
•	Page name + avatar
•	Truncation indicators
 
STEP 4 — Choose Action
Buttons (explicit, no ambiguity):
•	Save Draft
•	Submit for Approval
•	Schedule
•	Publish Now
 
STEP 5 — Execution
Depending on choice:
•	Draft saved
•	Approval triggered
•	Job scheduled
•	Post published
 
STEP 6 — Confirmation
User sees:
•	Success banner
•	Link to post (if published)
•	Calendar entry (if scheduled)
 
4️⃣ UI SCREENS & COMPONENTS
 
🖥 Screen: Post Composer (Facebook Mode)
Fields
Field	Required	Validation
Post Text	Yes	max 63,206 chars
Media	No	JPG/PNG/MP4 only
Link	No	Valid URL
Page Selector	Yes	Connected FB pages
 
⚠️ Live Validation Rules
•	Text + link allowed
•	Media + link allowed
•	Empty post ❌
•	Unsupported media ❌
•	Multiple videos ❌
Errors shown inline.
 
👁 Preview Component
•	Real FB typography
•	“See more” truncation
•	Media crop preview
 
5️⃣ API SPEC (DETAILED)
 
API: Create Post Draft
POST /api/v1/posts
{
  "workspace_id": "uuid",
  "platform": "facebook",
  "page_id": "fb_page_id",
  "content": {
    "text": "Hello Facebook!",
    "media_ids": [],
    "link": null
  }
}
Side effects:
•	Creates posts record
•	Status = draft
 
API: Submit for Approval
POST /api/v1/posts/{id}/submit
Side effects:
•	Status → pending_approval
•	Approval workflow started
 
API: Schedule Post
POST /api/v1/posts/{id}/schedule
{
  "publish_at": "2026-02-10T09:00:00Z"
}
Validations:
•	Time ≥ now + 5 min
•	Workspace timezone respected
 
API: Publish Now
POST /api/v1/posts/{id}/publish
Validations:
•	Permission check
•	Approval bypass only if allowed
Side effects:
•	Dispatch PublishFacebookPostJob
 
6️⃣ BACKGROUND JOB (CRITICAL)
Job: PublishFacebookPostJob
Steps:
1.	Validate token
2.	Validate page permission
3.	Publish via Meta Graph API
4.	Capture response
5.	Update post status
6.	Store external post ID
7.	Emit events
Retry policy:
•	3 retries
•	Backoff
•	Hard fail on permission errors
 
7️⃣ DATABASE ENTITIES
posts
•	id
•	workspace_id
•	platform = facebook
•	status (draft, scheduled, published, failed)
•	publish_at
•	external_post_id
post_targets
•	post_id
•	platform_account_id (FB page)
•	status
post_failures
•	post_id
•	error_code
•	error_message
•	retriable
 
8️⃣ VALIDATION & ERROR STATES
Scenario	Behavior
Token expired	Block publish, show reconnect
Page permission lost	Disable publish, warn
Rate limit hit	Retry + notify
API failure	Mark failed + retry
Approval required	Block publish
All errors must show:
•	What failed
•	Why
•	What user can do
 
9️⃣ AUDIT LOG EVENTS
Event	When
post.created	Draft
post.submitted	Approval
post.scheduled	Scheduled
post.published	Success
post.failed	Failure
Payload includes:
•	user_id
•	workspace_id
•	page_id
•	timestamp
 
🔟 TEST CASES
 
Unit Tests
•	Content validation
•	Permission enforcement
•	Status transitions
 
Integration Tests
•	Draft → publish flow
•	Job execution with mock Meta API
 
E2E (Playwright)
test('publish facebook page post', async () => {
  // Connect FB page
  // Create post
  // Publish now
  // Verify success banner
})
 
🔒 SECURITY & COMPLIANCE
✔ No auto-publishing
✔ Explicit confirmation required
✔ Permission drift detected
✔ Full audit trail
 
✅ DEFINITION OF DONE (DoD)
✔ User can publish FB post
✔ Failures explained
✔ Jobs retry safely
✔ Audit logs written
✔ Tests pass
 
🧭 BLOCKERS CHECK
Dependency	Status
Flow 3.1	REQUIRED
Job queue	REQUIRED
Audit logging	REQUIRED
Media upload	REQUIRED


PHASE 3 — Flow 3.3: Instagram Publishing
Instagram publishing is media-first, permission-sensitive, and compliance-heavy.
One silent failure here = broken trust.
This flow is intentionally more restrictive than Facebook.
 
1️⃣ PURPOSE (LOCK THIS)
Enable safe, predictable publishing to Instagram Business Accounts, supporting:
•	Feed posts (single image/video)
•	Carousels
•	Captions + hashtags
•	Scheduled publishing
•	Approval workflows
•	Explicit validation before publish
❌ NO Stories
❌ NO Reels (Phase 3.4+)
❌ NO Personal accounts
 
2️⃣ ENTRY CONDITIONS (STRICT)
User can enter this flow ONLY if:
✔ Meta integration completed (Flow 3.1)
✔ Instagram Business account connected
✔ IG account linked to Facebook Page
✔ instagram_basic, instagram_content_publish granted
✔ Publishing enabled in social_account_configs
✔ User has publish permission
Failure must explain exactly why.
 
3️⃣ COMPLETE USER JOURNEY
 
STEP 1 — Open Composer
URL
/app/w/:workspaceId/posts/create
User selects:
•	Platform: Instagram
System:
•	Switches composer to IG mode
•	Loads IG-specific constraints
 
STEP 2 — Compose Post
User must provide:
•	Media (required)
•	Caption (optional)
System enforces hard validation.
 
STEP 3 — Preview
User sees:
•	Square / portrait crop preview
•	Caption truncation
•	Hashtag grouping
 
STEP 4 — Choose Action
Buttons:
•	Save Draft
•	Submit for Approval
•	Schedule
•	Publish Now
 
STEP 5 — Execution
System:
•	Creates IG media container
•	Publishes container
•	Confirms success
 
4️⃣ UI SCREENS & COMPONENTS
 
🖥 Screen: Instagram Composer
 
Required Fields
Field	Required	Rules
Media	✅ Yes	Image or Video
Caption	❌ No	max 2,200 chars
Hashtags	❌ No	≤ 30
 
Media Rules (ENFORCED)
Images
•	JPG / PNG
•	Min: 320px
•	Max: 1440px
•	Aspect ratio:
o	1:1
o	4:5
o	1.91:1
Videos
•	MP4 only
•	≤ 60 seconds
•	≤ 100MB
•	Aspect ratio same as image
Carousel
•	2–10 media items
•	All images OR all videos (no mixing)
 
❌ Disallowed
•	Text-only posts
•	Mixed media types
•	Unsupported ratios
•	More than 30 hashtags
 
👁 Preview Component
•	Real IG feed mock
•	Crop indicators
•	“More” caption cutoff
 
5️⃣ API SPEC (DETAILED)
 
API: Create Draft
POST /api/v1/posts
{
  "workspace_id": "uuid",
  "platform": "instagram",
  "account_id": "ig_business_id",
  "content": {
    "caption": "Hello Instagram! #bizsocials",
    "media_ids": ["uuid1", "uuid2"]
  }
}
Side effects:
•	Post status = draft
 
API: Publish Now
POST /api/v1/posts/{id}/publish
Side effects:
•	Dispatch PublishInstagramPostJob
 
6️⃣ BACKGROUND JOB (CRITICAL)
Job: PublishInstagramPostJob
Steps (MANDATORY ORDER):
1.	Validate access token
2.	Validate IG account status
3.	Upload media container(s)
4.	Wait for container processing
5.	Publish container
6.	Capture IG post ID
7.	Update post status
8.	Emit events
Retry rules:
•	Processing delay → retry
•	Permission error → hard fail
•	Rate limit → backoff retry
 
7️⃣ DATABASE ENTITIES
posts
•	platform = instagram
•	status
•	publish_at
•	external_post_id
post_media
•	media_id
•	type (image/video)
•	order_index
post_failures
•	error_code
•	error_message
•	retriable
 
8️⃣ VALIDATION & ERROR STATES
Scenario	Behavior
No media	Block publish
Unsupported ratio	Inline error
Token expired	Reconnect CTA
Account disconnected	Disable publish
Container processing timeout	Retry + notify
Errors must be actionable.
 
9️⃣ AUDIT LOG EVENTS
Event	Payload
post.created	draft
post.submitted	approval
post.scheduled	time
post.published	ig_post_id
post.failed	error
 
🔟 TEST CASES
 
Unit Tests
•	Media validation rules
•	Caption length enforcement
•	Carousel rules
 
Integration Tests
•	Container creation mock
•	Publish job success/failure
 
E2E (Playwright)
test('publish instagram post', async () => {
  // Upload image
  // Add caption
  // Publish
  // Verify success banner
})
 
🔒 SECURITY & COMPLIANCE
✔ No silent retries
✔ No partial publishing
✔ Explicit user intent required
✔ Full audit trail
 
✅ DEFINITION OF DONE (DoD)
✔ IG post published successfully
✔ Errors explained clearly
✔ Jobs retry safely
✔ Audit logs present
✔ Tests pass
 
🚧 BLOCKERS CHECK
Dependency	Status
Flow 3.1 Meta Connect	REQUIRED
Media validation	REQUIRED
Job queue	REQUIRED
Approval engine	REQUIRED


PHASE 3 — Flow 3.4: Instagram Stories Publishing
Stories are ephemeral (24h), mobile-native, and high-risk if mis-published.
BizSocials must make Stories safe, intentional, and observable.
 
1️⃣ PURPOSE (LOCK THIS)
Enable safe publishing of Instagram Stories for Instagram Business accounts, supporting:
•	Image & video stories
•	Stickers (basic, Phase 1)
•	Swipe-up / Link sticker (eligible accounts only)
•	Scheduling
•	Approval workflows
•	Post-publish observability
❌ NO text-only stories
❌ NO polls/questions (future phase)
❌ NO personal accounts
 
2️⃣ ENTRY CONDITIONS (STRICT)
User can enter this flow ONLY if:
✔ Flow 3.1 (Meta Connect) complete
✔ Instagram Business account connected
✔ Account supports Stories publishing (API-verified)
✔ instagram_content_publish permission present
✔ Publishing enabled in social_account_configs
✔ User has publish permission
If any check fails, the UI must show WHY + WHAT NEXT.
 
3️⃣ COMPLETE USER JOURNEY
 
STEP 1 — Open Composer
URL
/app/w/:workspaceId/posts/create
User selects:
•	Platform: Instagram
•	Content Type: Story
System:
•	Switches composer into Story mode
•	Loads Story-specific constraints
 
STEP 2 — Add Media (REQUIRED)
User uploads:
•	Image OR Video (single item only)
System:
•	Enforces 9:16 aspect ratio
•	Shows crop tool if needed
 
STEP 3 — Add Optional Enhancements
Optional:
•	Caption (very limited)
•	Link sticker (if eligible)
System:
•	Validates eligibility
•	Shows preview overlay
 
STEP 4 — Preview Story
User sees:
•	Full-screen mobile preview
•	Safe area guides
•	CTA placement
 
STEP 5 — Choose Action
Buttons:
•	Save Draft
•	Submit for Approval
•	Schedule
•	Publish Now
 
STEP 6 — Execution
System:
•	Creates Story media container
•	Publishes to IG Stories
•	Confirms success
 
4️⃣ UI SCREENS & COMPONENTS
 
🖥 Screen: Instagram Story Composer
 
Required Fields
Field	Required	Rules
Media	✅ Yes	Image or Video
Caption	❌ Optional	≤ 125 chars
Link Sticker	❌ Optional	URL + label
 
Media Rules (STRICT)
Images
•	JPG / PNG
•	9:16 (1080×1920)
•	≤ 8MB
Videos
•	MP4
•	9:16
•	≤ 15 seconds
•	≤ 100MB
❌ No carousels
❌ No landscape
❌ No mixed media
 
Link Sticker Rules
Only if:
•	IG account eligible
•	Business account verified
Validation:
•	Valid HTTPS URL
•	Label ≤ 30 chars
 
👁 Story Preview
•	Full-screen mobile mock
•	Tap zones visible
•	CTA overlay preview
 
5️⃣ API SPEC (DETAILED)
 
API: Create Story Draft
POST /api/v1/posts
{
  "workspace_id": "uuid",
  "platform": "instagram",
  "content_type": "story",
  "account_id": "ig_business_id",
  "content": {
    "media_id": "uuid",
    "caption": "New launch today!",
    "link": {
      "url": "https://example.com",
      "label": "Learn more"
    }
  }
}
Side effects:
•	Post status = draft
•	content_type = story
 
API: Publish Story
POST /api/v1/posts/{id}/publish
Side effects:
•	Dispatch PublishInstagramStoryJob
 
6️⃣ BACKGROUND JOB (CRITICAL)
Job: PublishInstagramStoryJob
Execution steps (MANDATORY):
1.	Validate token
2.	Validate Story eligibility
3.	Upload media container (media_type=STORIES)
4.	Apply link sticker metadata
5.	Publish container
6.	Capture story ID
7.	Set expiry timestamp (+24h)
8.	Emit events
Retry policy:
•	Media processing delay → retry
•	Permission error → hard fail
•	Rate limit → backoff
 
7️⃣ DATABASE ENTITIES
posts
•	platform = instagram
•	content_type = story
•	status
•	publish_at
•	external_story_id
•	expires_at
post_media
•	media_id
•	type
•	aspect_ratio
post_story_metadata
•	post_id
•	link_url
•	link_label
 
8️⃣ VALIDATION & ERROR STATES
Scenario	Behavior
Wrong aspect ratio	Block + crop UI
Video too long	Inline error
Link not allowed	Disable CTA
Token expired	Reconnect prompt
API publish failure	Retry + notify
All errors must explain:
•	What failed
•	Why
•	How to fix
 
9️⃣ AUDIT LOG EVENTS
Event	Description
story.created	Draft
story.submitted	Approval
story.scheduled	Scheduled
story.published	Live
story.failed	Failed
Payload includes:
•	workspace_id
•	ig_account_id
•	expires_at
 
🔟 TEST CASES
 
Unit Tests
•	Aspect ratio validation
•	Video duration enforcement
•	Link eligibility rules
 
Integration Tests
•	Story container creation
•	Publish success/failure paths
 
E2E (Playwright)
test('publish instagram story', async () => {
  // Upload 9:16 image
  // Add link sticker
  // Publish
  // Verify success banner
})
 
🔒 SECURITY & COMPLIANCE
✔ Explicit story intent required
✔ No silent publishing
✔ Expiry tracked
✔ Full audit trail
 
✅ DEFINITION OF DONE (DoD)
✔ Story published successfully
✔ Eligibility enforced
✔ Failures actionable
✔ Audit logs written
✔ Tests pass
 
🚧 BLOCKERS CHECK
Dependency	Status
Flow 3.1 Meta Connect	REQUIRED
Media cropper	REQUIRED
Job queue	REQUIRED
Approval engine	REQUIRED


PHASE 3 — Flow 3.5: Cross-Platform Publishing (Facebook + Instagram)
This flow allows one intent → multiple platforms, without losing platform-specific correctness, approvals, or auditability.
Core rule:
👉 One post, many targets — but each target behaves as if it was published alone.
 
1️⃣ PURPOSE (LOCK THIS)
Enable users to:
•	Create one post
•	Publish it to Facebook Pages + Instagram Feed (optionally Stories later)
•	While preserving:
o	Platform-specific constraints
o	Separate approvals
o	Independent success/failure tracking
o	Clear previews per platform
❌ No “best effort” publishing
❌ No silent partial success
❌ No shared failure states
 
2️⃣ ENTRY CONDITIONS (STRICT)
Cross-platform publishing is allowed ONLY if:
✔ At least two platforms selected (FB + IG)
✔ All selected accounts are connected and healthy
✔ User has publish permission for each platform
✔ Required approvals resolved (per platform)
✔ Content is compatible OR user accepts overrides
If not, UI must clearly explain which platform blocks publishing and why.
 
3️⃣ COMPLETE USER JOURNEY
 
STEP 1 — Open Composer
URL
/app/w/:workspaceId/posts/create
User selects:
•	Platforms: ✅ Facebook Page(s), ✅ Instagram Feed
System:
•	Enters Multi-Platform Mode
•	Loads constraints for all selected platforms
 
STEP 2 — Compose Core Content
User provides:
•	Base caption/text
•	Media (images/videos)
•	Link (optional)
System:
•	Runs per-platform validation in parallel
 
STEP 3 — Platform Compatibility Check (CRITICAL)
BizSocials evaluates:
Rule	FB	IG
Text-only allowed	✅	❌
Link allowed	✅	⚠️ (caption only)
Media required	❌	✅
Aspect ratio strict	❌	✅
Hashtag limit	❌	30
If mismatch found → user sees Resolution Panel.
 
STEP 4 — Platform Overrides (MANDATORY UX)
User can:
•	Adjust caption per platform
•	Disable a platform
•	Upload alternate media per platform
❗ BizSocials NEVER auto-fix silently.
 
STEP 5 — Preview (Split View)
User sees:
•	Facebook preview (left)
•	Instagram preview (right)
Each preview shows:
•	Exact truncation
•	Media crop
•	Warnings (if any)
 
STEP 6 — Choose Action
Buttons:
•	Save Draft
•	Submit for Approval
•	Schedule
•	Publish Now
 
STEP 7 — Execution
System:
•	Creates one Post
•	Creates multiple PostTargets
•	Executes publishing per target
 
STEP 8 — Result Summary
User sees:
•	FB: ✅ Published
•	IG: ❌ Failed (reason shown)
No ambiguity. No guessing.
 
4️⃣ UI SCREENS & COMPONENTS
 
🖥 Screen: Cross-Platform Composer
Platform Selector
•	Checkboxes: Facebook, Instagram
•	Health indicator per platform
 
🔁 Platform Overrides Panel
Per platform:
•	Caption override (optional)
•	Media override (optional)
•	Disable platform toggle
 
👁 Split Preview
Two side-by-side previews:
•	Facebook feed
•	Instagram feed
Warnings shown inline.
 
5️⃣ API SPEC (DETAILED)
 
API: Create Multi-Platform Post
POST /api/v1/posts
{
  "workspace_id": "uuid",
  "platforms": ["facebook", "instagram"],
  "content": {
    "base": {
      "text": "Launch day!",
      "media_ids": ["m1"]
    },
    "overrides": {
      "instagram": {
        "caption": "Launch day 🚀 #bizsocials",
        "media_ids": ["m1"]
      }
    }
  }
}
Side effects:
•	Create posts
•	Create post_targets (one per platform)
•	Status = draft
 
API: Publish Post
POST /api/v1/posts/{id}/publish
Side effects:
•	Dispatch one job per platform:
o	PublishFacebookPostJob
o	PublishInstagramPostJob
 
6️⃣ BACKGROUND JOBS
Execution Model (CRITICAL)
Each platform runs independently.
✔ FB can succeed
✔ IG can fail
✔ Post remains partially published
No rollback across platforms.
 
Failure Handling
•	Retriable failures retry
•	Permanent failures stop
•	User notified per platform
 
7️⃣ DATABASE ENTITIES
posts
•	id
•	workspace_id
•	status (draft, scheduled, partially_published, published)
 
post_targets (MOST IMPORTANT)
Field	Purpose
post_id	Parent
platform	facebook / instagram
account_id	Page / IG ID
status	pending / published / failed
external_id	Platform post ID
error	Failure details
 
post_overrides
•	post_id
•	platform
•	overridden_content (JSON)
 
8️⃣ VALIDATION & ERROR STATES
Scenario	Behavior
IG requires media	Block IG only
FB link ok, IG not	Warn + allow override
One platform disconnected	Disable that platform
Approval missing	Block publish
Partial publish	Show summary
 
9️⃣ AUDIT LOG EVENTS
Event	Platform-Scoped
post.created	global
post.target.created	per platform
post.published	per platform
post.failed	per platform
Payload always includes:
•	platform
•	account_id
•	user_id
 
🔟 TEST CASES
 
Unit Tests
•	Compatibility rules
•	Override application
•	Status transitions
 
Integration Tests
•	FB success + IG failure
•	Retry behavior per target
 
E2E (Playwright)
test('cross-platform publish with partial failure', async () => {
  // Select FB + IG
  // Upload media
  // Publish
  // Verify FB success, IG failure shown
})
 
🔒 SECURITY & COMPLIANCE
✔ Explicit platform selection
✔ No silent fallbacks
✔ Independent permissions
✔ Full audit per target
 
✅ DEFINITION OF DONE (DoD)
✔ One post → many platforms
✔ Platform mismatches handled explicitly
✔ Partial success visible
✔ Audit logs per platform
✔ Tests pass
 
🚧 BLOCKERS CHECK
Dependency	Status
Flow 3.2 FB Publishing	REQUIRED
Flow 3.3 IG Feed	REQUIRED
Flow 3.4 IG Stories	OPTIONAL
Approval engine	REQUIRED
Media overrides	REQUIRED


PHASE 3 — Flow 3.6: Post Failure Recovery & Re-Publish
Failure is inevitable. Confusion is not.
 
1️⃣ PURPOSE (LOCK THIS)
Enable users to:
•	Understand exactly why a post failed
•	Recover without re-creating the post
•	Fix only the failed platform
•	Re-publish safely, audibly, and traceably
❌ No “try again later”
❌ No silent retries
❌ No global rollback
 
2️⃣ FAILURE CATEGORIES (EXPLICIT)
Every failure MUST fall into one and only one category.
Category	Recoverable	Examples
AUTH	✅	Token expired
VALIDATION	✅	IG media ratio
PERMISSION	⚠️	Page role removed
RATE_LIMIT	✅	API throttled
PLATFORM	⚠️	IG outage
POLICY	❌	Content violation
INTERNAL	✅	Job crash
This classification drives UX, retry rules, and alerts.
 
3️⃣ ENTRY CONDITIONS
Recovery is available if:
✔ Post exists
✔ At least one post_target.status = failed
✔ User has permission on that platform
✔ Failure is not POLICY-BLOCKED
 
4️⃣ COMPLETE USER JOURNEY
 
STEP 1 — Failure Visibility
Where failures appear:
•	Post list
•	Calendar
•	Notifications
•	Inbox alerts (optional)
Status badges:
•	🟢 Published
•	🟡 Partial failure
•	🔴 Failed
 
STEP 2 — Open Failure Detail Panel
URL
/app/w/:workspaceId/posts/:postId/failures
User sees platform-wise breakdown:
Platform	Status	Reason	Action
Facebook	✅ Published	—	View
Instagram	❌ Failed	Media ratio invalid	Fix & Re-Publish
 
STEP 3 — Failure Explanation (MANDATORY UX)
For each failed platform, show:
•	Human-readable reason
•	Raw API error (expandable)
•	What user can do
•	What BizSocials will do automatically (if any)
Example:
Instagram requires 1:1 or 4:5 images. Your image is 16:9.
 
STEP 4 — Recovery Options (Contextual)
Based on failure category:
VALIDATION
•	Upload new media
•	Edit caption
•	Preview again
AUTH
•	Re-authenticate account
•	Retry after success
RATE LIMIT
•	Retry after cooldown
•	Schedule retry
PLATFORM
•	Auto retry (system)
•	Manual retry later
POLICY (BLOCKED)
•	View violation details
•	Duplicate & edit post (new post)
 
STEP 5 — Re-Publish Execution
User clicks:
“Re-Publish to Instagram Only”
System:
•	Creates a new publish attempt
•	Preserves original audit trail
•	Does NOT affect successful platforms
 
STEP 6 — Confirmation & Feedback
User sees:
•	Attempt started
•	Real-time status updates
•	Success or new failure clearly shown
 
5️⃣ UI SCREENS & COMPONENTS
 
🖥 Screen: Post Failure Detail
Components:
•	Platform status list
•	Failure reason card
•	Recovery CTA
•	Retry history timeline
 
🧩 Component: Recovery Editor
Scoped to one platform only:
•	Media override
•	Caption override
•	Validation preview
 
🧾 Retry History Panel
Shows:
•	Attempt #
•	Time
•	Actor (user/system)
•	Result
 
6️⃣ API SPEC (CRITICAL)
 
API: Get Post Failures
GET /api/v1/posts/{id}/failures
{
  "post_id": "uuid",
  "targets": [
    {
      "platform": "instagram",
      "status": "failed",
      "failure_type": "validation",
      "message": "Image aspect ratio invalid",
      "retryable": true,
      "last_attempt_at": "2026-02-09T10:10:00Z"
    }
  ]
}
 
API: Retry Publish (Platform-Scoped)
POST /api/v1/posts/{id}/retry
{
  "platform": "instagram",
  "overrides": {
    "media_ids": ["new_media_id"]
  }
}
Side effects:
•	Create post_publish_attempt
•	Dispatch platform job
•	Update target status
 
7️⃣ BACKGROUND JOB MODEL
New Entity: post_publish_attempts
Field	Purpose
post_id	Parent
platform	FB / IG
attempt_no	Incremental
initiated_by	user / system
status	pending / success / failed
error	Failure snapshot
Jobs:
•	RetryInstagramPublishJob
•	RetryFacebookPublishJob
 
8️⃣ DATABASE ENTITIES
Extend post_targets
Add:
•	failure_type
•	failure_code
•	last_failed_at
•	retry_count
 
New Table: post_publish_attempts
Immutable history of retries.
 
9️⃣ VALIDATION & ERROR RULES
Rule	Enforcement
Max retries	Configurable (default 5)
Policy failures	Retry disabled
Permission loss	Block + explain
Duplicate retries	Prevent parallel attempts
 
🔔 NOTIFICATIONS
Notify user when:
•	Retry succeeds
•	Retry fails
•	Platform auto-retry exhausted
Channels:
•	In-app
•	Email (optional)
 
🔐 AUDIT LOG EVENTS
Event	Scope
post.failed	platform
post.retry_requested	platform
post.retry_succeeded	platform
post.retry_failed	platform
All events include:
•	platform
•	attempt_no
•	actor
 
🧪 TEST CASES
 
Unit Tests
•	Failure classification
•	Retry eligibility rules
•	Attempt counter increments
 
Integration Tests
•	Partial publish → retry → success
•	Token expiry → reauth → retry
 
E2E (Playwright)
test('recover failed Instagram post without affecting Facebook', async () => {
  // Publish FB+IG
  // Force IG failure
  // Fix media
  // Retry IG only
  // Verify FB unchanged, IG published
})
 
✅ DEFINITION OF DONE (DoD)
✔ Platform-specific recovery
✔ No data loss
✔ Retry history preserved
✔ Clear UX explanations
✔ Audits complete
✔ Tests pass
 
🚧 BLOCKERS CHECK
Dependency	Status
Flow 3.5 (Cross-Platform)	REQUIRED
Platform error mapping	REQUIRED
Media override editor	REQUIRED
Notifications infra	REQUIRED
 


PHASE 3 — Flow 3.7: Approval Bypass Safeguards & Governance
Approvals are only valuable if they cannot be bypassed — accidentally or intentionally.
 
1️⃣ PURPOSE (LOCK THIS)
Ensure that no content can be published in violation of:
•	Workspace approval rules
•	Role-based permissions
•	Platform-specific approval policies
•	Emergency controls
While still allowing:
•	Legitimate exceptions
•	Auditable overrides
•	Time-bound escalations
❌ No silent overrides
❌ No “admin magic”
❌ No implicit trust
 
2️⃣ CORE GOVERNANCE PRINCIPLES
These are hard rules, not guidelines:
1.	Approval enforcement happens server-side
2.	UI controls cannot override backend rules
3.	Every bypass must be explicit
4.	Every bypass must be auditable
5.	Every bypass must be reversible
6.	Bypass ≠ Disable approvals globally
 
3️⃣ APPROVAL MODES (PER WORKSPACE)
Stored in:
workspaces.approval_mode
Mode	Description
AUTO	No approvals required
MANUAL	Approval required for all posts
CONDITIONAL	Rules-based approvals
 
4️⃣ APPROVAL RULE ENGINE (CRITICAL)
Rule Definition
Stored in approval_rules:
Field	Example
workspace_id	uuid
applies_to	facebook, instagram
content_type	feed, story
condition	contains_link, external_url
action	require_approval
priority	integer
Rules are evaluated before publish.
 
5️⃣ BYPASS SCENARIOS (EXPLICIT)
A bypass is allowed ONLY in these cases:
Scenario	Allowed?
Workspace owner override	✅
Emergency publish	✅
Scheduled post escalation	✅
Super Admin tenant-level override	⚠️ Read-only approval
Regular user bypass	❌
 
6️⃣ COMPLETE USER JOURNEY
 
STEP 1 — User Attempts Publish
System evaluates:
•	Workspace approval mode
•	Rule engine
•	User role
If approval required → normal flow
If bypass attempted → block + explain
 
STEP 2 — Bypass Request UI
Triggered only if user eligible
UI requires:
•	Explicit reason (mandatory)
•	Confirmation checkbox
•	Acknowledgement of audit logging
“This action will be permanently recorded.”
 
STEP 3 — Server Validation (NON-NEGOTIABLE)
Backend checks:
•	Role eligibility
•	Rule scope
•	Platform eligibility
•	Rate limits on bypass usage
 
STEP 4 — Execution
If approved:
•	Publish allowed
•	Post marked approval_bypassed = true
•	Bypass metadata attached
If rejected:
•	Publish blocked
•	Reason returned
 
7️⃣ UI SCREENS & COMPONENTS
 
🖥 Modal: Approval Required
Shows:
•	Why approval is required
•	Applicable rule(s)
•	Options:
o	Submit for approval
o	Request bypass (if eligible)
 
🖥 Modal: Bypass Confirmation
Fields:
•	Reason (required, ≥ 10 chars)
•	Checkbox: “I understand this will be audited”
CTA:
•	“Bypass & Publish”
 
🖥 Admin View: Bypass Log
URL:
/app/w/:id/settings/approvals/bypasses
Shows:
•	Who bypassed
•	When
•	Why
•	What was published
•	Platform impact
 
8️⃣ API SPEC (DETAILED)
 
API: Evaluate Approval Requirement
POST /api/v1/approvals/evaluate
{
  "workspace_id": "uuid",
  "post_id": "uuid",
  "platform": "instagram"
}
Response:
{
  "approval_required": true,
  "rules_triggered": ["external_link_rule"],
  "bypass_allowed": true
}
 
API: Request Approval Bypass
POST /api/v1/approvals/bypass
{
  "post_id": "uuid",
  "platform": "facebook",
  "reason": "Time-sensitive campaign launch"
}
Side effects:
•	Validate eligibility
•	Record bypass
•	Allow publish
 
9️⃣ DATABASE ENTITIES
 
Extend posts
Add:
•	approval_required (bool)
•	approval_bypassed (bool)
 
New Table: approval_bypasses
Field	Purpose
post_id	Reference
platform	FB / IG
bypassed_by	user_id
reason	Text
created_at	Timestamp
 
New Table: approval_rules
Rules are immutable once active.
 
🔟 VALIDATION & SAFETY RULES
Rule	Enforcement
Reason required	Server-side
Max bypass per user/day	Configurable
Conditional rules	Higher priority
Approval re-enable	Automatic next post
 
🔐 AUDIT LOG EVENTS (MANDATORY)
Event	Description
approval.required	Rule triggered
approval.bypass_requested	User intent
approval.bypassed	Override applied
approval.denied	Attempt blocked
Each event includes:
•	post_id
•	platform
•	user_id
•	rule_ids
 
🔔 NOTIFICATIONS
Notify:
•	Workspace admins
•	Approvers
•	Super Admin (if repeated bypass)
 
🧪 TEST CASES
 
Unit Tests
•	Rule evaluation order
•	Bypass eligibility logic
•	Rate-limit enforcement
 
Integration Tests
•	Conditional approval → bypass → publish
•	Unauthorized bypass attempt blocked
 
E2E (Playwright)
test('owner bypasses approval with audit trail', async () => {
  // Create rule requiring approval
  // Attempt publish
  // Request bypass
  // Verify audit entry exists
})
 
✅ DEFINITION OF DONE (DoD)
✔ Approvals enforced server-side
✔ Bypass explicit and limited
✔ Full audit trail
✔ Admin visibility
✔ Abuse prevented
 
🚧 BLOCKERS CHECK
Dependency	Status
Approval engine	REQUIRED
Role system	REQUIRED
Audit logs	REQUIRED
Notification infra	REQUIRED



PHASE 4 — WhatsApp (WATI-Grade Foundations)
WhatsApp is not a channel.
It is a regulated communications system with irreversible penalties.
This phase builds the spine. Campaigns and automations come later.
 
🔒 PHASE 4 GOVERNING PRINCIPLES (LOCK THESE)
1.	Meta owns WhatsApp rules — BizSocials enforces them
2.	No message can be sent without compliance validation
3.	Templates are contracts, not content
4.	Quality score visibility BEFORE damage
5.	One mistake must not destroy a tenant
 
📦 PHASE 4 FLOW MAP
Flow ID	Name
4.1	WhatsApp Business Onboarding Wizard
4.2	WhatsApp Account & Phone Management
4.3	WhatsApp Inbox (Service Messaging)
4.4	Template Management & Meta Sync
4.5	Message Sending Engine (Compliance-Aware)
4.6	Quality Rating, Limits & Safeguards
4.7	Audit, Governance & Incident Handling
 
FLOW 4.1 — WhatsApp Business Onboarding Wizard
🎯 Purpose
Safely connect a tenant to Meta WhatsApp Business Platform (WABA) without exposing raw Meta complexity.
 
USER JOURNEY
1.	Tenant Admin chooses “Enable WhatsApp”
2.	Guided wizard walks through Meta steps
3.	BizSocials validates every step
4.	WhatsApp only activates after full health check
 
UI SCREENS
Screen 1 — WhatsApp Introduction
URL:
/app/w/:id/whatsapp/setup
Shows:
•	What WhatsApp can/cannot do
•	Compliance responsibilities
•	Risks (quality score, opt-out rules)
CTA: “Start WhatsApp Setup”
 
Screen 2 — Meta Business Connection
Explains:
•	BizSocials uses its own Meta app
•	Tenant authorizes access
CTA:
•	“Connect Meta Business”
❌ No app IDs shown
❌ No token dumps
 
Screen 3 — WABA Selection / Creation
Options:
•	Select existing WABA
•	Create new WABA (guided)
Validation:
•	Ownership verified
•	Business verified (or pending)
 
Screen 4 — Phone Number Setup
Fields:
•	Country
•	Phone number
•	Display name
System:
•	Sends OTP
•	Verifies number
•	Locks number to tenant
 
Screen 5 — Business Profile Setup
Fields:
•	Business description
•	Website
•	Address
•	Category
 
Screen 6 — Compliance & Limits Preview
Shows:
•	Messaging tier
•	Daily send limits
•	Quality rating (initial)
•	Opt-out policy
User must explicitly accept.
 
Screen 7 — Health Check & Activation
System verifies:
•	Token validity
•	Webhook connectivity
•	Template sync
•	Rate limits
CTA:
•	“Activate WhatsApp”
 
APIs
POST /api/v1/whatsapp/setup/start
POST /api/v1/whatsapp/setup/meta-connect
POST /api/v1/whatsapp/setup/waba
POST /api/v1/whatsapp/setup/phone
POST /api/v1/whatsapp/setup/profile
POST /api/v1/whatsapp/setup/activate
Each API:
•	Transactional
•	Audited
•	Rollback safe
 
DATABASE
whatsapp_accounts
•	tenant_id
•	workspace_id
•	waba_id
•	phone_number
•	status
•	quality_rating
•	messaging_limit
•	metadata
 
AUDIT EVENTS
•	whatsapp.setup_started
•	whatsapp.meta_connected
•	whatsapp.phone_verified
•	whatsapp.activated
 
FLOW 4.2 — WhatsApp Account & Phone Management
Purpose: Control, don’t expose
Features:
•	View connected phone
•	Pause messaging
•	Re-verify number
•	Disconnect WhatsApp (guarded)
Disconnection requires:
•	Confirmation
•	Warning
•	Super Admin visibility
 
FLOW 4.3 — WhatsApp Inbox (Service Messaging)
Inbox ≠ Campaigns
Inbox = 1-to-1 service within 24h window
 
Inbox Rules (HARD)
•	Only service messages allowed
•	24-hour session enforced
•	No template usage inside window
 
Inbox UI
URL:
/app/w/:id/whatsapp/inbox
Features:
•	Conversation list
•	Assignment
•	SLA timer
•	Tags
•	Internal notes
•	Opt-out handling
 
API
•	GET /whatsapp/conversations
•	POST /whatsapp/messages/send
Backend validates:
•	Session window
•	Opt-out status
•	Rate limits
 
FLOW 4.4 — Template Management & Meta Sync
Templates are legal artifacts
 
Template Lifecycle
1.	Draft (BizSocials)
2.	Submitted (Meta)
3.	Approved / Rejected
4.	Versioned
5.	Locked
 
UI
URL:
/app/w/:id/whatsapp/templates
Fields:
•	Name
•	Category (Utility / Marketing / Auth)
•	Language
•	Body variables
•	Header / Footer
•	Buttons
 
APIs
•	POST /whatsapp/templates
•	GET /whatsapp/templates/status
•	SYNC via webhook
 
Rules
❌ Unapproved templates cannot send
❌ Editing approved template creates new version
 
FLOW 4.5 — Message Sending Engine (Compliance-Aware)
Before sending ANY message:
System validates:
•	Approved template
•	Opt-in status
•	Rate limit
•	Quality tier
•	Time window
•	Approval rules (Phase 3)
If any fail → BLOCK + EXPLAIN
 
API
POST /api/v1/whatsapp/messages/send
 
FLOW 4.6 — Quality Rating, Limits & Safeguards
Dashboard
Shows:
•	Current quality score
•	Messaging tier
•	Warning thresholds
 
Safeguards (AUTOMATIC)
Condition	Action
Quality drops	Throttle
Repeated opt-outs	Pause marketing
Meta warning	Lock sends
Tier downgrade	Notify tenant
Super Admin always notified.
 
FLOW 4.7 — Audit, Governance & Incident Handling
Everything logged:
•	Message sent
•	Template used
•	Opt-out
•	Throttling
•	Suspension
 
Incident Mode
When triggered:
•	Campaigns disabled
•	Inbox allowed
•	Admin + Super Admin notified
•	Recovery checklist shown
 
TESTING (MANDATORY)
Unit
•	Session window enforcement
•	Template validation
•	Rate limit logic
Integration
•	Template approval lifecycle
•	Opt-out propagation
E2E
•	Full onboarding
•	Message blocked on violation
•	Quality drop simulation
 
✅ DEFINITION OF DONE (PHASE 4)
✔ WhatsApp onboarding wizard complete
✔ No raw Meta complexity exposed
✔ Inbox compliant
✔ Templates enforced
✔ Safeguards active
✔ Full audit trail
 
🚨 COMMON FAILURE TO AVOID
❌ “Just send messages”
❌ “We’ll fix compliance later”
❌ “Admins can override WhatsApp rules”
That kills SaaS businesses.



PHASE 4.8 — WhatsApp Campaigns & Broadcasts
Campaigns are opt-in, template-driven, rate-governed outbound communication.
If inbox is conversation, campaigns are contracts at scale.
 
🔒 GOVERNING RULES (LOCK THESE OR STOP)
1.	Campaigns ONLY use approved templates
2.	Opt-in is mandatory, provable, and revocable
3.	No campaign can bypass limits, quality score, or approvals
4.	Campaign failure must never cascade
5.	Every recipient must have a reason for receiving the message
 
📦 FLOW MAP — PHASE 4.8
Flow ID	Name
4.8.1	Campaign Creation Wizard
4.8.2	Audience Builder & Opt-In Governance
4.8.3	Template Binding & Variable Mapping
4.8.4	Scheduling, Throttling & Delivery Engine
4.8.5	Campaign Approval & Safeguards
4.8.6	Delivery Tracking & Analytics
4.8.7	Failure Handling, Pausing & Recovery
 
FLOW 4.8.1 — Campaign Creation Wizard
🎯 Purpose
Create WhatsApp campaigns without ever violating Meta policy.
 
USER JOURNEY
1.	User clicks “Create WhatsApp Campaign”
2.	Guided wizard enforces:
o	Audience → Template → Limits → Schedule
3.	Campaign enters approval or queue
 
UI SCREENS
Screen 1 — Campaign Basics
URL:
/app/w/:id/whatsapp/campaigns/create
Fields:
•	Campaign name (required)
•	Category (Utility / Marketing / Authentication)
•	Purpose (internal note)
Validation:
•	Name unique per workspace
•	Category locked once saved
 
Screen 2 — Audience Selection
(See Flow 4.8.2)
 
Screen 3 — Template Selection
(See Flow 4.8.3)
 
Screen 4 — Schedule & Limits
Fields:
•	Send now / Schedule
•	Start time
•	Throttle rate (messages/min)
•	Daily cap preview
System shows:
•	Tenant messaging tier
•	Maximum safe volume
❌ User cannot exceed system cap
 
Screen 5 — Review & Submit
Summary:
•	Audience size
•	Template
•	Estimated duration
•	Risk indicators
CTA:
•	“Submit for Approval” or “Schedule”
 
APIs
•	POST /api/v1/whatsapp/campaigns
•	POST /api/v1/whatsapp/campaigns/preview
 
DB
whatsapp_campaigns
•	tenant_id
•	workspace_id
•	name
•	category
•	status
•	scheduled_at
•	throttle_rate
•	created_by
 
FLOW 4.8.2 — Audience Builder & Opt-In Governance
Audience ≠ phone list
Audience = permissioned recipients
 
Audience Sources
✔ CRM contacts
✔ Uploaded CSV (validated)
✔ Tags
✔ Past conversations
✔ Campaign exclusions
 
UI
URL:
/app/w/:id/whatsapp/audience
Filters:
•	Opt-in status (required)
•	Last interaction
•	Tags
•	Country
•	Language
System enforces:
•	Explicit WhatsApp opt-in
•	Channel-specific consent
•	Opt-out exclusions
 
Validation (HARD)
❌ No opt-in → excluded
❌ DND flagged → excluded
❌ Country mismatch → warning
 
DB
whatsapp_contacts
•	phone
•	opt_in_status
•	opt_in_source
•	opt_in_at
•	opt_out_at
•	tags
 
Audit
•	whatsapp.optin_used
•	whatsapp.optout_respected
 
FLOW 4.8.3 — Template Binding & Variable Mapping
A campaign is invalid without 100% variable resolution
 
UI
•	Select approved template
•	Preview language variants
•	Map variables ({{1}}, {{2}}, etc.)
•	Sample validation
System blocks:
•	Missing variables
•	Invalid formats
•	Over-length values
 
API
•	POST /whatsapp/templates/validate
 
DB
whatsapp_campaign_templates
•	campaign_id
•	template_id
•	variable_map
•	language
 
FLOW 4.8.4 — Scheduling, Throttling & Delivery Engine
This is a queueing system, not a loop.
 
Delivery Engine Rules
•	Job-based dispatch
•	Adaptive throttling
•	Pause on:
o	Meta error
o	Quality dip
o	Opt-out spike
 
States
State	Meaning
draft	Incomplete
pending_approval	Awaiting approval
scheduled	Queued
running	Sending
paused	Auto/manual
completed	Finished
failed	Terminal
 
API
•	POST /whatsapp/campaigns/{id}/schedule
•	POST /whatsapp/campaigns/{id}/pause
•	POST /whatsapp/campaigns/{id}/resume
 
FLOW 4.8.5 — Campaign Approval & Safeguards
Integrates with Phase 3 approvals.
Approval triggers if:
•	Marketing category
•	Large audience
•	First campaign
•	New template
 
UI
•	Approval queue
•	Diff view (template, audience)
•	Approve / Reject with reason
 
Audit
•	campaign.submitted
•	campaign.approved
•	campaign.rejected
 
FLOW 4.8.6 — Delivery Tracking & Analytics
Dashboard
Metrics:
•	Sent
•	Delivered
•	Read
•	Failed
•	Opt-outs
•	Quality impact
Visuals:
•	Timeline
•	Failure reasons
•	Country split
 
API
•	GET /whatsapp/campaigns/{id}/stats
 
DB
whatsapp_campaign_stats
•	campaign_id
•	sent
•	delivered
•	read
•	failed
•	opt_outs
 
FLOW 4.8.7 — Failure Handling, Pausing & Recovery
Failure is expected, chaos is not.
 
Failure Types
Failure	Action
Template rejected mid-send	Pause
Rate limit	Auto throttle
Quality warning	Pause marketing
API outage	Retry with backoff
 
Recovery UI
Shows:
•	What failed
•	Why
•	What user can do
•	Safe resume button
 
Audit
•	campaign.paused
•	campaign.resumed
•	campaign.failed
 
🧪 TESTING (MANDATORY)
Unit
•	Opt-in enforcement
•	Variable mapping validation
•	Throttle logic
Integration
•	Meta send simulation
•	Pause on quality drop
E2E
•	Create → approve → send → pause → resume
•	Attempt violation → blocked with reason
 
✅ DEFINITION OF DONE — PHASE 4.8
✔ Campaign wizard complete
✔ Opt-in enforced
✔ Templates locked
✔ Throttling adaptive
✔ Safeguards active
✔ Full audit trail
✔ Failure recovery UX
 
⚠️ NON-NEGOTIABLE WARNINGS
❌ No CSV blast without opt-in
❌ No “force send”
❌ No bypass for admins
That’s how WhatsApp bans happen.


PHASE 4.9 — WhatsApp Automation Rules
Automation must assist humans, not impersonate them.
Every automated action must be explainable, stoppable, and reversible.
 
🔒 HARD GOVERNANCE RULES (LOCK THESE)
1.	No automation may send free-form outbound messages
2.	All outbound automation uses approved templates
3.	Inbound automation is allowed only inside service window
4.	Every automation must be traceable to a rule
5.	Users can always override automation
6.	Automation must never degrade quality score
If any rule breaks these → BLOCK
 
📦 FLOW MAP — PHASE 4.9
Flow ID	Name
4.9.1	Automation Rules Engine (Foundation)
4.9.2	Rule Builder UI
4.9.3	Inbound Auto-Replies
4.9.4	Business Hours & Away Messages
4.9.5	SLA Escalation & Human Handoff
4.9.6	Keyword & Intent Triggers
4.9.7	Automation Monitoring & Overrides
 
FLOW 4.9.1 — Automation Rules Engine (Foundation)
🎯 Purpose
Provide a deterministic, safe, inspectable automation engine.
 
ENGINE PRINCIPLES
•	Event-driven
•	Priority-based
•	One rule → one responsibility
•	Idempotent execution
•	Stop-on-first-match (configurable)
 
EVENTS (INPUTS)
Event	Description
inbound_message	User sent message
conversation_opened	New chat
no_response_timer	SLA breach
outside_business_hours	Time-based
tag_added	CRM trigger
status_changed	Conversation state
 
ACTIONS (OUTPUTS)
✔ Send template message
✔ Assign conversation
✔ Add tag
✔ Change priority
✔ Escalate to human
✔ Pause automation
❌ No raw text sends
❌ No bulk automation sends
 
DB
whatsapp_automation_rules
•	tenant_id
•	workspace_id
•	name
•	priority
•	enabled
•	stop_processing
•	created_by
whatsapp_automation_conditions
•	rule_id
•	event_type
•	operator
•	value
whatsapp_automation_actions
•	rule_id
•	action_type
•	config (JSON)
 
API
•	POST /whatsapp/automation/rules
•	PUT /whatsapp/automation/rules/{id}
•	POST /whatsapp/automation/evaluate (internal)
 
FLOW 4.9.2 — Rule Builder UI
Automation must be built visually, not buried in configs.
 
UI
URL:
/app/w/:id/whatsapp/automation
 
Rule Builder Layout
IF (Trigger)
•	Incoming message
•	Outside business hours
•	No response in X minutes
•	Keyword match
•	Conversation opened
AND (Conditions)
•	Language
•	Tags
•	Country
•	Opt-in status
•	Message contains / equals
THEN (Actions)
•	Send template
•	Assign agent/team
•	Add tag
•	Escalate
•	Pause rule
 
UX SAFEGUARDS
•	Live preview
•	Rule priority warning
•	Conflict detection
•	Dry-run test mode
 
Audit
•	automation.rule_created
•	automation.rule_updated
•	automation.rule_disabled
 
FLOW 4.9.3 — Inbound Auto-Replies
Auto-reply ≠ chatbot
Auto-reply = acknowledgment + guidance
 
Allowed Scenarios
✔ Greeting on first message
✔ “We got your message”
✔ FAQ direction
✔ Compliance notice
 
UI
Select:
•	Trigger: First inbound message
•	Template
•	Language fallback
•	Cooldown window (e.g., once per 24h)
 
Enforcement
•	Only inside service window OR neutral acknowledgment
•	Never loops
•	Never replies to system messages
 
Audit
•	automation.autoreply_sent
 
FLOW 4.9.4 — Business Hours & Away Messages
This is mandatory for quality score protection.
 
UI
URL:
/app/w/:id/whatsapp/business-hours
Fields:
•	Timezone
•	Weekly schedule
•	Holidays
•	Away template
 
Behavior
If inbound outside hours:
•	Send away template (once per session)
•	Tag conversation as “after_hours”
•	SLA timer starts next business hour
 
DB
whatsapp_business_hours
•	workspace_id
•	timezone
•	schedule
•	holidays
•	away_template_id
 
Audit
•	automation.away_message_sent
 
FLOW 4.9.5 — SLA Escalation & Human Handoff
Automation must hand off cleanly, not hide problems.
 
SLA Rules
Triggers:
•	No agent reply in X mins
•	High-priority tag
•	Customer angry keywords
Actions:
•	Escalate to supervisor
•	Reassign agent
•	Notify admin
 
UI
•	SLA dashboard
•	Breach indicators
•	Escalation history
 
Audit
•	automation.sla_triggered
•	automation.escalated
 
FLOW 4.9.6 — Keyword & Intent Triggers
Lightweight intent detection, not AI hallucination.
 
Supported Matching
✔ Exact keyword
✔ Contains
✔ Regex (admin-only)
Examples:
•	“price”
•	“refund”
•	“cancel”
•	“agent”
 
Behavior
Keyword → predefined template + tag
❌ No generative replies
❌ No context chaining
 
Safety
•	Case insensitive
•	Language aware
•	Cooldown per keyword
 
Audit
•	automation.keyword_triggered
 
FLOW 4.9.7 — Automation Monitoring & Overrides
If you can’t stop it instantly, it’s unsafe.
 
UI
Automation Monitor:
•	Rules firing count
•	Messages sent
•	Opt-outs caused
•	Quality impact
Controls:
•	Pause rule
•	Disable automation globally
•	Override per conversation
 
API
•	POST /whatsapp/automation/rules/{id}/pause
•	POST /whatsapp/automation/global-disable
 
Audit
•	automation.paused
•	automation.overridden
•	automation.disabled_globally
 
🧪 TESTING (MANDATORY)
Unit
•	Rule priority resolution
•	Condition evaluation
•	Action execution
Integration
•	Inbound message → rule → template send
•	SLA breach → escalation
E2E
•	User sends message → auto reply → agent takeover
•	Outside hours → away message once
 
✅ DEFINITION OF DONE — PHASE 4.9
✔ Visual rule builder
✔ Safe inbound automation
✔ Business hours enforced
✔ SLA escalation works
✔ Overrides instant
✔ Full audit trail
✔ No policy violations
 
🚫 WHAT WE WILL NEVER BUILD (BY DESIGN)
❌ Chatbot pretending to be human
❌ Unapproved template sending
❌ Silent automation
❌ Hidden rules
❌ Infinite reply loops


PHASE 5 — CONTENT ENGINE (PUBLISHING CORE)
Content is not a post.
Content is an asset lifecycle: idea → creation → approval → distribution → recovery → reuse → insight.
 
🔒 NON-NEGOTIABLE PRINCIPLES (LOCK THESE)
1.	One content object → many platform outputs
2.	Platform rules enforced before publish
3.	Draft ≠ scheduled ≠ published (explicit states)
4.	Preview is always platform-accurate
5.	Failures are recoverable
6.	Everything versioned & auditable
 
📦 PHASE 5 FLOW MAP
Flow ID	Name
5.1	Content Object Model (Foundation)
5.2	Post Composer (Editor + Preview)
5.3	Media Library
5.4	Scheduling & Calendar
5.5	Approval Workflow Integration
5.6	Bulk Upload & CSV
5.7	Evergreen & Recycling
5.8	Publishing Execution Engine
5.9	Failure Handling & Re-Publish
5.10	Versioning, Revisions & Restore
 
FLOW 5.1 — Content Object Model (FOUNDATION)
🎯 Purpose
Decouple what you create from where you publish.
 
Core Concept
Content
 ├─ Text
 ├─ Media
 ├─ Metadata
 └─ PlatformVariants[]
 
DB ENTITIES
contents
•	id
•	tenant_id
•	workspace_id
•	title (internal)
•	status (draft, pending_approval, scheduled, published, failed)
•	created_by
•	updated_by
•	created_at
content_platform_variants
•	content_id
•	platform (facebook, instagram, linkedin)
•	post_type (feed, story, reel)
•	caption
•	media_overrides
•	character_count
•	validation_status
 
Audit
•	content.created
•	content.updated
•	content.deleted
 
FLOW 5.2 — Post Composer (Editor + Preview)
This is where users spend 70% of their time.
 
UI
URL:
/app/w/:id/content/create
 
LEFT PANEL — Editor
•	Post text
•	Emoji picker
•	Hashtag helper
•	AI assist (caption ideas only)
•	Link shortener + UTM builder
•	First comment (IG)
 
RIGHT PANEL — Platform Preview
Tabs:
•	Facebook Feed
•	Instagram Feed
•	Instagram Story
•	LinkedIn Feed
Each preview:
•	Exact character limits
•	Media crop enforcement
•	Link behavior simulation
 
Validation (REAL-TIME)
Rule	Enforced
Character limit	Hard stop
Media ratio	Warning → block
Platform disallowed content	Block
Missing media	Block
 
API
•	POST /content
•	PUT /content/{id}
•	POST /content/{id}/validate
 
Audit
•	content.edited
•	content.validated
 
FLOW 5.3 — Media Library
Media is a shared asset, not an attachment.
 
UI
URL:
/app/w/:id/media
Features:
•	Folder structure
•	Tags
•	Search
•	Bulk upload
•	Usage indicator (“used in 3 posts”)
 
Media Actions
✔ Crop / resize
✔ Alt text
✔ Replace version
✔ Soft delete
 
DB
media_assets
•	id
•	tenant_id
•	workspace_id
•	file_path
•	type
•	dimensions
•	size
•	metadata
 
Audit
•	media.uploaded
•	media.updated
•	media.deleted
 
FLOW 5.4 — Scheduling & Calendar
Scheduling is risk management, not just timing.
 
UI
URL:
/app/w/:id/calendar
Views:
•	Month
•	Week
•	Agenda
 
Behavior
•	Drag & drop
•	Conflict detection
•	Platform rate-limit awareness
•	Timezone aware
 
API
•	POST /content/{id}/schedule
•	PUT /content/{id}/reschedule
 
Audit
•	content.scheduled
•	content.rescheduled
 
FLOW 5.5 — Approval Workflow Integration
Publishing without governance = brand damage.
 
Integration Points
•	On schedule attempt
•	On publish attempt
•	On content edit after approval
 
Behavior
•	Approval required → lock content
•	Changes invalidate approval
•	Approval status visible inline
 
Audit
•	content.submitted_for_approval
•	content.approved
•	content.rejected
 
FLOW 5.6 — Bulk Upload & CSV
Power users demand scale.
 
Supported Inputs
•	CSV (caption, date, platform)
•	Media ZIP
•	Template validation
 
Safety
•	Preview before commit
•	Row-level error reporting
•	Partial success allowed
 
API
•	POST /content/bulk/import
•	GET /content/bulk/status/{id}
 
Audit
•	content.bulk_imported
 
FLOW 5.7 — Evergreen & Recycling
Good content should outlive its publish date.
 
UI
•	Evergreen rules
•	Frequency limits
•	Blackout dates
•	Auto-stop on failure
 
Safety
•	No repost spam
•	Performance decay detection
 
Audit
•	evergreen.rule_created
•	evergreen.post_recycled
 
FLOW 5.8 — Publishing Execution Engine
This is mission-critical infrastructure.
 
Execution Steps
1.	Validate token
2.	Validate platform rules
3.	Lock content
4.	Publish
5.	Capture platform post ID
6.	Update status
 
Guarantees
✔ Idempotent
✔ Retry safe
✔ No double post
 
Audit
•	content.published
•	content.publish_failed
 
FLOW 5.9 — Failure Handling & Re-Publish
Failure is inevitable. Chaos is optional.
 
Failure Types
•	Token expired
•	Media rejected
•	Platform outage
•	Rate limit hit
 
UI
•	Clear error
•	Retry option
•	Fix & re-publish
 
Audit
•	content.retry_attempted
•	content.republished
 
FLOW 5.10 — Versioning, Revisions & Restore
Content must be recoverable.
 
Versioning
•	Every save = version
•	Diff view
•	Restore any version
 
DB
content_versions
•	content_id
•	version_number
•	snapshot
•	created_at
 
Audit
•	content.version_created
•	content.version_restored
 
🧪 TESTING (MANDATORY)
Unit
•	Validation rules
•	Platform adapters
•	Scheduling logic
Integration
•	Draft → approval → publish
•	Media reuse across posts
E2E
•	Create → schedule → approve → publish
•	Fail → fix → re-publish
 
✅ DEFINITION OF DONE — PHASE 5
✔ Single content → multi-platform
✔ Accurate previews
✔ Safe scheduling
✔ Approvals enforced
✔ Failures recoverable
✔ Version history intact
✔ Audit complete
 
🚫 WHAT WE WILL NOT BUILD
❌ Platform-specific hacks
❌ “Just publish anyway” buttons
❌ Silent failures
❌ Untracked edits



PHASE 6 — UNIFIED INBOX (CROSS-CHANNEL ENGAGEMENT)
Publishing attracts attention.
Engagement builds trust, revenue, and retention.
 
🔒 NON-NEGOTIABLE PRINCIPLES (LOCK THESE)
1.	One conversation timeline per customer per platform
2.	Humans always know who is speaking
3.	No cross-tenant data leakage — ever
4.	Automation assists, humans decide
5.	Every reply is traceable, editable, auditable
6.	Channel rules enforced before send
 
📦 PHASE 6 FLOW MAP
Flow ID	Name
6.1	Unified Conversation Model (Foundation)
6.2	Inbox UI & Conversation Thread
6.3	Replying & Sending Messages
6.4	Assignment, Ownership & SLAs
6.5	Tags, Notes & CRM Context
6.6	Automation Integration
6.7	Escalation, Collision & Concurrency
6.8	Audit, Compliance & Retention
 
FLOW 6.1 — Unified Conversation Model (FOUNDATION)
🎯 Purpose
Normalize messages, comments, DMs, and chats into a single operational model.
 
Core Abstraction
Conversation
 ├─ Participant(s)
 ├─ Messages[]
 ├─ Channel (FB / IG / WhatsApp / X)
 ├─ Status (open, pending, closed)
 ├─ Owner
 └─ SLA timers
 
DB ENTITIES
conversations
•	id
•	tenant_id
•	workspace_id
•	channel
•	external_thread_id
•	status
•	priority
•	assigned_to
•	created_at
conversation_participants
•	conversation_id
•	platform_user_id
•	display_name
•	avatar
•	is_customer
conversation_messages
•	conversation_id
•	direction (inbound/outbound/system)
•	message_type (text, media, template)
•	body
•	metadata
•	sent_by
•	created_at
 
Audit
•	conversation.created
•	message.received
•	message.sent
 
FLOW 6.2 — Inbox UI & Conversation Thread
This is mission-critical UX.
 
UI
URL:
/app/w/:id/inbox
 
Layout
| Conversation List | Thread | Context Panel |
 
Conversation List
•	Channel icon
•	Customer name
•	Last message preview
•	SLA timer
•	Unread badge
•	Assigned agent
Filters:
•	Channel
•	Status
•	Assignee
•	SLA breached
 
Thread View
•	Chronological timeline
•	System messages inline
•	Media preview
•	Platform badges
 
Context Panel
•	Customer profile
•	Tags
•	Notes
•	Past interactions
•	Opt-in status
 
Audit
•	inbox.viewed
 
FLOW 6.3 — Replying & Sending Messages
Every reply is a brand action.
 
Allowed Actions
Channel	Allowed
Facebook	Reply comment, DM
Instagram	Reply comment, DM
WhatsApp	Template / session message
X	Reply, DM
 
Validation
•	WhatsApp template enforcement
•	Service window enforcement
•	Platform character limits
 
UI Safeguards
•	Preview before send
•	Platform warning banners
•	Disable send if invalid
 
API
•	POST /inbox/{conversation}/reply
•	POST /inbox/{conversation}/template-send
 
Audit
•	message.sent
•	message.failed
 
FLOW 6.4 — Assignment, Ownership & SLAs
Unassigned conversations are operational debt.
 
Ownership Rules
•	One owner at a time
•	Auto-assignment supported
•	Manual override allowed
 
SLA Timers
•	First response
•	Resolution
•	Business hours aware
 
UI
•	Assign dropdown
•	SLA breach indicators
•	Escalation banners
 
API
•	POST /inbox/{id}/assign
•	POST /inbox/{id}/escalate
 
Audit
•	conversation.assigned
•	sla.breached
 
FLOW 6.5 — Tags, Notes & CRM Context
Context prevents mistakes.
 
Features
•	Add/remove tags
•	Internal notes
•	Conversation history
 
Rules
•	Notes never sent externally
•	Tags visible across team
 
API
•	POST /inbox/{id}/tags
•	POST /inbox/{id}/notes
 
Audit
•	tag.added
•	note.added
 
FLOW 6.6 — Automation Integration
Automation should trigger, not dominate.
 
Supported Triggers
•	Keyword match
•	SLA breach
•	After hours
•	Tag added
 
Safety
•	Cooldowns
•	One automation per event
•	Manual override always possible
 
Audit
•	automation.triggered
 
FLOW 6.7 — Escalation, Collision & Concurrency
Two people replying = disaster.
 
Collision Control
•	Soft lock on open thread
•	“User is typing” indicator
•	Takeover confirmation
 
Escalation
•	Supervisor takeover
•	Priority bump
•	Audit preserved
 
Audit
•	conversation.locked
•	conversation.taken_over
 
FLOW 6.8 — Audit, Compliance & Retention
Inbox data is regulated data.
 
Retention Rules
•	Configurable per tenant
•	Auto-redaction
•	GDPR export/delete
 
Compliance
•	Opt-out enforcement
•	Consent visibility
•	Platform policy tracking
 
Audit
•	conversation.exported
•	conversation.deleted
 
🧪 TESTING (MANDATORY)
Unit
•	Message normalization
•	SLA timers
•	Permission enforcement
Integration
•	Inbound → reply → close
•	Assignment → escalation
E2E
•	Multi-agent inbox usage
•	WhatsApp template enforcement
 
✅ DEFINITION OF DONE — PHASE 6
✔ Unified inbox across channels
✔ Safe reply enforcement
✔ Assignment & SLA working
✔ Automation integrated
✔ Concurrency protected
✔ Full audit trail
 
🚫 WHAT WE WILL NOT BUILD
❌ Anonymous replies
❌ Platform-breaking shortcuts
❌ Hidden automation
❌ Untraceable messages





PHASE 7 — COLLABORATION, TASKS & GOVERNANCE
If Phase 6 is “handle conversations”,
Phase 7 is “run a team safely”.
This phase ensures:
•	Nothing ships without accountability
•	Nothing changes without traceability
•	No individual can silently bypass controls
•	Collaboration scales without chaos
 
🔒 GOVERNING PRINCIPLES (LOCK THESE)
1.	Every action has an owner
2.	Approvals are explicit, never implied
3.	Bypasses are logged, visible, and reversible
4.	Tasks are tied to real work (posts, inbox, campaigns)
5.	History is immutable
6.	Governance ≠ friction — it’s guidance
 
📦 PHASE 7 FLOW MAP
Flow ID	Name
7.1	Approval Framework (Foundation)
7.2	Approval Workflows (Multi-step)
7.3	Tasks & Kanban Board
7.4	Post Revisions & Versioning
7.5	Bypass, Override & Safeguards
7.6	Audit Trail & Activity Timeline
7.7	Governance Policies & Enforcement
 
FLOW 7.1 — Approval Framework (FOUNDATION)
🎯 Purpose
Define who can approve what, at tenant + workspace + content-type levels.
 
Core Concepts
ApprovalPolicy
 ├─ Scope (Tenant / Workspace)
 ├─ Content Type (Post, Campaign, Template)
 ├─ Required Approvers (role-based)
 ├─ Conditions (platform, risk, automation)
 
DB ENTITIES
approval_policies
•	id
•	tenant_id
•	workspace_id (nullable)
•	content_type
•	enabled
•	created_by
approval_policy_rules
•	policy_id
•	condition_type (platform, channel, risk)
•	condition_value
 
UI
Settings → Approvals
•	Toggle approvals per content type
•	Preview approval path
 
Audit
•	approval_policy.created
•	approval_policy.updated
 
FLOW 7.2 — Approval Workflows (MULTI-STEP)
“Approved” is not binary. It’s a journey.
 
Approval States
draft → pending_approval → approved → published
                      ↘ rejected → revision_required
 
Workflow Capabilities
•	Single or multi-step approvals
•	Parallel approvals (any / all)
•	Conditional approvals (platform-based)
•	SLA-based escalation
 
UI
•	Approval queue
•	Inline approve/reject
•	Mandatory rejection comments
 
API
•	POST /approvals/{id}/approve
•	POST /approvals/{id}/reject
 
Audit
•	approval.requested
•	approval.approved
•	approval.rejected
 
FLOW 7.3 — Tasks & Kanban Board
Work without visibility is invisible failure.
 
Task Model
tasks
•	id
•	tenant_id
•	workspace_id
•	title
•	linked_entity_type (post, conversation, campaign)
•	linked_entity_id
•	status (todo, in_progress, blocked, done)
•	assigned_to
•	due_at
 
UI
URL:
/app/w/:id/tasks
•	Kanban board
•	Drag & drop
•	Filters by assignee, due date, entity
 
Rules
•	Tasks must have owner
•	Completion requires checklist (optional)
 
Audit
•	task.created
•	task.completed
 
FLOW 7.4 — Post Revisions & Versioning
If it changed, it must be recoverable.
 
Versioning Rules
•	Immutable versions
•	Diff view (before/after)
•	Restore allowed (permission-gated)
 
DB
content_versions
•	entity_type
•	entity_id
•	version
•	snapshot_json
•	created_by
 
UI
•	Version history panel
•	Restore button
•	Compare view
 
Audit
•	version.created
•	version.restored
 
FLOW 7.5 — Bypass, Override & Safeguards
Bypasses happen. Silent bypasses are forbidden.
 
Allowed Bypass Scenarios
•	Emergency publish
•	SLA breach
•	Executive override
 
Mandatory Requirements
•	Reason (required)
•	Expiry window
•	Visibility to admins
 
API
•	POST /approvals/{id}/bypass
 
UI
•	Warning banner
•	Countdown expiry
•	Audit preview
 
Audit
•	approval.bypassed
 
FLOW 7.6 — Audit Trail & Activity Timeline
This is your black box recorder.
 
Scope
•	Posts
•	Inbox
•	Tasks
•	Approvals
•	Settings changes
•	Billing-impacting actions
 
UI
URL:
/app/w/:id/audit
•	Timeline view
•	Filter by user, action, date
•	Export CSV/PDF
 
API
•	GET /audit/logs
 
Audit (meta)
•	audit.exported
 
FLOW 7.7 — Governance Policies & Enforcement
Rules should guide before they block.
 
Policy Types
•	Approval required for platform X
•	No publish after business hours
•	Mandatory review for paid campaigns
•	Max posts per day
 
Enforcement Levels
•	Warn
•	Require confirmation
•	Block
 
UI
•	Policy editor
•	Live impact preview
 
Audit
•	policy.violated
•	policy.enforced
 
🧪 TESTING (MANDATORY)
Unit
•	Approval state transitions
•	Version restore logic
•	Policy enforcement
Integration
•	Draft → approval → publish
•	Bypass → audit → expiry
E2E
•	Multi-user approval flow
•	Emergency bypass visibility
•	Task-driven publishing
 
✅ DEFINITION OF DONE — PHASE 7
✔ Approval workflows enforced
✔ No silent bypass possible
✔ Tasks linked to real work
✔ Version history immutable
✔ Governance policies active
✔ Full audit visibility
 
🚫 WHAT WE WILL NOT BUILD
❌ Auto-approvals without visibility
❌ Editable audit logs
❌ Global overrides without scope
❌ “Owner-only” hidden powers


PHASE 8 — ANALYTICS & REPORTING (REVENUE & ROI LAYER)
If you can’t prove impact, you’ll be replaced.
Phase 8 makes BizSocials indispensable.
 
🔒 CORE ANALYTICS PRINCIPLES (LOCK THESE)
1.	Metrics must map to business outcomes
2.	Data lineage must be explainable
3.	No “black box” AI metrics
4.	Tenant data isolation is absolute
5.	Every chart must answer “So what?”
6.	Reports must be exportable & schedulable
7.	Analytics must respect platform compliance
 
📦 PHASE 8 FLOW MAP
Flow ID	Name
8.1	Analytics Data Model & Ingestion (Foundation)
8.2	Analytics Dashboard (Executive Overview)
8.3	Content Performance Analytics
8.4	Audience & Engagement Insights
8.5	Channel & Platform Comparison
8.6	Campaign & Revenue Attribution
8.7	Custom Reports & Exports
8.8	Scheduled Reports & Alerts
8.9	Data Governance, Accuracy & Trust
 
FLOW 8.1 — Analytics Data Model & Ingestion (FOUNDATION)
🎯 Purpose
Create a trusted analytics backbone that scales without breaking correctness.
 
Data Strategy
•	Write-once, read-many
•	Immutable raw snapshots
•	Derived aggregates only from raw data
•	Time-bucketed metrics (hour/day/week)
 
DB ENTITIES
analytics_events (raw, immutable)
•	tenant_id
•	workspace_id
•	platform
•	entity_type (post, message, campaign)
•	entity_id
•	metric_key
•	metric_value
•	occurred_at
analytics_aggregates
•	tenant_id
•	workspace_id
•	metric_key
•	time_bucket (day/week/month)
•	value
 
Ingestion Jobs
•	Fetch metrics per platform
•	Validate API limits
•	Retry with backoff
•	Partial failure tolerant
 
Audit
•	analytics.fetch_started
•	analytics.fetch_failed
•	analytics.fetch_completed
 
FLOW 8.2 — Analytics Dashboard (EXECUTIVE OVERVIEW)
One screen. One minute. One decision.
 
UI
URL:
/app/w/:id/analytics
 
KPI Cards
•	Reach
•	Engagement rate
•	Clicks
•	Conversions
•	Response time (inbox)
•	Spend vs ROI (if ads enabled)
 
Charts
•	Trend over time
•	Top platforms
•	Best-performing content
 
Controls
•	Date range
•	Platform filter
•	Workspace switch
 
Audit
•	analytics.dashboard_viewed
 
FLOW 8.3 — Content Performance Analytics
Know what works, not just what ran.
 
Metrics
•	Impressions
•	Engagements
•	CTR
•	Saves
•	Shares
•	Completion (videos)
 
UI
URL:
/app/w/:id/analytics/content
 
Features
•	Sort by metric
•	Compare posts
•	Platform-specific breakdown
•	Best time insights (from Phase 5)
 
API
•	GET /analytics/content
 
Audit
•	analytics.content_viewed
 
FLOW 8.4 — Audience & Engagement Insights
Reach is useless without relevance.
 
Metrics
•	Audience growth
•	Demographics (where allowed)
•	Engagement patterns
•	Active hours
 
UI
URL:
/app/w/:id/analytics/audience
 
Compliance
•	Only platform-allowed fields
•	Aggregated only (no PII leakage)
 
Audit
•	analytics.audience_viewed
 
FLOW 8.5 — Channel & Platform Comparison
Spend where it performs.
 
Comparison Dimensions
•	Platform vs platform
•	Organic vs paid
•	Publishing vs engagement
 
UI
•	Side-by-side charts
•	Normalized metrics
 
API
•	GET /analytics/comparison
 
Audit
•	analytics.comparison_viewed
 
FLOW 8.6 — Campaign & Revenue Attribution
This is where BizSocials justifies its subscription.
 
Attribution Model (Configurable)
•	First touch
•	Last touch
•	Linear
•	Time decay
 
Revenue Sources
•	UTM tracking (Phase 5)
•	WhatsApp campaign clicks
•	CRM integrations (future)
 
UI
URL:
/app/w/:id/analytics/revenue
 
Metrics
•	Revenue per campaign
•	Cost per conversion
•	ROI %
 
Audit
•	analytics.revenue_viewed
 
FLOW 8.7 — Custom Reports & Exports
Executives live in PDFs and Excel.
 
Report Builder
•	Select metrics
•	Choose charts
•	Date range
•	Branding (logo, colors)
 
Formats
•	PDF
•	CSV
•	XLSX
 
API
•	POST /reports/generate
•	GET /reports/{id}/download
 
Audit
•	report.generated
•	report.downloaded
 
FLOW 8.8 — Scheduled Reports & Alerts
Don’t wait for bad news.
 
Scheduling
•	Daily / Weekly / Monthly
•	Timezone-aware
•	Multiple recipients
 
Alerts
•	Engagement drop
•	SLA breach spike
•	Campaign underperforming
 
API
•	POST /reports/schedule
•	POST /alerts/create
 
Audit
•	report.scheduled
•	alert.triggered
 
FLOW 8.9 — Data Governance, Accuracy & Trust
If numbers are wrong once, trust is lost forever.
 
Governance Rules
•	Data freshness indicator
•	Partial data warnings
•	API quota visibility
•	Platform sync status
 
UI
•	“Data Health” panel
•	Last sync timestamps
•	Error explanations
 
Audit
•	analytics.data_warning_shown
 
🧪 TESTING (MANDATORY)
Unit
•	Metric aggregation
•	Attribution logic
•	Permission scoping
Integration
•	Platform fetch → aggregate → report
•	Partial failure handling
E2E
•	Dashboard accuracy
•	Export validation
•	Scheduled email delivery
 
✅ DEFINITION OF DONE — PHASE 8
✔ Trustworthy analytics backbone
✔ Executive-ready dashboards
✔ Content & revenue insights
✔ Custom & scheduled reports
✔ Alerts & governance in place
✔ Full auditability
 
🚫 WHAT WE WILL NOT BUILD
❌ Vanity metrics with no meaning
❌ Unverifiable AI scores
❌ Raw PII exposure
❌ Charts without context


PHASE 9 — BILLING & SUBSCRIPTIONS (MONETIZATION ENGINE)
Monetization should feel fair, transparent, and boring.
Boring billing = happy customers.
 
🔒 BILLING PRINCIPLES (LOCK THESE)
1.	No surprise charges
2.	Limits visible before breach
3.	Usage metered continuously
4.	Billing actions auditable
5.	Plan logic separate from payment gateway
6.	Grace before punishment
7.	Tenant ≠ Payment method owner always
 
📦 PHASE 9 FLOW MAP
Flow ID	Name
9.1	Plan Definitions & Entitlements
9.2	Subscription Lifecycle
9.3	Usage Metering & Enforcement
9.4	Checkout & Payment Processing
9.5	Invoices & Tax Compliance
9.6	Dunning, Failures & Grace
9.7	Admin Revenue Console
9.8	Audit, Compliance & Controls
 
FLOW 9.1 — Plan Definitions & Entitlements
🎯 Purpose
Define what customers can do, independent of how they pay.
 
DB ENTITIES
plan_definitions
•	id
•	name
•	tier (free, starter, growth, enterprise)
•	billing_cycle (monthly, yearly)
•	price
•	currency
•	features (json)
•	limits (json)
•	is_active
 
Examples of Limits
•	Workspaces
•	Team members
•	Social accounts
•	Posts/month
•	Inbox conversations
•	WhatsApp messages
•	Analytics retention
 
UI
Super Admin → Plans
•	Create/edit plans
•	Preview impact
•	Version history
 
Audit
•	plan.created
•	plan.updated
 
FLOW 9.2 — Subscription Lifecycle
Subscriptions are state machines, not flags.
 
States
trial → active → past_due → suspended → cancelled
 
Transitions
•	Trial start (Phase 0)
•	Upgrade/downgrade
•	Renewal
•	Cancellation (end of period)
 
DB ENTITIES
subscriptions
•	tenant_id
•	plan_id
•	status
•	current_period_start
•	current_period_end
•	cancel_at_period_end
 
Audit
•	subscription.started
•	subscription.upgraded
•	subscription.cancelled
 
FLOW 9.3 — Usage Metering & Enforcement
Limits must be felt before they are hit.
 
Metered Metrics
•	Posts published
•	Messages sent
•	API calls
•	Storage usage
 
Enforcement Levels
Level	Behavior
80%	Warning
95%	Strong warning
100%	Soft block
>100%	Hard block / upgrade prompt
 
UI
•	Usage meters in header
•	Inline warnings
 
Audit
•	usage.warning
•	usage.blocked
 
FLOW 9.4 — Checkout & Payment Processing
Gateways are replaceable. Logic is not.
 
Gateway Strategy
•	Razorpay (India)
•	Stripe-ready architecture (future)
 
Checkout Flow
1.	Select plan
2.	Review pricing
3.	Redirect to gateway
4.	Webhook confirmation
5.	Activate subscription
 
API
•	POST /billing/checkout
•	POST /billing/webhook (signed)
 
Audit
•	payment.initiated
•	payment.success
•	payment.failed
 
FLOW 9.5 — Invoices & Tax Compliance
Finance should never open support tickets.
 
Invoice Generation
•	Auto-generated per cycle
•	Line-item breakdown
•	Tax calculation (GST/VAT)
 
DB ENTITIES
invoices
•	invoice_number
•	tenant_id
•	amount
•	tax_amount
•	status
•	pdf_url
 
UI
•	Billing → Invoices
•	Download PDF
 
Audit
•	invoice.generated
•	invoice.downloaded
 
FLOW 9.6 — Dunning, Failures & Grace
Punishment last. Communication first.
 
Flow
•	Payment fails
•	Retry schedule (3-5-7 days)
•	Email + in-app notifications
•	Grace period
•	Soft suspension
 
UI
•	Payment issue banner
•	Retry button
 
Audit
•	dunning.started
•	subscription.suspended
 
FLOW 9.7 — Admin Revenue Console
Revenue without visibility is risk.
 
Super Admin Views
•	MRR / ARR
•	Churn
•	Upgrades
•	Failed payments
 
UI
URL:
/admin/revenue
 
Audit
•	revenue.viewed
 
FLOW 9.8 — Audit, Compliance & Controls
Billing is legally sensitive.
 
Controls
•	Manual adjustments (logged)
•	Refunds (role-gated)
•	Backdated changes blocked
 
Audit
•	billing.adjusted
•	refund.processed
 
🧪 TESTING (MANDATORY)
Unit
•	Plan limit logic
•	Subscription state transitions
Integration
•	Webhook validation
•	Usage enforcement
E2E
•	Trial → paid upgrade
•	Payment failure → grace → suspension
 
✅ DEFINITION OF DONE — PHASE 9
✔ Transparent pricing
✔ Predictable revenue
✔ Accurate usage metering
✔ Reliable invoices
✔ Graceful failure handling
✔ Finance-grade audit trail
 
🚫 WHAT WE WILL NOT BUILD
❌ Hidden charges
❌ Unlimited plans without control
❌ Hard suspensions without warning
❌ Gateway-coupled business logic



PHASE 10 — INTEGRATIONS & EXTENSIBILITY
(APIs, Webhooks, Marketplace)
Platforms scale faster than products.
Phase 10 ensures BizSocials can grow without rewriting itself.
 
🔒 CORE PRINCIPLES (LOCK THESE)
1.	APIs are first-class citizens
2.	Tenant-scoped by default
3.	No integration without observability
4.	Events > Polling
5.	Extensibility without data leakage
6.	Open standards, open licenses only
7.	Super Admin controls surface area
 
📦 PHASE 10 FLOW MAP
Flow ID	Name
10.1	Public API Access & API Keys
10.2	Outbound Webhooks
10.3	Event Catalog & Contracts
10.4	Incoming Integrations (CRM, Helpdesk, Automation)
10.5	Marketplace & App Registry
10.6	Permissions, Rate Limits & Safety
10.7	Observability & Debugging
10.8	Governance, Compliance & Kill Switches
 
FLOW 10.1 — Public API Access & API Keys
🎯 Purpose
Allow external systems to read & act on BizSocials safely.
 
API DESIGN RULES
•	REST (v1)
•	JSON only
•	Cursor-based pagination
•	Idempotency keys
•	Versioned routes
/api/v1/…
 
DB ENTITIES
api_keys
•	id
•	tenant_id
•	workspace_id (nullable)
•	name
•	hashed_key
•	scopes (json)
•	last_used_at
•	expires_at
•	status
 
UI — Tenant Settings → API Access
•	Create API key
•	Scope selection (read/write)
•	Copy once (never shown again)
•	Revoke / rotate
 
Security
•	HMAC or Bearer token
•	IP allowlist (optional)
•	Per-key rate limits
 
Audit
•	api_key.created
•	api_key.revoked
•	api_key.used
 
FLOW 10.2 — Outbound Webhooks
Webhooks are the nervous system of modern SaaS.
 
Supported Events (Initial)
•	post.published
•	post.failed
•	inbox.message.received
•	whatsapp.message.sent
•	subscription.updated
•	invoice.generated
•	usage.limit_reached
 
DB ENTITIES
webhooks
•	id
•	tenant_id
•	url
•	subscribed_events
•	secret
•	status
webhook_deliveries
•	webhook_id
•	event
•	payload
•	response_code
•	retry_count
•	delivered_at
 
Delivery Rules
•	Signed payloads (HMAC SHA-256)
•	Retry with exponential backoff
•	Disable after repeated failures
 
UI
Tenant → Settings → Webhooks
•	Add endpoint
•	Select events
•	View delivery logs
•	Retry manually
 
Audit
•	webhook.created
•	webhook.delivery.failed
•	webhook.disabled
 
FLOW 10.3 — Event Catalog & Contracts
Events are APIs for the future.
 
Event Catalog (Public Docs)
Each event defines:
•	Name
•	Trigger
•	Payload schema
•	Retry semantics
•	Breaking change policy
 
Example Event
{
  "event": "post.published",
  "tenant_id": "uuid",
  "workspace_id": "uuid",
  "data": {
    "post_id": "uuid",
    "platform": "instagram",
    "published_at": "ISO8601"
  }
}
 
Governance
•	Events versioned
•	Deprecation notices
•	No silent breaking changes
 
FLOW 10.4 — Incoming Integrations
(CRM, Helpdesk, Automation)
Integrations consume BizSocials data and push actions back.
 
Supported Categories (Phase-wise)
•	CRM (HubSpot, Zoho, Salesforce)
•	Helpdesk (Zendesk, Freshdesk)
•	Automation (Zapier, n8n, Make)
 
Integration Pattern
1.	OAuth or API key
2.	Scope approval
3.	Field mapping
4.	Test sync
5.	Activate
 
DB ENTITIES
integrations
•	id
•	tenant_id
•	provider
•	status
•	config (json)
•	last_sync_at
 
Audit
•	integration.connected
•	integration.sync_failed
 
FLOW 10.5 — Marketplace & App Registry
Marketplace is optional, registry is mandatory.
 
Platform App Registry (Super Admin)
Defines:
•	App name
•	Provider
•	Required scopes
•	Events consumed/emitted
•	Review status
 
DB ENTITIES
platform_apps
•	id
•	name
•	provider
•	scopes
•	status
•	reviewed_at
 
Tenant View
•	Browse approved apps
•	Install / uninstall
•	View permissions
 
Audit
•	app.installed
•	app.removed
 
FLOW 10.6 — Permissions, Rate Limits & Safety
Permission Layers
Layer	Scope
API Key	Endpoint access
Integration	Feature access
Workspace	Data boundary
Tenant	Hard boundary
 
Rate Limits
•	Per API key
•	Per tenant
•	Burst + sustained
 
Safety Controls
•	Emergency revoke
•	Kill switch per app
•	Read-only fallback mode
 
Audit
•	rate_limit.exceeded
•	api_access.blocked
 
FLOW 10.7 — Observability & Debugging
If integrations fail silently, customers leave.
 
Tenant Debug Panel
•	API logs
•	Webhook delivery logs
•	Error traces
•	Retry controls
 
Super Admin View
•	Integration health heatmap
•	Failure rates by provider
•	Latency percentiles
 
Audit
•	integration.debug_viewed
 
FLOW 10.8 — Governance, Compliance & Kill Switches
Compliance
•	Data minimization
•	GDPR delete propagation
•	Token revocation cascade
 
Kill Switches (Super Admin)
•	Disable all webhooks
•	Disable specific providers
•	Freeze API access for tenant
 
Audit
•	integration.killed
•	api_access_suspended
 
🧪 TESTING (NON-NEGOTIABLE)
Unit
•	Signature verification
•	Scope enforcement
Integration
•	Webhook retries
•	OAuth refresh
E2E
•	External system receives post.published
•	API key revoked → access denied
 
✅ DEFINITION OF DONE — PHASE 10
✔ Secure public APIs
✔ Reliable webhooks
✔ Clear event contracts
✔ Controlled integrations
✔ Marketplace-ready architecture
✔ Full audit & observability
 
🚫 WHAT WE WILL NOT DO
❌ Unscoped APIs
❌ Tenant data leakage
❌ Silent webhook failures
❌ Unreviewed third-party apps
❌ License-restricted SDKs
PHASE 11 — UX POLISH, ERRORS & DELIGHT
(Stickiness, Retention & Trust Layer)
Users don’t churn because of missing features.
They churn because the product feels hard, confusing, or unsafe.
 
🔒 PHASE 11 PRINCIPLES (LOCK THESE FOREVER)
1.	Every error must teach
2.	Every empty state must guide
3.	Every delay must reassure
4.	Every restriction must explain why
5.	Every success must confirm impact
6.	Nothing should feel broken—even when it fails
7.	UX debt is business debt
 
📦 PHASE 11 FLOW MAP
Flow ID	Name
11.1	Global UX System & Consistency
11.2	Error States & Recovery UX
11.3	Empty States & First-Action Guidance
11.4	Loading, Latency & Feedback
11.5	Permissions, Access & Denial UX
11.6	Product Tours, Nudges & Education
11.7	Accessibility & Inclusivity
11.8	Trust Signals & System Transparency
11.9	Quality Gates & UX Definition of Done
 
FLOW 11.1 — Global UX System & Consistency
🎯 Purpose
Ensure BizSocials feels designed once, not assembled over time.
 
UX SYSTEM RULES
•	One spacing scale
•	One typography scale
•	One color system
•	One interaction pattern per intent
 
Mandatory Global Components
•	Primary / Secondary / Destructive buttons
•	Form fields with inline validation
•	Toasts (success, warning, error)
•	Banners (system-wide)
•	Modals (confirm / destructive)
•	Stepper (wizard)
•	Skeleton loaders
 
Definition of Done
✔ No duplicated UI patterns
✔ No inconsistent copy
✔ No unexplained icons
 
FLOW 11.2 — Error States & Recovery UX
Errors are not failures — silence is.
 
Error Taxonomy
Type	Example
Validation	Required field missing
Permission	Action not allowed
Integration	Token expired
System	API down
Limit	Plan exceeded
 
Error UX MUST INCLUDE
1.	What happened
2.	Why it happened
3.	What user can do now
4.	When to retry
5.	Who to contact (if needed)
 
Examples
•	Token expired → “Reconnect account” CTA
•	Limit exceeded → “Upgrade / Reduce usage”
•	API down → “We’re retrying automatically”
 
Audit
•	error.shown
•	recovery.action_clicked
 
FLOW 11.3 — Empty States & First-Action Guidance
Empty ≠ dead. Empty = opportunity.
 
Mandatory Empty States
•	No workspace
•	No social accounts
•	No posts
•	No inbox messages
•	No reports
•	No team members
 
Empty State Structure
•	Clear headline
•	One-sentence explanation
•	Primary CTA
•	Optional help link
 
Example
“No posts yet.
Create your first post to start building your audience.”
 
Audit
•	empty_state.viewed
•	empty_state.cta_clicked
 
FLOW 11.4 — Loading, Latency & Feedback
Waiting without feedback destroys trust.
 
Rules
•	Skeletons for >300ms
•	Progress bars for long jobs
•	Inline spinners for actions
•	Background jobs must notify on completion
 
Use Cases
•	Post publishing
•	Report generation
•	WhatsApp sync
•	Analytics refresh
 
Audit
•	async.job_started
•	async.job_completed
•	async.job_failed
 
FLOW 11.5 — Permissions, Access & Denial UX
“You can’t do this” is not enough.
 
Permission Denial UX MUST SHOW
•	Action attempted
•	Required role/permission
•	Who can grant access
•	Request access CTA (optional)
 
Example
“You need Publish Approval permission.
Contact Workspace Admin to request access.”
 
Audit
•	permission.denied
•	access.requested
 
FLOW 11.6 — Product Tours, Nudges & Education
Teach without training.
 
Tours (Contextual)
•	First login
•	First post
•	First inbox reply
•	First report
 
Nudges
•	“You haven’t connected Instagram yet”
•	“Approval enabled — posts will wait for review”
•	“You’re close to your plan limit”
 
Rules
•	Dismissible
•	Never block core work
•	Respect user role
 
Audit
•	tour.started
•	tour.completed
•	nudge.dismissed
 
FLOW 11.7 — Accessibility & Inclusivity
If it’s not accessible, it’s not complete.
 
Minimum Standards
•	WCAG 2.1 AA
•	Keyboard navigation
•	Screen reader labels
•	Color contrast compliance
•	Focus indicators
 
Test Cases
•	Keyboard-only navigation
•	Screen reader flows
•	High-contrast mode
 
FLOW 11.8 — Trust Signals & System Transparency
Trust is built when users know what’s happening.
 
Trust Indicators
•	Platform status banner
•	Last sync timestamps
•	Data freshness badges
•	Integration health indicators
 
System Status
•	Degraded mode warnings
•	Maintenance notices
•	Incident updates
 
Audit
•	status.banner_shown
 
FLOW 11.9 — Quality Gates & UX Definition of Done
Phase 11 is enforced, not optional.
 
UX DoD Checklist (MANDATORY FOR EVERY FEATURE)
✔ Has empty state
✔ Has error handling
✔ Has loading feedback
✔ Has permission handling
✔ Has audit event
✔ Has copy reviewed
✔ Has accessibility check
If any ❌ → feature is NOT DONE
 
🧪 TESTING REQUIREMENTS
UX Regression
•	Visual snapshot tests
•	Interaction flows
E2E
•	Error recovery
•	Permission denial
•	Empty → first action
Manual QA
•	First-time user journey
•	Low-connectivity mode
 
✅ DEFINITION OF DONE — PHASE 11
✔ Product feels calm, predictable, human
✔ Errors guide, not frustrate
✔ Users always know what to do next
✔ Trust is continuously reinforced
✔ UX debt reduced to near-zero
 
🚫 WHAT WE WILL NOT ACCEPT
❌ Silent failures
❌ Blank screens
❌ Cryptic error codes
❌ Unexplained limits
❌ Inconsistent UI language

