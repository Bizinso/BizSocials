# BizSocials — Phase-1 Implementation Blueprint

**Version:** 1.1
**Status:** Approved for Development
**Date:** February 2026
**Prerequisites:** Product Constitution v1.0, Data Model v1.1, API Contract v1.1

---

## 1. Document Purpose

This blueprint defines the **build order** for Phase-1 implementation. It ensures:
- Foundational modules are built first
- Dependencies are clear before development starts
- Teams can work in parallel where possible
- Progress is measurable through defined milestones

**Audience:** Development team, technical founder, project manager

---

## 2. Domain Dependency Map

```
                                    ┌─────────────────┐
                                    │      PLANS      │ (System seed data)
                                    │    (Billing)    │
                                    └────────┬────────┘
                                             │
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│   ┌─────────────────┐                                                       │
│   │    IDENTITY     │ ◀── FOUNDATION (Build First)                         │
│   │    & ACCESS     │                                                       │
│   └────────┬────────┘                                                       │
│            │                                                                │
│            ▼                                                                │
│   ┌─────────────────┐         ┌─────────────────┐                          │
│   │   WORKSPACE     │────────▶│   SUBSCRIPTION  │                          │
│   │   MANAGEMENT    │         │    (Billing)    │                          │
│   └────────┬────────┘         └─────────────────┘                          │
│            │                                                                │
│            ├──────────────────┬──────────────────┐                         │
│            │                  │                  │                         │
│            ▼                  ▼                  ▼                         │
│   ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐             │
│   │     SOCIAL      │ │   AUDIT LOG     │ │  NOTIFICATIONS  │             │
│   │    ACCOUNTS     │ │  (Cross-cut)    │ │   (Cross-cut)   │             │
│   └────────┬────────┘ └─────────────────┘ └─────────────────┘             │
│            │                                                                │
│            ▼                                                                │
│   ┌─────────────────┐                                                       │
│   │    CONTENT      │ ◀── CORE VALUE (Build Second)                        │
│   │     ENGINE      │                                                       │
│   └────────┬────────┘                                                       │
│            │                                                                │
│            ├──────────────────┬──────────────────┐                         │
│            │                  │                  │                         │
│            ▼                  ▼                  ▼                         │
│   ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐             │
│   │   ENGAGEMENT    │ │   ANALYTICS     │ │    AI ASSIST    │             │
│   │     INBOX       │ │   & REPORTS     │ │   (Optional)    │             │
│   └─────────────────┘ └─────────────────┘ └─────────────────┘             │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Build Order (Sequential Layers)

### Layer 0: Project Foundation
**Must complete before any domain work**

| Task | Description | Blocks |
|------|-------------|--------|
| Project scaffolding | Laravel project setup, folder structure | Everything |
| Database configuration | MySQL connection, migration setup | All migrations |
| Authentication scaffold | JWT implementation, middleware | All protected routes |
| Base API structure | Response formats, error handling, validation | All endpoints |
| Queue configuration | Job dispatcher setup | Async operations |
| Scheduler configuration | Cron setup for scheduled tasks | Scheduled publishing, syncs |
| **Tenancy infrastructure** | Workspace scope middleware, global query scopes | All workspace endpoints |

**Mandatory Tenancy Rule (See: `docs/07_saas_tenancy_enforcement.md`):**
> All queued jobs MUST receive `workspace_id` as a required parameter and MUST enforce tenant isolation internally. Jobs without workspace context are forbidden.

**Deliverable:** Empty Laravel project with auth middleware, workspace tenancy enforcement, base controllers, and standard response helpers.

---

### Layer 1: Identity & Access
**Foundation — Everything depends on this**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| User | P0 | Core entity; email/password auth |
| EmailVerificationToken | P0 | Required for registration flow |
| PasswordResetToken | P0 | Required for forgot password |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| Auth (register, login, logout, refresh) | P0 | 6 |
| Email verification | P0 | 2 |
| Password reset | P0 | 2 |
| User profile | P1 | 3 |

**Unblocks:** Workspace Management, all authenticated endpoints

**Deliverable:** User can register, verify email, login, logout, reset password, view/update profile.

---

### Layer 2: Workspace Management + Billing Foundation
**Second foundation — Most features are workspace-scoped**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| Plan | P0 | Seed data; defines limits |
| Workspace | P0 | Core tenant entity |
| WorkspaceMembership | P0 | User-workspace-role link |
| Subscription | P0 | Links workspace to plan |
| Invitation | P1 | Can stub email sending initially |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| Plans (list) | P0 | 1 |
| Workspaces (CRUD) | P0 | 5 |
| Members (list, update role, remove) | P0 | 4 |
| Invitations (create, list, revoke, accept) | P1 | 4 |
| Subscription (get, portal, checkout) | P1 | 5 |

**Dependencies:** Layer 1 complete

**Unblocks:** Social Accounts, Content Engine, all workspace-scoped features

**Stub/Mock:**
- Stripe integration → Return mock portal URLs initially
- Email sending for invitations → Log to console initially

**Plan Limits Enforcement Timing:**
| Limit Type | During Trial | After Paid Activation |
|------------|--------------|----------------------|
| Seat limit | **Soft** — Warn at limit, allow +1 grace | **Hard** — Block invite at limit |
| Social account limit | **Soft** — Warn at limit, allow +1 grace | **Hard** — Block connect at limit |
| AI suggestion limit | **Hard** — Enforce from day 1 | **Hard** — Enforce strictly |

*Rationale: Soft limits during trial encourage exploration without frustration. Hard limits post-payment protect revenue and set expectations.*

**Deliverable:** User can create workspace, invite members, assign roles. Workspace has trial subscription.

---

### Layer 3A: Social Accounts
**Parallel track — Enables content publishing**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| SocialAccount | P0 | OAuth credentials storage |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| List accounts | P0 | 1 |
| OAuth initiate | P0 | 1 |
| OAuth callback | P0 | 1 |
| Disconnect | P1 | 1 |
| Reconnect | P1 | 1 |
| Health check | P2 | 1 |

**Dependencies:** Layer 2 complete

**Unblocks:** Content Engine (post targets), Engagement Inbox

**Stub/Mock:**
- Real OAuth → Use sandbox/test credentials initially
- Token refresh → Implement basic; edge cases later
- Health check → Return static "connected" initially

**Platform Build Order:**
1. **Facebook Pages** — Most common, Meta API shared with Instagram
2. **Instagram Business** — Shares Meta API infrastructure
3. **LinkedIn Pages** — Separate API, add after Meta works

**Deliverable:** User can connect Facebook Page via OAuth. Account appears in list with status.

---

### Layer 3B: Audit Log (Cross-cutting)
**Parallel track — Wire into domains as they're built**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| AuditLog | P0 | Append-only event log |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| List audit logs | P1 | 1 |

**Dependencies:** Layer 2 complete

**Implementation Approach:**
- Create AuditLog service/trait
- Wire into each domain as it's built
- Start logging: user invited, role changed, social account connected

**Deliverable:** Actions are logged. Owner/Admin can view audit history.

---

### Layer 4: Content Engine (Core)
**The product's core value — Build thoroughly**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| Post | P0 | Core content entity |
| PostMedia | P0 | Media attachments |
| PostTarget | P0 | Multi-platform targeting |
| ApprovalDecision | P0 | Approval workflow |

**Build in Sub-Phases:**

#### 4A: Post CRUD + Media
| Endpoint | Priority |
|----------|:--------:|
| Create post (draft) | P0 |
| Get post | P0 |
| Update post | P0 |
| Delete post | P0 |
| List posts | P0 |
| Upload media | P0 |
| Delete media | P1 |
| Reorder media | P2 |

**Deliverable:** User can create draft posts with text and images targeting connected accounts.

#### 4B: Approval Workflow
| Endpoint | Priority |
|----------|:--------:|
| Submit for approval | P0 |
| Approve post | P0 |
| Reject post | P0 |

**Deliverable:** Editor submits post, Admin/Owner approves or rejects with comment.

#### 4C: Scheduling
| Endpoint | Priority |
|----------|:--------:|
| Schedule post | P0 |
| Reschedule post | P0 |
| Unschedule post | P1 |

**Deliverable:** Approved posts can be scheduled for future datetime.

#### 4D: Publishing
| Endpoint | Priority |
|----------|:--------:|
| Publish now | P0 |
| (Background) Scheduled publishing job | P0 |
| (Background) Platform API integration | P0 |

**Dependencies:** Social Accounts Layer complete

**Stub/Mock:**
- Platform publishing → Log "would publish to X" initially
- Then implement real publishing per platform

**Platform Publishing Order:**
1. Facebook Pages
2. Instagram Business
3. LinkedIn Pages

**Deliverable:** Posts publish to connected Facebook Page at scheduled time.

#### 4E: Calendar View
| Endpoint | Priority |
|----------|:--------:|
| Get calendar | P1 |

**Deliverable:** Calendar returns posts by date range with status indicators.

---

### Layer 5A: Engagement Inbox
**Depends on Social Accounts + Content Engine**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| InboxItem | P0 | Comments and mentions |
| InboxReply | P0 | Sent replies |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| List inbox items | P0 | 1 |
| Get inbox item | P0 | 1 |
| Reply to item | P0 | 1 |
| Mark read/resolved | P1 | 4 |
| Assign/unassign | P2 | 2 |
| Bulk mark read | P2 | 1 |

**Background Jobs:**
| Job | Priority | Schedule |
|-----|:--------:|----------|
| Sync inbox from platforms | P0 | Every 15 minutes |

**Dependencies:** Layer 3A (Social Accounts), Layer 4 (Content Engine for post linking)

**Stub/Mock:**
- Platform sync → Seed test data initially
- Reply sending → Log "would reply" initially

**Data Volume Guardrails:**
| Rule | Value | Behavior |
|------|-------|----------|
| Default page size | 20 items | Standard pagination |
| Max page size | 100 items | Reject if `per_page > 100` |
| Auto-archive threshold | 90 days | Items older than 90 days → ARCHIVED status |
| Archive cleanup | Weekly job | Move archived items to cold storage (optional) |
| Max unread count display | 999+ | UI shows "999+" if count exceeds |

*Rationale: High-volume agencies may receive thousands of comments. Pagination and archival prevent database bloat and UI overload.*

**Platform Sync Order:**
1. Facebook Pages (comments)
2. Instagram Business (comments)
3. LinkedIn Pages (comments)

**Deliverable:** Comments from Facebook appear in inbox. User can reply.

---

### Layer 5B: Analytics & Reports
**Depends on published posts existing**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| PostMetricSnapshot | P0 | Point-in-time metrics |
| ReportExport | P1 | Generated reports |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| Get post analytics | P0 | 1 |
| Get workspace analytics | P0 | 1 |
| Generate report | P1 | 1 |
| List/get reports | P1 | 2 |

**Background Jobs:**
| Job | Priority | Schedule |
|-----|:--------:|----------|
| Fetch post metrics | P0 | Every 6 hours |
| Generate report (async) | P1 | On-demand |

**Dependencies:** Layer 4D (Publishing complete, posts exist on platforms)

**Stub/Mock:**
- Metrics fetch → Return mock metrics initially
- Report generation → Generate simple CSV first, PDF later

**Deliverable:** Published post shows engagement metrics. Basic report exports.

---

### Layer 5C: Notifications
**Cross-cutting — Add incrementally**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| Notification | P1 | In-app + email records |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| List notifications | P1 | 1 |
| Mark read | P1 | 2 |
| Unread count | P1 | 1 |

**Implementation Approach:**
- Create notification dispatch service
- Add to each domain incrementally:
  1. Post submitted for approval → notify approvers
  2. Post approved/rejected → notify author
  3. Post published/failed → notify author
  4. New comment/mention → notify assignee or all
  5. Invitation received → notify invitee

**Stub/Mock:**
- Email sending → Log to console initially
- In-app → Database insert from start

**Deliverable:** Users see in-app notifications. Email notifications work.

---

### Layer 6: AI Assist
**Optional enhancement — Add after core is stable**

| Entity | Priority | Notes |
|--------|:--------:|-------|
| AiSuggestionLog | P2 | Usage tracking |

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| Generate captions | P2 | 1 |
| Generate hashtags | P2 | 1 |
| Get usage | P2 | 1 |

**Dependencies:** Layer 2 (Workspace + Subscription for limits)

**Stub/Mock:**
- LLM integration → Return canned suggestions initially
- Rate limiting → Implement after LLM connected

**Deliverable:** User gets AI caption suggestions when composing posts.

---

### Layer 7: Billing Completion
**Finalize after core features work**

| Endpoint Group | Priority | Count |
|----------------|:--------:|:-----:|
| Stripe checkout (real) | P1 | 1 |
| Billing portal (real) | P1 | 1 |
| List invoices | P2 | 1 |
| Cancel/resume subscription | P2 | 2 |
| Stripe webhooks | P1 | N/A |

**Webhook Events to Handle:**
- `checkout.session.completed` → Activate subscription
- `invoice.paid` → Record invoice
- `invoice.payment_failed` → Mark past_due, notify
- `customer.subscription.updated` → Sync plan changes
- `customer.subscription.deleted` → Mark cancelled

**Dependencies:** All core features working (user needs something to pay for)

**Deliverable:** User can upgrade from trial, pay via Stripe, view invoices.

---

## 4. What to Stub vs Implement

### Stub Initially (Replace Later)

| Component | Stub Behavior | When to Replace |
|-----------|---------------|-----------------|
| OAuth flow | Return test tokens | Before social account testing |
| Platform publishing | Log "published to X" | Before publishing testing |
| Platform inbox sync | Seed fake comments | Before inbox testing |
| Platform metrics fetch | Return mock numbers | Before analytics testing |
| Stripe checkout | Return mock session | Before billing testing |
| Stripe portal | Return mock URL | Before billing testing |
| Email sending | Log to console | Before user acceptance testing |
| LLM API | Return canned suggestions | Before AI feature testing |

### Implement From Start (No Stubbing)

| Component | Reason |
|-----------|--------|
| User authentication | Security critical |
| Password hashing | Security critical |
| JWT token handling | Security critical |
| Role-based authorization | Security critical |
| Workspace data isolation | Security critical |
| Audit logging | Compliance requirement |
| Database transactions | Data integrity |
| File upload to storage | Complex to mock correctly |

---

## 5. Development Milestones

### Milestone 1: "Users Can Sign Up and Create Workspaces"
**Target: Week 2**

| Capability | Status |
|------------|--------|
| User registration with email verification | Required |
| Login/logout with JWT | Required |
| Password reset flow | Required |
| Create workspace (auto-trial) | Required |
| View workspace | Required |
| Basic role enforcement | Required |

**Exit Criteria:**
- [ ] User registers, receives verification email, verifies
- [ ] User logs in, receives JWT
- [ ] User creates workspace, becomes Owner
- [ ] Workspace shows trial subscription status
- [ ] API returns 403 for unauthorized workspace access

---

### Milestone 2: "Teams Can Be Formed"
**Target: Week 3**

| Capability | Status |
|------------|--------|
| Invite user to workspace | Required |
| Accept invitation | Required |
| List workspace members | Required |
| Change member role | Required |
| Remove member | Required |
| Leave workspace | Required |

**Exit Criteria:**
- [ ] Owner invites user by email
- [ ] Invitee receives email (logged), accepts via token
- [ ] New member appears in list with assigned role
- [ ] Owner changes Editor to Viewer
- [ ] Admin cannot remove Owner
- [ ] Audit log shows member events

---

### Milestone 3: "Social Accounts Connect"
**Target: Week 4**

| Capability | Status |
|------------|--------|
| Connect Facebook Page | Required |
| Connect Instagram Business | Required |
| Connect LinkedIn Page | Stretch |
| List connected accounts | Required |
| Disconnect account | Required |

**Exit Criteria:**
- [ ] User clicks Connect, redirects to Facebook OAuth
- [ ] Callback stores tokens (encrypted)
- [ ] Account appears in list with name and status
- [ ] User can disconnect account
- [ ] Cannot disconnect account with scheduled posts (error)

---

### Milestone 4: "Posts Can Be Created and Approved"
**Target: Week 6**

| Capability | Status |
|------------|--------|
| Create draft post | Required |
| Upload images to post | Required |
| Select target accounts | Required |
| Submit for approval | Required |
| Approve post | Required |
| Reject post with comment | Required |
| Edit rejected post, resubmit | Required |

**Exit Criteria:**
- [ ] Editor creates post with text and image
- [ ] Editor selects Facebook + Instagram targets
- [ ] Editor submits post
- [ ] Admin sees pending post, approves
- [ ] Admin sees another post, rejects with "needs better image"
- [ ] Editor sees rejection comment, edits, resubmits
- [ ] Approval history preserved (visible to Admin)
- [ ] Audit log shows all approval events

---

### Milestone 5: "Posts Publish to Social Platforms"
**Target: Week 8**

| Capability | Status |
|------------|--------|
| Schedule approved post | Required |
| Reschedule post | Required |
| Unschedule post | Required |
| Publish now (async) | Required |
| Scheduled publishing job | Required |
| Publishing to Facebook | Required |
| Publishing to Instagram | Required |
| Publishing to LinkedIn | Stretch |
| Handle publish failures | Required |

**Exit Criteria:**
- [ ] Admin schedules post for tomorrow 9am
- [ ] Calendar shows post on correct date
- [ ] At 9am, job picks up post, publishes to Facebook
- [ ] Post status changes to PUBLISHED
- [ ] PostTarget has platform_post_id and URL
- [ ] Failed publish marks post as FAILED with reason
- [ ] Notification sent on publish success/failure

---

### Milestone 6: "Inbox Shows Engagement"
**Target: Week 10**

| Capability | Status |
|------------|--------|
| Sync comments from Facebook | Required |
| Sync comments from Instagram | Required |
| Sync mentions | Stretch |
| List inbox items | Required |
| Mark as read/resolved | Required |
| Reply to comment | Required |
| Assign to team member | Stretch |

**Exit Criteria:**
- [ ] Sync job runs every 15 minutes
- [ ] New Facebook comment appears in inbox
- [ ] User opens item, status changes to READ
- [ ] User types reply, sends to Facebook
- [ ] Reply appears on Facebook (verify manually)
- [ ] User marks item as RESOLVED
- [ ] 90+ day old items auto-archived

---

### Milestone 7: "Analytics Show Performance"
**Target: Week 11**

| Capability | Status |
|------------|--------|
| Fetch metrics from Facebook | Required |
| Fetch metrics from Instagram | Required |
| View post analytics | Required |
| View workspace analytics | Required |
| Export report (CSV) | Required |
| Export report (PDF) | Stretch |

**Exit Criteria:**
- [ ] Metrics job runs every 6 hours
- [ ] Published post shows likes, comments, shares
- [ ] Workspace summary shows totals for date range
- [ ] User generates CSV report, downloads file
- [ ] Report shows in exports list with expiry

---

### Milestone 8: "Billing Works End-to-End"
**Target: Week 12**

| Capability | Status |
|------------|--------|
| View current subscription | Required |
| Upgrade via Stripe Checkout | Required |
| Access Stripe billing portal | Required |
| View invoice history | Required |
| Cancel subscription | Required |
| Enforce seat limits | Required |
| Enforce account limits | Required |

**Exit Criteria:**
- [ ] Trial shows days remaining
- [ ] User clicks upgrade, goes to Stripe Checkout
- [ ] Payment succeeds, subscription active
- [ ] User accesses billing portal, updates card
- [ ] Invoices appear in list with PDF links
- [ ] User cancels, shown end date
- [ ] Workspace at seat limit cannot invite (error)
- [ ] Workspace at account limit cannot connect (error)

---

### Milestone 9: "AI Assists Content Creation"
**Target: Week 13**

| Capability | Status |
|------------|--------|
| Generate caption suggestions | Required |
| Generate hashtag suggestions | Required |
| Track AI usage | Required |
| Enforce monthly limits | Required |

**Exit Criteria:**
- [ ] User clicks "Suggest Caption" while composing
- [ ] 3 caption options appear
- [ ] User clicks "Suggest Hashtags"
- [ ] 10 hashtags appear
- [ ] Usage counter increments
- [ ] At limit, request returns rate limit error
- [ ] Usage resets at month start

---

### Milestone 10: "Production Ready"
**Target: Week 14**

| Capability | Status |
|------------|--------|
| All notifications working | Required |
| All audit logging complete | Required |
| Error handling comprehensive | Required |
| Rate limiting enforced | Required |
| All edge cases handled | Required |
| Manual QA complete | Required |

**Exit Criteria:**
- [ ] Full user journey works without errors
- [ ] Notifications arrive for all key events
- [ ] Audit log captures all significant actions
- [ ] Rate limits prevent abuse
- [ ] No 500 errors in testing
- [ ] Performance acceptable (< 3s page loads)

---

## 6. Parallel Work Streams

Once Layer 2 is complete, these can proceed in parallel:

```
Week 4+:
├── Stream A: Social Accounts → Publishing → Inbox
├── Stream B: Audit Log → Notifications
└── Stream C: Billing (Stripe integration)

Week 8+:
├── Stream A: Analytics → Reports
├── Stream B: AI Assist
└── Stream C: Billing completion
```

**Recommended Team Split (if multiple developers):**

| Developer | Focus |
|-----------|-------|
| Dev 1 | Auth → Workspace → Content Engine |
| Dev 2 | Social Accounts → Platform APIs → Inbox |
| Dev 3 (if available) | Billing → Notifications → AI |

---

## 7. Risk Mitigation by Build Order

| Risk | Mitigation via Build Order |
|------|----------------------------|
| OAuth complexity delays everything | Social Accounts early, stub initially |
| Platform API changes break publishing | Abstract platform layer, implement one first |
| Stripe integration delays billing | Stub checkout, implement after core works |
| AI costs uncertain | Build last, easy to cut if needed |
| Approval workflow edge cases | Build thoroughly in Layer 4, lots of testing |
| Inbox volume overwhelms | Build after content, understand volume first |

---

## 8. Definition of Done (Per Milestone)

Every milestone must meet:

- [ ] All "Required" capabilities functional
- [ ] API endpoints match contract v1.1
- [ ] Role-based access enforced
- [ ] Audit events logged
- [ ] Error responses follow standard format
- [ ] Basic happy-path tested
- [ ] No known critical bugs

---

## 9. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Solution Architecture | Initial implementation blueprint |
| 1.1 | Feb 2026 | Solution Architecture | Added: tenancy infrastructure to Layer 0; background job tenancy rule; plan limits soft/hard enforcement timing; inbox data volume guardrails |

---

**END OF PHASE-1 IMPLEMENTATION BLUEPRINT**
