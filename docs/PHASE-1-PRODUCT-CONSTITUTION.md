# BizSocials — Phase-1 Product Constitution

**Version:** 1.0
**Status:** Approved for Phase-1 Development
**Date:** February 2026
**Owner:** Bizinso Product Team

---

## 1. Executive Summary

BizSocials is a B2B-first Social Media Management Platform targeting Marketing Agencies and In-house Brand Teams. Phase-1 delivers a sellable MVP focused on collaborative content creation, scheduling, engagement management, and basic performance tracking across LinkedIn, Facebook Pages, and Instagram Business.

**Phase-1 Objective:** Launch a commercially viable SaaS product that solves the core workflow pain points for small-to-medium marketing teams (5–15 users) managing social presence for brands.

---

## 2. Target Customer Profile (Phase-1 Only)

### Primary ICP
| Attribute | Definition |
|-----------|------------|
| **Segment** | Marketing Agencies, In-house Brand Teams |
| **Team Size** | 5–15 users per workspace (must support 2–5) |
| **Use Case** | Collaborative social content planning, approval, publishing, and engagement |
| **Platforms Used** | LinkedIn, Facebook Pages, Instagram Business |
| **Pain Points** | Scattered tools, no approval workflow, manual posting, fragmented engagement |

### Explicitly NOT Phase-1 Customers
- Solo creators / freelancers (not optimized for single-user workflows)
- Enterprise teams (50+ users)
- E-commerce brands needing shoppable posts
- Performance marketers focused on paid ads

---

## 3. Phase-1 Scope Definition

### 3.1 What EXISTS in Phase-1

| Domain | Capability | Scope Boundary |
|--------|------------|----------------|
| **Workspaces** | Multi-workspace support | One user can belong to multiple workspaces. One workspace = one billing entity. |
| **Social Accounts** | Connect & manage | LinkedIn Pages, Facebook Pages, Instagram Business only. OAuth-based connection. |
| **Team Management** | Invite, remove, assign roles | Roles: Owner, Admin, Editor, Viewer. Seat limits enforced by plan. |
| **Role-Based Access** | Permission enforcement | Predefined roles only. No custom role creation. |
| **Approval Workflow** | Single-level approval | Draft → Submitted → Approved/Rejected. No multi-level, parallel, or conditional approvals. |
| **Post Creation** | Compose posts | Text, images, videos (within platform limits). Platform-specific preview. |
| **Scheduling** | Schedule posts | Single future datetime. Timezone-aware. Queue-based publishing. |
| **Content Calendar** | Visual calendar view | Monthly/weekly view. Drag-drop rescheduling. Filter by platform, status, author. |
| **Unified Inbox** | Engagement management | Comments and @mentions only. Reply capability. Mark as read/resolved. |
| **Analytics** | Basic performance metrics | Post-level metrics (likes, comments, shares, reach). Aggregate workspace metrics. Date range filtering. |
| **Reports** | Export reports | PDF/CSV export. Predefined report templates only. |
| **AI Assist** | Caption & hashtag suggestions | Assistive only. User must approve/edit. No auto-publishing from AI. |
| **Subscription & Billing** | Plan management | Per-workspace billing. Seat caps per plan. Free trial (time-limited). Stripe integration assumed. |
| **Notifications** | In-app + email alerts | Approval requests, post published, post failed, mentions received. |
| **Audit Log** | Activity tracking | Who did what, when. Workspace-level visibility for Owners/Admins. |

### 3.2 What DOES NOT EXIST in Phase-1

| Category | Excluded Capability | Reason |
|----------|---------------------|--------|
| **Platforms** | Twitter/X, TikTok, YouTube, Pinterest, Google Business | API complexity, rate limits, scope control |
| **Platforms** | Personal LinkedIn/Facebook profiles | API restrictions, B2B focus |
| **Ads** | Paid ad creation, boosting, ad analytics | Out of scope per requirement |
| **Listening** | Keyword monitoring, brand mentions outside owned accounts | Full social listening excluded |
| **Influencer** | Discovery, outreach, campaign management | Marketplace excluded |
| **Attribution** | UTM builder, conversion tracking, ROI dashboards | Advanced attribution excluded |
| **AI** | Auto-scheduling, predictive virality, AI-generated images | Assistive text only |
| **Workflow** | Multi-level approvals, conditional routing, external approvers | Single-level only |
| **Billing** | Usage-based pricing, add-ons, white-label billing | Per-workspace + seat cap only |
| **Integrations** | CRM sync, Zapier, webhooks, API access | No external integrations |
| **Content** | Asset library with tagging, stock image search, Canva integration | Basic upload only |
| **Inbox** | DMs, story replies, lead forms, chatbot | Comments + mentions only |
| **Reports** | Custom report builder, scheduled report delivery, white-label reports | Predefined templates only |
| **Security** | SSO/SAML, 2FA, IP whitelisting | Standard email/password auth only |
| **Localization** | Multi-language UI, RTL support | English only |

---

## 4. Core Domains & Modules (Conceptual)

Phase-1 is organized into **8 bounded domains**. Each domain owns its data, rules, and boundaries.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          BIZSOCIALS PHASE-1                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   IDENTITY   │  │  WORKSPACE   │  │   SOCIAL     │  │   CONTENT    │ │
│  │   & ACCESS   │  │  MANAGEMENT  │  │   ACCOUNTS   │  │   ENGINE     │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  ENGAGEMENT  │  │  ANALYTICS   │  │   BILLING    │  │     AI       │ │
│  │    INBOX     │  │  & REPORTS   │  │   & PLANS    │  │   ASSIST     │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Domain Definitions

#### 4.1 Identity & Access
**Purpose:** User authentication, session management, role enforcement.

| Concept | Definition |
|---------|------------|
| User | Individual with email/password credentials |
| Session | Authenticated state with expiry |
| Role | Predefined permission set (Owner, Admin, Editor, Viewer) |
| Permission | Granular action allowance (e.g., can_approve_posts) |

**Phase-1 Constraints:**
- Email/password authentication only
- No SSO, no 2FA, no social login
- Password reset via email token
- Roles are predefined, not customizable

#### 4.2 Workspace Management
**Purpose:** Organizational container for teams, accounts, content, and billing.

| Concept | Definition |
|---------|------------|
| Workspace | Isolated tenant containing all related data |
| Membership | User's association with a workspace + assigned role |
| Invitation | Pending membership request (email-based) |
| Seat | Countable user slot within a workspace |

**Phase-1 Constraints:**
- One workspace = one billing entity
- User can belong to multiple workspaces
- Workspace deletion is soft-delete (data retained for 30 days)
- No workspace-to-workspace data sharing

#### 4.3 Social Accounts
**Purpose:** OAuth connection and credential management for social platforms.

| Concept | Definition |
|---------|------------|
| Social Account | Connected platform account (LinkedIn Page, FB Page, IG Business) |
| Connection | OAuth token + refresh mechanism |
| Platform | Supported social network (3 in Phase-1) |
| Health Status | Token validity state (connected, expired, revoked) |

**Phase-1 Constraints:**
- LinkedIn Pages, Facebook Pages, Instagram Business only
- Personal profiles not supported
- One social account can belong to one workspace only
- Token refresh handled automatically; manual reconnect if revoked
- No bulk import of accounts

#### 4.4 Content Engine
**Purpose:** Post creation, approval workflow, scheduling, and publishing.

| Concept | Definition |
|---------|------------|
| Post | Content unit with text, media, target platform(s), and status |
| Draft | Unpublished, editable post |
| Submission | Draft sent for approval |
| Approval | Single-level accept/reject decision |
| Schedule | Future datetime for publishing |
| Publishing | Actual delivery to social platform via API |
| Calendar | Visual representation of scheduled/published posts |

**Phase-1 Constraints:**
- Single-level approval only: Draft → Submitted → Approved/Rejected
- Approved posts can be scheduled or published immediately
- Rejected posts return to Draft with reviewer comment
- No bulk post creation
- No recurring posts
- No post templates
- Media upload size limits enforced per platform
- Calendar supports month and week views only

**Post Status State Machine:**
```
┌───────┐    submit    ┌───────────┐   approve   ┌──────────┐   schedule   ┌───────────┐
│ DRAFT │─────────────▶│ SUBMITTED │────────────▶│ APPROVED │─────────────▶│ SCHEDULED │
└───────┘              └───────────┘             └──────────┘              └───────────┘
    ▲                       │                         │                          │
    │                       │ reject                  │ publish now              │ publish
    │                       ▼                         ▼                          ▼
    │                  ┌──────────┐             ┌───────────┐             ┌───────────┐
    └──────────────────│ REJECTED │             │ PUBLISHED │◀────────────│ PUBLISHED │
         (edit)        └──────────┘             └───────────┘             └───────────┘
                                                      │
                                                      ▼
                                                ┌──────────┐
                                                │  FAILED  │
                                                └──────────┘
```

#### 4.5 Engagement Inbox
**Purpose:** Centralized view of audience interactions for response management.

| Concept | Definition |
|---------|------------|
| Inbox Item | Comment or @mention received on connected accounts |
| Reply | Response sent from BizSocials to the platform |
| Status | Read, Unread, Resolved |
| Assignment | Inbox item assigned to team member (optional) |

**Phase-1 Constraints:**
- Comments and @mentions only
- No DMs, story replies, or lead form responses
- Reply capability for comments only (platform API permitting)
- No sentiment analysis
- No canned responses
- No collision detection (two users replying simultaneously)
- Items older than 90 days auto-archived

#### 4.6 Analytics & Reports
**Purpose:** Performance measurement and exportable reporting.

| Concept | Definition |
|---------|------------|
| Metric | Quantitative measure (likes, comments, shares, reach, impressions) |
| Post Analytics | Metrics scoped to individual post |
| Workspace Analytics | Aggregated metrics across posts/accounts |
| Report | Exportable document with predefined structure |
| Date Range | User-selected time window for metrics |

**Phase-1 Constraints:**
- Metrics depend on platform API availability
- No real-time metrics (polling-based, delay acceptable)
- No competitor benchmarking
- No custom metric definitions
- Reports: PDF and CSV only
- Predefined report templates only (no custom builder)
- No scheduled/automated report delivery
- Date range maximum: 90 days

#### 4.7 Billing & Plans
**Purpose:** Subscription management, plan enforcement, payment processing.

| Concept | Definition |
|---------|------------|
| Plan | Subscription tier with defined limits (seats, accounts, features) |
| Subscription | Workspace's active plan + billing cycle |
| Seat | Billable user slot |
| Trial | Time-limited free access (no permanent free tier) |
| Invoice | Payment record |

**Phase-1 Constraints:**
- Per-workspace billing (one workspace = one subscription)
- Seat limits enforced by plan
- Social account limits enforced by plan
- Free trial only (e.g., 14 days), no permanent free tier
- No usage-based billing
- No add-on purchases
- No annual vs monthly toggle in Phase-1 (pick one)
- Payment provider: Stripe (assumed)
- Dunning: Basic retry + workspace suspension on failure

#### 4.8 AI Assist
**Purpose:** Assistive content suggestions to improve post quality.

| Concept | Definition |
|---------|------------|
| Caption Suggestion | AI-generated text options for post body |
| Hashtag Suggestion | AI-recommended hashtags based on content |
| Suggestion | Non-binding recommendation requiring user action |

**Phase-1 Constraints:**
- Assistive only — user must review, edit, and confirm
- No auto-publishing from AI suggestions
- No AI-generated images
- No optimal time suggestions
- No predictive engagement scoring
- Rate-limited per workspace (prevent abuse)
- Explicit user trigger required (no unsolicited suggestions)

---

## 5. Role & Permission Matrix (Phase-1)

| Permission | Owner | Admin | Editor | Viewer |
|------------|:-----:|:-----:|:------:|:------:|
| Manage workspace settings | ✓ | ✓ | ✗ | ✗ |
| Manage billing | ✓ | ✗ | ✗ | ✗ |
| Invite/remove members | ✓ | ✓ | ✗ | ✗ |
| Assign roles | ✓ | ✓ | ✗ | ✗ |
| Connect social accounts | ✓ | ✓ | ✗ | ✗ |
| Create posts | ✓ | ✓ | ✓ | ✗ |
| Submit posts for approval | ✓ | ✓ | ✓ | ✗ |
| Approve/reject posts | ✓ | ✓ | ✗ | ✗ |
| Publish posts directly | ✓ | ✓ | ✗ | ✗ |
| View calendar | ✓ | ✓ | ✓ | ✓ |
| Manage calendar (reschedule) | ✓ | ✓ | ✓ | ✗ |
| View inbox | ✓ | ✓ | ✓ | ✓ |
| Reply to inbox items | ✓ | ✓ | ✓ | ✗ |
| View analytics | ✓ | ✓ | ✓ | ✓ |
| Export reports | ✓ | ✓ | ✓ | ✗ |
| Use AI assist | ✓ | ✓ | ✓ | ✗ |
| View audit log | ✓ | ✓ | ✗ | ✗ |
| Delete workspace | ✓ | ✗ | ✗ | ✗ |

**Phase-1 Constraints:**
- Roles are predefined, not customizable
- One role per user per workspace
- Owner role cannot be removed (must transfer first)
- Minimum one Owner per workspace

---

## 6. Key Assumptions

| ID | Assumption | Impact if Wrong |
|----|------------|-----------------|
| A1 | Meta (Facebook/Instagram) and LinkedIn APIs remain stable and accessible | Core publishing functionality blocked |
| A2 | Target users have Business/Page accounts (not personal) | Cannot connect accounts |
| A3 | Stripe is acceptable as sole payment processor | Billing module redesign |
| A4 | 5–15 user teams don't need granular custom permissions | Permission complaints, churn |
| A5 | Single-level approval covers 80% of agency workflows | Workflow friction, workarounds |
| A6 | English-only UI is acceptable for Phase-1 market | Limits addressable market |
| A7 | AI suggestions via third-party LLM API (e.g., OpenAI) | Cost and latency dependency |
| A8 | Users accept email/password auth without SSO | Enterprise prospects blocked |
| A9 | No free tier still allows competitive trial-to-paid conversion | Lower top-of-funnel |
| A10 | 90-day analytics window is sufficient for Phase-1 users | Reporting limitations complaints |

---

## 7. Key Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|:----------:|:------:|------------|
| R1 | Meta API rate limits or breaking changes | Medium | High | Build retry logic, monitor API changelog, abstract platform layer |
| R2 | OAuth token expiry causing silent publishing failures | High | High | Proactive health checks, user notifications, reconnect prompts |
| R3 | AI suggestion costs exceed budget | Medium | Medium | Rate limiting per workspace, caching common suggestions |
| R4 | Single-level approval insufficient for key prospects | Medium | Medium | Document as Phase-1 constraint, gather feedback for Phase-2 |
| R5 | Inbox volume overwhelms simple UI | Low | Medium | Pagination, filtering, archival rules |
| R6 | Competitors offer free tier, hurting acquisition | Medium | Medium | Emphasize trial experience, focus on B2B value prop |
| R7 | Media upload failures due to format/size mismatches | High | Low | Pre-upload validation, clear error messages |
| R8 | Timezone handling errors in scheduling | Medium | High | Store all times in UTC, convert on display, explicit timezone selection |
| R9 | User expects features from competitor tools | Medium | Medium | Clear product positioning, roadmap transparency |
| R10 | Stripe regional limitations | Low | Medium | Verify supported countries before launch |

---

## 8. Decision Log (Phase-1 Constraints)

Decisions made to keep Phase-1 scope controlled. These are **intentional constraints**, not oversights.

| Decision | Rationale |
|----------|-----------|
| No Twitter/X, TikTok, YouTube | API instability, scope control |
| No personal profile support | API restrictions, B2B focus |
| No multi-level approvals | Covers 80% use cases, avoids complexity |
| No custom roles | Predefined roles sufficient for target team size |
| No DMs in inbox | API complexity, privacy concerns, scope control |
| No recurring posts | Edge cases around failures, timezone shifts |
| No post templates | Can be simulated with drafts, avoids template management UX |
| No asset library | Basic upload sufficient, avoids DAM complexity |
| No SSO/2FA | Target market accepts email/password for now |
| No free tier | Trial-only model, sustainable economics |
| No webhooks/API | Internal use only, no third-party integrations |
| No white-label | B2B direct brand only |
| No mobile app | Responsive web sufficient for Phase-1 |
| English only | Primary market is English-speaking |

---

## 9. Success Criteria (Phase-1 Launch)

| Metric | Target |
|--------|--------|
| Core publishing flow functional | 100% for LinkedIn, Facebook, Instagram |
| Approval workflow functional | Single-level working without errors |
| Inbox displays comments/mentions | Within 15-minute sync delay |
| Analytics data accuracy | Within 5% of native platform metrics |
| Trial-to-paid conversion | Baseline measurement (no target yet) |
| System uptime | 99.5% during business hours |
| Post publishing success rate | ≥ 98% (excluding user errors) |
| Page load time | < 3 seconds for calendar view |

---

## 10. Glossary

| Term | Definition |
|------|------------|
| Workspace | Isolated organizational container; unit of billing and data separation |
| Seat | Countable user slot within a workspace; limited by plan |
| Social Account | OAuth-connected platform account (LinkedIn Page, FB Page, IG Business) |
| Post | Content unit including text, media, target platforms, and lifecycle status |
| Inbox Item | Comment or @mention received on connected social accounts |
| Plan | Subscription tier defining limits and features |
| Owner | Highest-privilege role; manages billing and can delete workspace |
| Admin | High-privilege role; manages team and content but not billing |
| Editor | Content creator role; creates and submits posts, cannot approve |
| Viewer | Read-only role; can view but not create or modify |

---

## 11. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Product Architecture | Initial Phase-1 Constitution |

---

**END OF PHASE-1 PRODUCT CONSTITUTION**
