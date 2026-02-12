# BizSocials — Phase-1 Data Model

**Version:** 1.1
**Status:** Draft for Review
**Date:** February 2026
**Source of Truth:** Phase-1 Product Constitution v1.0

---

## 1. Document Purpose

This document defines ALL data entities required to support the Phase-1 Product Constitution. It serves as the foundation for database design, API contracts, and application logic.

**Guiding Principles:**
- Simpler is better
- No premature optimization
- If ambiguous, choose the simplest model and document as constraint
- No entities beyond Phase-1 scope

---

## 2. Entity Scope Legend

Each entity is marked with one of:

| Scope | Meaning | Example |
|-------|---------|---------|
| **SYSTEM** | Global, shared across all workspaces | Plans, platform definitions |
| **USER** | Belongs to a user, independent of workspace | User profile, password tokens |
| **WORKSPACE** | Belongs to a single workspace tenant | Posts, social accounts, inbox items |

---

## 3. All Status/Lifecycle Enums (Reference)

Defined upfront for clarity and consistency.

### 3.1 UserStatus
```
PENDING_VERIFICATION  → User registered, email not verified
ACTIVE                → Email verified, can access system
SUSPENDED             → Temporarily disabled by admin
DEACTIVATED           → User self-deactivated account
```

### 3.2 WorkspaceStatus
```
ACTIVE                → Normal operation
SUSPENDED             → Payment failed, limited access
DELETED               → Soft-deleted, 30-day retention
```

### 3.3 InvitationStatus
```
PENDING               → Sent, awaiting response
ACCEPTED              → User joined workspace
EXPIRED               → TTL passed without acceptance
REVOKED               → Cancelled by inviter
```

### 3.4 SocialAccountStatus
```
CONNECTED             → OAuth valid, operational
TOKEN_EXPIRED         → Needs re-authentication
REVOKED               → User revoked access on platform
DISCONNECTED          → Manually disconnected by user
```

### 3.5 Platform (Enum)
```
LINKEDIN              → LinkedIn Page
FACEBOOK              → Facebook Page
INSTAGRAM             → Instagram Business
```

### 3.6 PostStatus
```
DRAFT                 → Created, not submitted
SUBMITTED             → Awaiting approval
APPROVED              → Approved, ready to schedule/publish
REJECTED              → Rejected, returned to author
SCHEDULED             → Queued for future publishing
PUBLISHED             → Successfully published
FAILED                → Publishing attempt failed
```

### 3.7 InboxItemType
```
COMMENT               → Comment on a post
MENTION               → @mention of the account
```

### 3.8 InboxItemStatus
```
UNREAD                → New, not viewed
READ                  → Viewed by team member
RESOLVED              → Marked as handled
ARCHIVED              → Auto-archived (90+ days old)
```

### 3.9 SubscriptionStatus
```
TRIALING              → In free trial period
ACTIVE                → Paid and current
PAST_DUE              → Payment failed, in grace period
SUSPENDED             → Access restricted pending payment
CANCELLED             → Subscription ended
```

### 3.10 NotificationType
```
APPROVAL_REQUESTED    → Post submitted for approval
POST_APPROVED         → Post was approved
POST_REJECTED         → Post was rejected
POST_PUBLISHED        → Post successfully published
POST_FAILED           → Post publishing failed
MENTION_RECEIVED      → New @mention in inbox
COMMENT_RECEIVED      → New comment in inbox
INVITATION_RECEIVED   → Invited to workspace
PAYMENT_FAILED        → Subscription payment failed
TRIAL_ENDING          → Trial period ending soon
```

### 3.11 Role (Enum)
```
OWNER                 → Full access, billing control
ADMIN                 → Team + content management
EDITOR                → Content creation, no approvals
VIEWER                → Read-only access
```

### 3.12 AuditAction (Enum)
```
USER_INVITED
USER_REMOVED
ROLE_CHANGED
SOCIAL_ACCOUNT_CONNECTED
SOCIAL_ACCOUNT_DISCONNECTED
POST_CREATED
POST_SUBMITTED
POST_APPROVED
POST_REJECTED
POST_SCHEDULED
POST_PUBLISHED
POST_DELETED
INBOX_REPLIED
WORKSPACE_SETTINGS_UPDATED
SUBSCRIPTION_CHANGED
```

---

## 4. Entities by Domain

---

### DOMAIN 1: Identity & Access

#### 4.1.1 User
**Scope:** USER
**Purpose:** Represents an individual person who can authenticate and access the system.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| email | String | Yes | Unique, used for login |
| password_hash | String | Yes | Hashed password (never plain text) |
| full_name | String | Yes | Display name |
| status | UserStatus | Yes | Account lifecycle state |
| email_verified_at | Timestamp | No | When email was verified |
| created_at | Timestamp | Yes | Account creation time |
| updated_at | Timestamp | Yes | Last modification time |
| last_login_at | Timestamp | No | Most recent successful login |
| timezone | String | Yes | User's preferred timezone (IANA format) |

**Relationships:**
- Has many WorkspaceMemberships
- Has many PasswordResetTokens
- Has many Notifications

**Phase-1 Constraints:**
- Email/password only, no social login
- No profile picture in Phase-1
- No phone number field

---

#### 4.1.2 PasswordResetToken
**Scope:** USER
**Purpose:** Supports secure password reset flow via email.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| user_id | UUID | Yes | FK to User |
| token_hash | String | Yes | Hashed token (never plain text) |
| expires_at | Timestamp | Yes | Token expiry (e.g., 1 hour) |
| used_at | Timestamp | No | When token was used |
| created_at | Timestamp | Yes | Token creation time |

**Relationships:**
- Belongs to User

**Phase-1 Constraints:**
- Single-use tokens
- Auto-invalidate on successful reset

---

#### 4.1.3 EmailVerificationToken
**Scope:** USER
**Purpose:** Supports email verification for new accounts.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| user_id | UUID | Yes | FK to User |
| token_hash | String | Yes | Hashed token |
| expires_at | Timestamp | Yes | Token expiry (e.g., 24 hours) |
| used_at | Timestamp | No | When token was used |
| created_at | Timestamp | Yes | Token creation time |

**Relationships:**
- Belongs to User

---

### DOMAIN 2: Workspace Management

#### 4.2.1 Workspace
**Scope:** WORKSPACE (root entity)
**Purpose:** Isolated organizational container. Unit of billing and data separation.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| name | String | Yes | Display name of workspace |
| slug | String | Yes | URL-friendly unique identifier |
| status | WorkspaceStatus | Yes | Lifecycle state |
| created_at | Timestamp | Yes | Creation time |
| updated_at | Timestamp | Yes | Last modification |
| deleted_at | Timestamp | No | Soft delete timestamp (30-day retention) |
| settings | JSON | No | Workspace-level preferences |

**Relationships:**
- Has many WorkspaceMemberships
- Has many SocialAccounts
- Has many Posts
- Has many InboxItems
- Has one Subscription
- Has many AuditLogs
- Has many Invitations

**Phase-1 Constraints:**
- Settings JSON is minimal (e.g., default timezone)
- No workspace logo/branding
- No custom domain

---

#### 4.2.2 WorkspaceMembership
**Scope:** WORKSPACE
**Purpose:** Join entity linking User to Workspace with assigned role.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| user_id | UUID | Yes | FK to User |
| role | Role | Yes | Assigned permission role |
| joined_at | Timestamp | Yes | When user joined |
| created_at | Timestamp | Yes | Record creation |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Belongs to Workspace
- Belongs to User

**Unique Constraint:** (workspace_id, user_id)

**Phase-1 Constraints:**
- One role per user per workspace
- Every workspace must have at least one OWNER
- User can have different roles in different workspaces

---

#### 4.2.3 Invitation
**Scope:** WORKSPACE
**Purpose:** Pending invitation for a user to join a workspace.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| email | String | Yes | Invitee email address |
| role | Role | Yes | Role to assign upon acceptance |
| status | InvitationStatus | Yes | Invitation state |
| invited_by_user_id | UUID | Yes | FK to User who sent invite |
| token_hash | String | Yes | Hashed invitation token |
| expires_at | Timestamp | Yes | Invitation expiry (e.g., 7 days) |
| accepted_at | Timestamp | No | When accepted |
| created_at | Timestamp | Yes | Invitation creation |

**Relationships:**
- Belongs to Workspace
- Belongs to User (inviter)

**Phase-1 Constraints:**
- Invitations are email-based only
- Cannot invite existing member
- Expired invitations can be resent

---

### DOMAIN 3: Social Accounts

#### 4.3.1 SocialAccount
**Scope:** WORKSPACE
**Purpose:** Represents a connected social media account with OAuth credentials.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| platform | Platform | Yes | LINKEDIN, FACEBOOK, or INSTAGRAM |
| platform_account_id | String | Yes | ID from the social platform |
| account_name | String | Yes | Display name from platform |
| account_username | String | No | Handle/username if applicable |
| profile_image_url | String | No | Avatar URL from platform |
| status | SocialAccountStatus | Yes | Connection health state |
| access_token_encrypted | String | Yes | Encrypted OAuth access token |
| refresh_token_encrypted | String | No | Encrypted refresh token (if platform provides) |
| token_expires_at | Timestamp | No | Access token expiry |
| connected_by_user_id | UUID | Yes | FK to User who connected |
| connected_at | Timestamp | Yes | Initial connection time |
| last_refreshed_at | Timestamp | No | Last token refresh |
| disconnected_at | Timestamp | No | When disconnected |
| created_at | Timestamp | Yes | Record creation |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Belongs to Workspace
- Belongs to User (connector)
- Has many PostTargets
- Has many InboxItems

**Unique Constraint:** (workspace_id, platform, platform_account_id)

**Phase-1 Constraints:**
- One social account belongs to one workspace only
- No bulk import
- Personal profiles not supported
- Token encryption at rest required

---

### DOMAIN 4: Content Engine

#### 4.4.1 Post
**Scope:** WORKSPACE
**Purpose:** Core content unit representing a social media post through its lifecycle.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| created_by_user_id | UUID | Yes | FK to User (author) |
| content_text | Text | No | Post body/caption text |
| status | PostStatus | Yes | Current lifecycle state |
| scheduled_at | Timestamp | No | When to publish (if scheduled) |
| scheduled_timezone | String | No | Timezone for scheduled_at |
| published_at | Timestamp | No | Actual publish time |
| submitted_at | Timestamp | No | When submitted for approval |
| created_at | Timestamp | Yes | Record creation |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Belongs to Workspace
- Belongs to User (author)
- Has many PostMedia
- Has many PostTargets
- Has many ApprovalDecisions (historical, one active)
- Has many PostMetricSnapshots

**Phase-1 Constraints:**
- No recurring posts
- No post templates
- No bulk creation
- Content text can be empty if media-only (platform rules apply)

---

#### 4.4.2 PostMedia
**Scope:** WORKSPACE
**Purpose:** Media attachments (images, videos) for a post.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| post_id | UUID | Yes | FK to Post |
| media_type | String | Yes | "image" or "video" |
| file_url | String | Yes | Storage URL for the file |
| file_size_bytes | Integer | Yes | File size for validation |
| mime_type | String | Yes | e.g., "image/jpeg", "video/mp4" |
| width | Integer | No | Image/video width in pixels |
| height | Integer | No | Image/video height in pixels |
| duration_seconds | Integer | No | Video duration |
| sort_order | Integer | Yes | Display order in carousel |
| created_at | Timestamp | Yes | Upload time |

**Relationships:**
- Belongs to Post

**Phase-1 Constraints:**
- No external URL embeds (uploaded files only)
- No alt-text field in Phase-1
- Storage provider assumed (S3-compatible)

---

#### 4.4.3 PostTarget
**Scope:** WORKSPACE
**Purpose:** Links a post to specific social account(s) it will be published to. Tracks per-platform publishing state.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| post_id | UUID | Yes | FK to Post |
| social_account_id | UUID | Yes | FK to SocialAccount |
| platform_post_id | String | No | ID returned by platform after publish |
| platform_post_url | String | No | Direct link to published post |
| published_at | Timestamp | No | When published to this platform |
| failed_at | Timestamp | No | When publishing failed |
| failure_reason | String | No | Error message from platform |
| created_at | Timestamp | Yes | Record creation |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Belongs to Post
- Belongs to SocialAccount

**Unique Constraint:** (post_id, social_account_id)

**Phase-1 Constraints:**
- One post can target multiple accounts
- Each target tracks its own publish status
- Post-level status reflects aggregate (PUBLISHED only if all targets succeeded)

---

#### 4.4.4 ApprovalDecision
**Scope:** WORKSPACE
**Purpose:** Records approval or rejection decisions for a submitted post. Preserves full decision history for audit.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| post_id | UUID | Yes | FK to Post |
| decided_by_user_id | UUID | Yes | FK to User (approver) |
| decision | String | Yes | "APPROVED" or "REJECTED" |
| comment | Text | No | Reviewer feedback (required if rejected) |
| is_active | Boolean | Yes | True if this is the current decision |
| decided_at | Timestamp | Yes | Decision timestamp |
| created_at | Timestamp | Yes | Record creation |

**Relationships:**
- Belongs to Post (many-to-one)
- Belongs to User (approver)

**Unique Constraint:** (post_id, is_active) WHERE is_active = TRUE — only one active decision per post

**Phase-1 Constraints:**
- Single-level approval only (workflow unchanged)
- Multiple decision records allowed per post (historical)
- Only ONE decision can have is_active = TRUE at a time
- When post is resubmitted: previous decision marked is_active = FALSE, new decision created with is_active = TRUE
- Historical decisions preserved for audit traceability
- UI shows only the active decision; audit log can show history

---

### DOMAIN 5: Engagement Inbox

#### 4.5.1 InboxItem
**Scope:** WORKSPACE
**Purpose:** A comment or @mention received on a connected social account.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| social_account_id | UUID | Yes | FK to SocialAccount |
| post_target_id | UUID | No | FK to PostTarget (if comment on our post) |
| item_type | InboxItemType | Yes | COMMENT or MENTION |
| status | InboxItemStatus | Yes | UNREAD, READ, RESOLVED, ARCHIVED |
| platform_item_id | String | Yes | ID from the social platform |
| platform_post_id | String | No | ID of the post being commented on |
| author_name | String | Yes | Name of commenter/mentioner |
| author_username | String | No | Handle of commenter |
| author_profile_url | String | No | Link to author's profile |
| author_avatar_url | String | No | Author's profile image |
| content_text | Text | Yes | Comment/mention text |
| platform_created_at | Timestamp | Yes | When created on platform |
| assigned_to_user_id | UUID | No | FK to User (optional assignment) |
| assigned_at | Timestamp | No | When assigned (for SLA tracking) |
| resolved_at | Timestamp | No | When marked resolved |
| resolved_by_user_id | UUID | No | FK to User who resolved |
| created_at | Timestamp | Yes | Record creation (sync time) |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Belongs to Workspace
- Belongs to SocialAccount
- Belongs to PostTarget (optional)
- Belongs to User (assignee, optional)
- Has many InboxReplies

**Unique Constraint:** (social_account_id, platform_item_id)

**Phase-1 Constraints:**
- Comments and mentions only (no DMs)
- Auto-archive after 90 days
- No sentiment analysis
- No threading (flat list)

---

#### 4.5.2 InboxReply
**Scope:** WORKSPACE
**Purpose:** A reply sent from BizSocials in response to an inbox item.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| inbox_item_id | UUID | Yes | FK to InboxItem |
| replied_by_user_id | UUID | Yes | FK to User |
| content_text | Text | Yes | Reply message |
| platform_reply_id | String | No | ID returned by platform |
| sent_at | Timestamp | Yes | When sent |
| failed_at | Timestamp | No | If send failed |
| failure_reason | String | No | Error message |
| created_at | Timestamp | Yes | Record creation |

**Relationships:**
- Belongs to InboxItem
- Belongs to User

**Phase-1 Constraints:**
- No canned responses
- No collision detection
- Reply to comments only (not mentions, platform-dependent)

---

### DOMAIN 6: Analytics & Reports

#### 4.6.1 PostMetricSnapshot
**Scope:** WORKSPACE
**Purpose:** Point-in-time snapshot of engagement metrics for a published post target.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| post_target_id | UUID | Yes | FK to PostTarget |
| captured_at | Timestamp | Yes | When metrics were fetched |
| likes_count | Integer | No | Like/reaction count |
| comments_count | Integer | No | Comment count |
| shares_count | Integer | No | Share/repost count |
| impressions_count | Integer | No | Times shown in feeds |
| reach_count | Integer | No | Unique accounts reached |
| clicks_count | Integer | No | Link clicks (if applicable) |
| engagement_rate | Decimal | No | Calculated engagement % |
| raw_response | JSON | No | Full API response for debugging |
| created_at | Timestamp | Yes | Record creation |

**Relationships:**
- Belongs to PostTarget

**Phase-1 Constraints:**
- Metrics depend on platform API availability
- Polling-based (not real-time)
- Stored as snapshots to track changes over time
- Null means not available from platform
- 90-day maximum queryable range

---

#### 4.6.2 ReportExport
**Scope:** WORKSPACE
**Purpose:** Record of an exported report for audit and re-download.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| exported_by_user_id | UUID | Yes | FK to User |
| report_type | String | Yes | Predefined template name |
| format | String | Yes | "PDF" or "CSV" |
| date_range_start | Date | Yes | Report period start |
| date_range_end | Date | Yes | Report period end |
| file_url | String | Yes | Storage URL for download |
| file_size_bytes | Integer | Yes | File size |
| expires_at | Timestamp | Yes | When download link expires |
| created_at | Timestamp | Yes | Export time |

**Relationships:**
- Belongs to Workspace
- Belongs to User

**Phase-1 Constraints:**
- Predefined templates only
- No scheduled delivery
- Files auto-expire (e.g., 7 days)

---

### DOMAIN 7: Billing & Plans

#### 4.7.1 Plan
**Scope:** SYSTEM
**Purpose:** Defines a subscription tier with limits and pricing.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| name | String | Yes | Plan display name (e.g., "Starter", "Pro") |
| slug | String | Yes | Unique identifier for code reference |
| description | String | No | Marketing description |
| price_cents | Integer | Yes | Monthly price in cents |
| currency | String | Yes | ISO currency code (e.g., "USD") |
| seat_limit | Integer | Yes | Max users per workspace |
| social_account_limit | Integer | Yes | Max connected accounts |
| ai_suggestions_monthly_limit | Integer | Yes | Max AI requests per month |
| is_active | Boolean | Yes | Available for new subscriptions |
| sort_order | Integer | Yes | Display order |
| created_at | Timestamp | Yes | Record creation |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Has many Subscriptions

**Phase-1 Constraints:**
- Predefined plans managed by system admins
- No usage-based pricing
- No annual vs monthly toggle (monthly only)
- Price changes don't affect existing subscriptions until renewal

---

#### 4.7.2 Subscription
**Scope:** WORKSPACE
**Purpose:** Links a workspace to its active billing plan.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace (unique) |
| plan_id | UUID | Yes | FK to Plan |
| status | SubscriptionStatus | Yes | Billing state |
| stripe_subscription_id | String | No | Stripe subscription reference |
| stripe_customer_id | String | No | Stripe customer reference |
| trial_ends_at | Timestamp | No | Trial expiry (if trialing) |
| current_period_start | Timestamp | Yes | Billing period start |
| current_period_end | Timestamp | Yes | Billing period end |
| cancelled_at | Timestamp | No | When cancellation requested |
| cancel_at_period_end | Boolean | Yes | Cancel at next renewal? |
| created_at | Timestamp | Yes | Record creation |
| updated_at | Timestamp | Yes | Last modification |

**Relationships:**
- Belongs to Workspace (one-to-one)
- Belongs to Plan

**Unique Constraint:** (workspace_id)

**Phase-1 Constraints:**
- One subscription per workspace
- Stripe is sole payment provider
- No add-ons or overages
- Trial is time-limited (e.g., 14 days)

---

#### 4.7.3 Invoice
**Scope:** WORKSPACE
**Purpose:** Record of billing transactions for history and receipts.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| subscription_id | UUID | Yes | FK to Subscription |
| stripe_invoice_id | String | Yes | Stripe invoice reference |
| amount_cents | Integer | Yes | Invoice total in cents |
| currency | String | Yes | ISO currency code |
| status | String | Yes | "paid", "open", "void", "uncollectible" |
| invoice_url | String | No | Link to Stripe hosted invoice |
| invoice_pdf_url | String | No | Link to PDF |
| period_start | Timestamp | Yes | Billing period start |
| period_end | Timestamp | Yes | Billing period end |
| paid_at | Timestamp | No | When payment succeeded |
| created_at | Timestamp | Yes | Record creation |

**Relationships:**
- Belongs to Subscription

**Phase-1 Constraints:**
- Mirrors Stripe invoice data
- Used for display, not source of truth (Stripe is)

---

### DOMAIN 8: AI Assist

#### 4.8.1 AiSuggestionLog
**Scope:** WORKSPACE
**Purpose:** Tracks AI suggestion requests for rate limiting, usage tracking, and audit.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| user_id | UUID | Yes | FK to User (requester) |
| suggestion_type | String | Yes | "caption" or "hashtag" |
| input_text | Text | No | User-provided context |
| suggestions_returned | JSON | Yes | Array of suggestions provided |
| selected_suggestion | Text | No | Which suggestion user chose |
| tokens_used | Integer | No | LLM tokens consumed |
| latency_ms | Integer | No | Response time |
| created_at | Timestamp | Yes | Request time |

**Relationships:**
- Belongs to Workspace
- Belongs to User

**Phase-1 Constraints:**
- Rate-limited per workspace per month
- Used for usage enforcement against plan limits
- No caching of suggestions

---

### CROSS-DOMAIN: Notifications & Audit

#### 4.9.1 Notification
**Scope:** USER (delivered to user) + WORKSPACE (contextual)
**Purpose:** In-app and email notification records.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| user_id | UUID | Yes | FK to User (recipient) |
| workspace_id | UUID | No | FK to Workspace (context, if applicable) |
| type | NotificationType | Yes | Notification category |
| title | String | Yes | Short headline |
| body | Text | No | Detailed message |
| resource_type | String | No | e.g., "Post", "Invitation" |
| resource_id | UUID | No | ID of related entity |
| is_read | Boolean | Yes | Read status |
| email_sent | Boolean | Yes | Whether email was sent |
| email_sent_at | Timestamp | No | When email sent |
| read_at | Timestamp | No | When marked read |
| created_at | Timestamp | Yes | Notification creation |

**Relationships:**
- Belongs to User
- Belongs to Workspace (optional)

**Phase-1 Constraints:**
- No notification preferences (all enabled)
- No push notifications (email + in-app only)
- No batching/digest

**Design Note:** Notification delivery is user-centric (delivered to user_id). The workspace_id field provides context only (e.g., which workspace the post belongs to). Queries for "my notifications" filter by user_id, not workspace_id.

---

#### 4.9.2 AuditLog
**Scope:** WORKSPACE
**Purpose:** Immutable record of significant actions for compliance and debugging.

| Attribute | Type | Required | Description |
|-----------|------|:--------:|-------------|
| id | UUID | Yes | Primary identifier |
| workspace_id | UUID | Yes | FK to Workspace |
| user_id | UUID | No | FK to User (actor), null if system |
| action | AuditAction | Yes | What happened |
| resource_type | String | Yes | Entity type affected |
| resource_id | UUID | No | Entity ID affected |
| details | JSON | No | Additional context |
| ip_address | String | No | Actor's IP address |
| user_agent | String | No | Actor's browser/client |
| created_at | Timestamp | Yes | When action occurred |

**Relationships:**
- Belongs to Workspace
- Belongs to User (optional)

**Phase-1 Constraints:**
- Append-only (no updates or deletes)
- Visible to Owner and Admin only
- No export functionality in Phase-1
- Retention: indefinite (no auto-purge)

---

## 5. Entity Inventory Summary

### By Scope

| Scope | Entities |
|-------|----------|
| **SYSTEM** | Plan |
| **USER** | User, PasswordResetToken, EmailVerificationToken, Notification |
| **WORKSPACE** | Workspace, WorkspaceMembership, Invitation, SocialAccount, Post, PostMedia, PostTarget, ApprovalDecision, InboxItem, InboxReply, PostMetricSnapshot, ReportExport, Subscription, Invoice, AiSuggestionLog, AuditLog |

### By Domain

| Domain | Entities | Count |
|--------|----------|:-----:|
| Identity & Access | User, PasswordResetToken, EmailVerificationToken | 3 |
| Workspace Management | Workspace, WorkspaceMembership, Invitation | 3 |
| Social Accounts | SocialAccount | 1 |
| Content Engine | Post, PostMedia, PostTarget, ApprovalDecision | 4 |
| Engagement Inbox | InboxItem, InboxReply | 2 |
| Analytics & Reports | PostMetricSnapshot, ReportExport | 2 |
| Billing & Plans | Plan, Subscription, Invoice | 3 |
| AI Assist | AiSuggestionLog | 1 |
| Cross-Domain | Notification, AuditLog | 2 |
| **TOTAL** | | **21** |

---

## 6. Entity Relationship Overview

```
                                    ┌─────────────────┐
                                    │      Plan       │ SYSTEM
                                    │   (defines)     │
                                    └────────┬────────┘
                                             │
┌──────────────┐                             │
│     User     │ USER                        │
└──────┬───────┘                             │
       │ has many                            │
       ▼                                     ▼
┌──────────────────┐    belongs to    ┌─────────────────┐
│   Membership     │◀────────────────▶│   Workspace     │ WORKSPACE
└──────────────────┘                  └────────┬────────┘
                                               │
       ┌───────────────┬───────────────┬───────┴───────┬───────────────┐
       │               │               │               │               │
       ▼               ▼               ▼               ▼               ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ SocialAcct  │ │    Post     │ │ InboxItem   │ │Subscription │ │  AuditLog   │
└──────┬──────┘ └──────┬──────┘ └──────┬──────┘ └──────┬──────┘ └─────────────┘
       │               │               │               │
       │               ├───────────────┤               │
       │               ▼               ▼               ▼
       │        ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
       │        │ PostTarget  │ │ InboxReply  │ │   Invoice   │
       │        └──────┬──────┘ └─────────────┘ └─────────────┘
       │               │
       └───────────────┤
                       ▼
                ┌─────────────┐
                │PostMetric   │
                │ Snapshot    │
                └─────────────┘
```

---

## 7. Data Intentionally NOT Stored in Phase-1

| Data Type | Reason Not Stored |
|-----------|-------------------|
| User profile pictures | Not in Phase-1 scope |
| Workspace logos/branding | No white-label support |
| Custom roles/permissions | Predefined roles only |
| Post templates | Not in Phase-1 scope |
| Asset library metadata | No DAM in Phase-1 |
| Hashtag/tag collections | Not in Phase-1 scope |
| Canned responses | Not in Phase-1 scope |
| Notification preferences | All notifications enabled |
| DM/direct message data | Inbox is comments/mentions only |
| Ad campaign data | Paid ads out of scope |
| Competitor data | No social listening |
| UTM parameters | No attribution tracking |
| Conversion/ROI data | No analytics attribution |
| Webhook configurations | No external integrations |
| API keys/tokens for users | No public API |
| SSO/SAML configuration | Email/password only |
| 2FA secrets | No 2FA in Phase-1 |
| Session tokens in DB | Stateless JWT assumed |
| Full platform API responses | Only metrics stored, raw optional |
| Comment threading hierarchy | Flat list only |
| Sentiment scores | No sentiment analysis |
| Optimal posting times | No AI scheduling |
| Predictive engagement scores | No predictive features |

---

## 8. Phase-1 Data Model Constraints (Summary)

| Constraint | Rationale |
|------------|-----------|
| Soft-delete only on Workspace | 30-day retention requirement |
| One ACTIVE ApprovalDecision per Post | Single-level approval; history preserved for audit |
| No polymorphic relationships | Keep joins simple |
| JSON fields limited to settings/raw_response | Avoid query complexity |
| All timestamps in UTC | Timezone conversion at display layer |
| UUIDs for all primary keys | No auto-increment leakage |
| Encrypted tokens stored, never plain | Security requirement |
| Stripe IDs stored as reference | Stripe is source of truth for billing |
| Metrics as snapshots, not live | Polling-based, historical tracking |
| 90-day inbox auto-archive | Prevent unbounded growth |

---

## 9. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Data Architecture | Initial Phase-1 data model |
| 1.1 | Feb 2026 | Data Architecture | Fixed ApprovalDecision to preserve history (is_active flag); added assigned_at to InboxItem; added Notification design note |

---

**END OF PHASE-1 DATA MODEL**
