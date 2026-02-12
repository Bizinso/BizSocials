# BizSocials — Expanded Data Model

**Version:** 2.0
**Date:** February 2026
**Status:** Draft
**Purpose:** Complete data model for enterprise-grade multi-tenant SaaS platform

---

## 1. Platform Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           DATA HIERARCHY                                         │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  LEVEL 0: PLATFORM                                                              │
│  └── SuperAdmin (Bizinso team)                                                  │
│      └── Platform-wide configuration, all tenant management                    │
│                                                                                 │
│  LEVEL 1: TENANT (Billing Entity)                                               │
│  └── Organization/Individual who pays for the service                          │
│      └── Has subscription, invoices, tenant-wide settings                       │
│                                                                                 │
│  LEVEL 2: USER                                                                  │
│  └── Individual person within a tenant                                          │
│      └── Can belong to multiple workspaces with different roles                │
│                                                                                 │
│  LEVEL 3: WORKSPACE                                                             │
│  └── Organizational unit for content management                                 │
│      └── Contains social accounts, posts, inbox, analytics                     │
│                                                                                 │
│  LEVEL 4: RESOURCES                                                             │
│  └── Social Accounts, Posts, Media, Inbox Items, etc.                          │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              ENTITY RELATIONSHIPS                                │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌──────────────┐         ┌──────────────┐         ┌──────────────┐            │
│  │  SuperAdmin  │         │    Tenant    │────────▶│ Subscription │            │
│  │    Users     │         │              │         │              │            │
│  └──────────────┘         └──────┬───────┘         └──────────────┘            │
│                                  │                                              │
│                                  │ 1:N                                          │
│                                  ▼                                              │
│                           ┌──────────────┐                                      │
│                           │     User     │                                      │
│                           │              │                                      │
│                           └──────┬───────┘                                      │
│                                  │                                              │
│                                  │ N:M (via WorkspaceMembership)                │
│                                  ▼                                              │
│                           ┌──────────────┐                                      │
│                           │  Workspace   │                                      │
│                           │              │                                      │
│                           └──────┬───────┘                                      │
│                                  │                                              │
│           ┌──────────────────────┼──────────────────────┐                       │
│           │                      │                      │                       │
│           ▼                      ▼                      ▼                       │
│    ┌─────────────┐       ┌─────────────┐        ┌─────────────┐                │
│    │   Social    │       │    Post     │        │   Inbox     │                │
│    │  Accounts   │       │             │        │   Items     │                │
│    └─────────────┘       └─────────────┘        └─────────────┘                │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Domain: Platform Administration

### 3.1 SuperAdminUser

Platform administrators (Bizinso team members).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Login email |
| password | VARCHAR(255) | NOT NULL | Bcrypt hashed |
| name | VARCHAR(100) | NOT NULL | Full name |
| role | ENUM | NOT NULL | SUPER_ADMIN, ADMIN, SUPPORT, VIEWER |
| status | ENUM | NOT NULL | ACTIVE, INACTIVE, SUSPENDED |
| last_login_at | TIMESTAMP | NULL | Last login timestamp |
| mfa_enabled | BOOLEAN | DEFAULT false | Two-factor enabled |
| mfa_secret | VARCHAR(255) | NULL | Encrypted MFA secret |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**SuperAdmin Roles:**
- `SUPER_ADMIN`: Full platform access
- `ADMIN`: Tenant management, no billing access
- `SUPPORT`: View tenants, handle tickets
- `VIEWER`: Read-only access

### 3.2 PlatformConfig

Global platform configuration settings.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| key | VARCHAR(100) | UNIQUE, NOT NULL | Configuration key |
| value | JSON | NOT NULL | Configuration value |
| category | VARCHAR(50) | NOT NULL | Category grouping |
| description | TEXT | NULL | Human-readable description |
| is_sensitive | BOOLEAN | DEFAULT false | Should be encrypted |
| updated_by | UUID | FK → SuperAdminUser | Last modifier |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Configuration Categories:**
- `general`: Platform name, URLs, etc.
- `features`: Global feature toggles
- `integrations`: Third-party API keys
- `limits`: Default limits
- `notifications`: Email/SMS settings
- `security`: Security policies

### 3.3 FeatureFlag

Feature flags for gradual rollout and plan-based features.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| key | VARCHAR(100) | UNIQUE, NOT NULL | Feature flag key |
| name | VARCHAR(255) | NOT NULL | Human-readable name |
| description | TEXT | NULL | Feature description |
| is_enabled | BOOLEAN | DEFAULT false | Global enable/disable |
| rollout_percentage | INTEGER | DEFAULT 0 | 0-100 percentage rollout |
| allowed_plans | JSON | NULL | Array of plan IDs |
| allowed_tenants | JSON | NULL | Array of tenant IDs |
| metadata | JSON | NULL | Additional configuration |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 3.4 PlanDefinition

Subscription plan definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| code | VARCHAR(50) | UNIQUE, NOT NULL | Plan code (FREE, STARTER, etc.) |
| name | VARCHAR(100) | NOT NULL | Display name |
| description | TEXT | NULL | Plan description |
| price_inr_monthly | DECIMAL(10,2) | NOT NULL | Monthly price in INR |
| price_inr_yearly | DECIMAL(10,2) | NOT NULL | Yearly price in INR |
| price_usd_monthly | DECIMAL(10,2) | NOT NULL | Monthly price in USD |
| price_usd_yearly | DECIMAL(10,2) | NOT NULL | Yearly price in USD |
| trial_days | INTEGER | DEFAULT 0 | Trial period in days |
| is_active | BOOLEAN | DEFAULT true | Available for new subscriptions |
| is_public | BOOLEAN | DEFAULT true | Visible on pricing page |
| sort_order | INTEGER | DEFAULT 0 | Display order |
| features | JSON | NOT NULL | Feature list for display |
| metadata | JSON | NULL | Additional metadata |
| razorpay_plan_id_inr | VARCHAR(100) | NULL | Razorpay plan ID for INR |
| razorpay_plan_id_usd | VARCHAR(100) | NULL | Razorpay plan ID for USD |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 3.5 PlanLimit

Limits associated with each plan.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| plan_id | UUID | FK → PlanDefinition | Associated plan |
| limit_key | VARCHAR(100) | NOT NULL | Limit identifier |
| limit_value | INTEGER | NOT NULL | Limit value (-1 = unlimited) |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Limit Keys:**
- `max_workspaces`: Maximum workspaces per tenant
- `max_users`: Maximum users per tenant
- `max_social_accounts`: Maximum connected accounts
- `max_posts_per_month`: Monthly post limit
- `max_scheduled_posts`: Concurrent scheduled posts
- `max_team_members_per_workspace`: Per workspace limit
- `max_storage_gb`: Storage limit in GB
- `max_api_calls_per_day`: API rate limit
- `ai_requests_per_month`: AI feature usage

**Unique Constraint:** (plan_id, limit_key)

---

## 4. Domain: Tenant Management

### 4.1 Tenant

Top-level billing entity - the customer organization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| name | VARCHAR(255) | NOT NULL | Organization/Individual name |
| slug | VARCHAR(100) | UNIQUE, NOT NULL | URL-friendly identifier |
| type | ENUM | NOT NULL | B2B_ENTERPRISE, B2B_SMB, B2C_BRAND, INDIVIDUAL, INFLUENCER, NON_PROFIT |
| status | ENUM | NOT NULL | PENDING, ACTIVE, SUSPENDED, TERMINATED |
| owner_user_id | UUID | FK → User | Primary owner |
| plan_id | UUID | FK → PlanDefinition | Current plan |
| trial_ends_at | TIMESTAMP | NULL | Trial expiration |
| settings | JSON | NOT NULL | Tenant-wide settings |
| metadata | JSON | NULL | Additional metadata |
| onboarding_completed_at | TIMESTAMP | NULL | Onboarding completion |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Tenant Types:**
```
B2B_ENTERPRISE    - Large agencies, enterprises (50+ employees)
B2B_SMB           - Small-medium businesses (1-50 employees)
B2C_BRAND         - E-commerce, retail, D2C brands
INDIVIDUAL        - Freelancers, solopreneurs
INFLUENCER        - Content creators, KOLs
NON_PROFIT        - NGOs, educational institutions
```

**Tenant Status Flow:**
```
PENDING → ACTIVE → SUSPENDED → TERMINATED
                ↑            │
                └────────────┘ (can reactivate)
```

**Settings JSON Schema:**
```json
{
  "timezone": "Asia/Kolkata",
  "date_format": "DD/MM/YYYY",
  "language": "en",
  "notifications": {
    "email_digest": "daily",
    "marketing_emails": true
  },
  "branding": {
    "logo_url": null,
    "primary_color": "#000000"
  },
  "security": {
    "require_mfa": false,
    "session_timeout_hours": 24
  }
}
```

### 4.2 TenantProfile

Detailed business profile information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant, UNIQUE | Associated tenant |
| legal_name | VARCHAR(255) | NULL | Legal entity name |
| business_type | VARCHAR(100) | NULL | Specific business type |
| industry | VARCHAR(100) | NULL | Industry vertical |
| company_size | ENUM | NULL | SOLO, SMALL, MEDIUM, LARGE, ENTERPRISE |
| website | VARCHAR(255) | NULL | Company website |
| address_line1 | VARCHAR(255) | NULL | Address line 1 |
| address_line2 | VARCHAR(255) | NULL | Address line 2 |
| city | VARCHAR(100) | NULL | City |
| state | VARCHAR(100) | NULL | State/Province |
| country | VARCHAR(2) | NOT NULL | ISO country code |
| postal_code | VARCHAR(20) | NULL | Postal/ZIP code |
| phone | VARCHAR(20) | NULL | Primary phone |
| gstin | VARCHAR(15) | NULL | GST Number (India) |
| pan | VARCHAR(10) | NULL | PAN (India) |
| tax_id | VARCHAR(50) | NULL | Tax ID (International) |
| verification_status | ENUM | DEFAULT 'PENDING' | PENDING, VERIFIED, FAILED |
| verified_at | TIMESTAMP | NULL | Verification timestamp |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Company Size Enum:**
```
SOLO        - 1 person
SMALL       - 2-10 employees
MEDIUM      - 11-50 employees
LARGE       - 51-200 employees
ENTERPRISE  - 200+ employees
```

### 4.3 TenantOnboarding

Tracks onboarding progress for each tenant.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant, UNIQUE | Associated tenant |
| current_step | VARCHAR(50) | NOT NULL | Current onboarding step |
| steps_completed | JSON | NOT NULL | Array of completed steps |
| started_at | TIMESTAMP | NOT NULL | Onboarding start |
| completed_at | TIMESTAMP | NULL | Onboarding completion |
| abandoned_at | TIMESTAMP | NULL | If abandoned |
| metadata | JSON | NULL | Step-specific data |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Onboarding Steps:**
```json
[
  "account_created",
  "email_verified",
  "business_type_selected",
  "profile_completed",
  "plan_selected",
  "payment_completed",
  "first_workspace_created",
  "first_social_account_connected",
  "first_post_created",
  "tour_completed"
]
```

### 4.4 TenantUsage

Tracks usage metrics for billing and limits.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| period_start | DATE | NOT NULL | Usage period start |
| period_end | DATE | NOT NULL | Usage period end |
| metric_key | VARCHAR(100) | NOT NULL | Usage metric key |
| metric_value | BIGINT | NOT NULL | Usage value |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Unique Constraint:** (tenant_id, period_start, metric_key)

**Metric Keys:**
- `workspaces_count`
- `users_count`
- `social_accounts_count`
- `posts_published`
- `posts_scheduled`
- `storage_bytes_used`
- `api_calls`
- `ai_requests`

---

## 5. Domain: Subscription & Billing

### 5.1 Subscription

Tenant subscription records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| plan_id | UUID | FK → PlanDefinition | Subscribed plan |
| status | ENUM | NOT NULL | CREATED, AUTHENTICATED, ACTIVE, PENDING, HALTED, CANCELLED, COMPLETED, EXPIRED |
| billing_cycle | ENUM | NOT NULL | MONTHLY, YEARLY |
| currency | VARCHAR(3) | NOT NULL | INR, USD |
| amount | DECIMAL(10,2) | NOT NULL | Subscription amount |
| razorpay_subscription_id | VARCHAR(100) | UNIQUE | Razorpay subscription ID |
| razorpay_customer_id | VARCHAR(100) | NULL | Razorpay customer ID |
| current_period_start | TIMESTAMP | NULL | Current billing period start |
| current_period_end | TIMESTAMP | NULL | Current billing period end |
| trial_start | TIMESTAMP | NULL | Trial start date |
| trial_end | TIMESTAMP | NULL | Trial end date |
| cancelled_at | TIMESTAMP | NULL | Cancellation timestamp |
| cancel_at_period_end | BOOLEAN | DEFAULT false | Cancel at period end |
| ended_at | TIMESTAMP | NULL | Subscription end timestamp |
| metadata | JSON | NULL | Additional metadata |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Subscription Status:**
```
CREATED         - Subscription created, pending payment
AUTHENTICATED   - Payment method authenticated
ACTIVE          - Active and paid
PENDING         - Payment pending/retry
HALTED          - Payment failed, in grace period
CANCELLED       - Cancelled by user
COMPLETED       - Completed (fixed term)
EXPIRED         - Subscription expired
```

### 5.2 Invoice

Invoice records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| subscription_id | UUID | FK → Subscription | Associated subscription |
| invoice_number | VARCHAR(50) | UNIQUE, NOT NULL | Invoice number |
| razorpay_invoice_id | VARCHAR(100) | UNIQUE | Razorpay invoice ID |
| status | ENUM | NOT NULL | DRAFT, ISSUED, PAID, CANCELLED, EXPIRED |
| currency | VARCHAR(3) | NOT NULL | Invoice currency |
| subtotal | DECIMAL(10,2) | NOT NULL | Amount before tax |
| tax_amount | DECIMAL(10,2) | NOT NULL | Tax amount |
| total | DECIMAL(10,2) | NOT NULL | Total amount |
| amount_paid | DECIMAL(10,2) | DEFAULT 0 | Amount paid |
| amount_due | DECIMAL(10,2) | NOT NULL | Amount remaining |
| gst_details | JSON | NULL | GST breakdown (CGST, SGST, IGST) |
| billing_address | JSON | NOT NULL | Billing address snapshot |
| line_items | JSON | NOT NULL | Invoice line items |
| issued_at | TIMESTAMP | NULL | Issue date |
| due_at | TIMESTAMP | NULL | Due date |
| paid_at | TIMESTAMP | NULL | Payment date |
| pdf_url | VARCHAR(500) | NULL | PDF download URL |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Line Items JSON Schema:**
```json
[
  {
    "description": "Professional Plan - Monthly",
    "hsn_sac": "998314",
    "quantity": 1,
    "unit_price": 2499.00,
    "amount": 2499.00
  }
]
```

**GST Details JSON Schema:**
```json
{
  "gstin": "27AABCU9603R1ZM",
  "place_of_supply": "Maharashtra",
  "cgst": 224.91,
  "sgst": 224.91,
  "igst": 0,
  "total_gst": 449.82
}
```

### 5.3 Payment

Payment transaction records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| subscription_id | UUID | FK → Subscription | Associated subscription |
| invoice_id | UUID | FK → Invoice | Associated invoice |
| razorpay_payment_id | VARCHAR(100) | UNIQUE | Razorpay payment ID |
| razorpay_order_id | VARCHAR(100) | NULL | Razorpay order ID |
| status | ENUM | NOT NULL | CREATED, AUTHORIZED, CAPTURED, FAILED, REFUNDED |
| amount | DECIMAL(10,2) | NOT NULL | Payment amount |
| currency | VARCHAR(3) | NOT NULL | Payment currency |
| method | VARCHAR(50) | NULL | Payment method (card, upi, netbanking, etc.) |
| method_details | JSON | NULL | Payment method details |
| fee | DECIMAL(10,2) | NULL | Transaction fee |
| tax_on_fee | DECIMAL(10,2) | NULL | GST on fee |
| error_code | VARCHAR(100) | NULL | Error code if failed |
| error_description | TEXT | NULL | Error description |
| captured_at | TIMESTAMP | NULL | Capture timestamp |
| refunded_at | TIMESTAMP | NULL | Refund timestamp |
| refund_amount | DECIMAL(10,2) | NULL | Refunded amount |
| metadata | JSON | NULL | Additional metadata |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 5.4 PaymentMethod

Saved payment methods for tenants.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| razorpay_token_id | VARCHAR(100) | NULL | Razorpay token ID |
| type | ENUM | NOT NULL | CARD, UPI, NETBANKING, WALLET, EMANDATE |
| is_default | BOOLEAN | DEFAULT false | Default payment method |
| details | JSON | NOT NULL | Method details (masked) |
| expires_at | TIMESTAMP | NULL | Expiration (for cards) |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Details JSON (Card):**
```json
{
  "last4": "4242",
  "brand": "Visa",
  "exp_month": 12,
  "exp_year": 2027,
  "name": "John Doe"
}
```

---

## 6. Domain: User Management

### 6.1 User

Individual users within tenants.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| email | VARCHAR(255) | NOT NULL | User email |
| password | VARCHAR(255) | NULL | Bcrypt hashed (NULL for SSO) |
| name | VARCHAR(100) | NOT NULL | Full name |
| avatar_url | VARCHAR(500) | NULL | Profile picture URL |
| phone | VARCHAR(20) | NULL | Phone number |
| timezone | VARCHAR(50) | NULL | User timezone (overrides tenant) |
| language | VARCHAR(10) | DEFAULT 'en' | Preferred language |
| status | ENUM | NOT NULL | PENDING, ACTIVE, SUSPENDED, DEACTIVATED |
| role_in_tenant | ENUM | NOT NULL | OWNER, ADMIN, MEMBER |
| email_verified_at | TIMESTAMP | NULL | Email verification timestamp |
| last_login_at | TIMESTAMP | NULL | Last login |
| last_active_at | TIMESTAMP | NULL | Last activity |
| mfa_enabled | BOOLEAN | DEFAULT false | MFA enabled |
| mfa_secret | VARCHAR(255) | NULL | Encrypted MFA secret |
| settings | JSON | NOT NULL | User preferences |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Unique Constraint:** (tenant_id, email)

**Role in Tenant:**
```
OWNER   - Tenant owner (billing, can delete tenant)
ADMIN   - Full tenant admin (cannot delete tenant)
MEMBER  - Regular team member
```

**User Status:**
```
PENDING      - Invited, not yet accepted
ACTIVE       - Active user
SUSPENDED    - Temporarily suspended
DEACTIVATED  - Permanently deactivated
```

### 6.2 UserSession

Active user sessions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| user_id | UUID | FK → User | Associated user |
| token_hash | VARCHAR(255) | NOT NULL | Hashed session token |
| ip_address | VARCHAR(45) | NULL | Client IP |
| user_agent | TEXT | NULL | Browser/client info |
| device_type | ENUM | NULL | DESKTOP, MOBILE, TABLET, API |
| location | JSON | NULL | Geo-location data |
| last_active_at | TIMESTAMP | NOT NULL | Last activity |
| expires_at | TIMESTAMP | NOT NULL | Session expiration |
| created_at | TIMESTAMP | NOT NULL | Record creation |

### 6.3 UserInvitation

Pending user invitations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Target tenant |
| email | VARCHAR(255) | NOT NULL | Invitee email |
| role_in_tenant | ENUM | NOT NULL | Role to assign |
| workspace_memberships | JSON | NULL | Workspace roles to assign |
| invited_by | UUID | FK → User | Inviter |
| token | VARCHAR(100) | UNIQUE, NOT NULL | Invitation token |
| status | ENUM | NOT NULL | PENDING, ACCEPTED, EXPIRED, REVOKED |
| expires_at | TIMESTAMP | NOT NULL | Expiration |
| accepted_at | TIMESTAMP | NULL | Acceptance timestamp |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

---

## 7. Domain: Workspace

### 7.1 Workspace

Organizational units within tenants.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Associated tenant |
| name | VARCHAR(100) | NOT NULL | Workspace name |
| slug | VARCHAR(100) | NOT NULL | URL-friendly identifier |
| description | TEXT | NULL | Workspace description |
| status | ENUM | NOT NULL | ACTIVE, SUSPENDED, ARCHIVED |
| timezone | VARCHAR(50) | NOT NULL | Workspace timezone |
| settings | JSON | NOT NULL | Workspace settings |
| created_by | UUID | FK → User | Creator |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Unique Constraint:** (tenant_id, slug)

**Settings JSON Schema:**
```json
{
  "approval_workflow": {
    "enabled": true,
    "required_for_roles": ["EDITOR"]
  },
  "default_social_accounts": [],
  "content_categories": ["Marketing", "Product", "Culture"],
  "hashtag_groups": {},
  "branding": {
    "logo_url": null,
    "primary_color": null
  }
}
```

### 7.2 WorkspaceMembership

User membership in workspaces.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| workspace_id | UUID | FK → Workspace | Associated workspace |
| user_id | UUID | FK → User | Associated user |
| role | ENUM | NOT NULL | OWNER, ADMIN, EDITOR, VIEWER |
| permissions | JSON | NULL | Custom permissions override |
| joined_at | TIMESTAMP | NOT NULL | Join date |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Unique Constraint:** (workspace_id, user_id)

**Workspace Roles & Permissions:**
```
OWNER:
  - Full workspace access
  - Manage members
  - Manage settings
  - Delete workspace
  - Connect/disconnect social accounts
  - All content operations
  - Approve content

ADMIN:
  - Manage members (except owner)
  - Manage settings
  - Connect/disconnect social accounts
  - All content operations
  - Approve content

EDITOR:
  - Create/edit content
  - Schedule posts
  - Respond to inbox
  - View analytics
  - Submit for approval

VIEWER:
  - View content
  - View calendar
  - View analytics
  - View inbox (read-only)
```

---

## 8. Domain: Social Accounts

### 8.1 SocialPlatform

Supported social media platforms (reference data).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| code | VARCHAR(50) | UNIQUE, NOT NULL | Platform code |
| name | VARCHAR(100) | NOT NULL | Display name |
| icon_url | VARCHAR(500) | NULL | Platform icon |
| color | VARCHAR(7) | NULL | Brand color (hex) |
| is_enabled | BOOLEAN | DEFAULT true | Platform enabled |
| oauth_config | JSON | NOT NULL | OAuth configuration |
| content_limits | JSON | NOT NULL | Content restrictions |
| supported_features | JSON | NOT NULL | Available features |
| api_version | VARCHAR(20) | NULL | Current API version |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Platform Codes:**
```
LINKEDIN, FACEBOOK, INSTAGRAM, TWITTER, YOUTUBE, TIKTOK,
PINTEREST, THREADS, GOOGLE_BUSINESS, WHATSAPP, TELEGRAM,
SNAPCHAT, REDDIT, MASTODON, BLUESKY
```

**Content Limits JSON:**
```json
{
  "max_text_length": 3000,
  "max_images": 20,
  "max_image_size_mb": 5,
  "max_video_duration_seconds": 600,
  "max_video_size_mb": 200,
  "supported_image_formats": ["jpg", "png", "gif"],
  "supported_video_formats": ["mp4", "mov"],
  "aspect_ratios": {
    "feed": ["1:1", "4:5", "16:9"],
    "story": ["9:16"]
  }
}
```

### 8.2 SocialAccount

Connected social media accounts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| workspace_id | UUID | FK → Workspace | Associated workspace |
| platform_id | UUID | FK → SocialPlatform | Platform reference |
| platform_code | VARCHAR(50) | NOT NULL | Denormalized for queries |
| account_type | ENUM | NOT NULL | PERSONAL, PAGE, BUSINESS, CREATOR, CHANNEL |
| external_id | VARCHAR(255) | NOT NULL | Platform's account ID |
| external_username | VARCHAR(255) | NULL | Platform username/handle |
| display_name | VARCHAR(255) | NOT NULL | Display name |
| profile_url | VARCHAR(500) | NULL | Profile URL |
| avatar_url | VARCHAR(500) | NULL | Profile picture URL |
| access_token | TEXT | NOT NULL | Encrypted access token |
| refresh_token | TEXT | NULL | Encrypted refresh token |
| token_expires_at | TIMESTAMP | NULL | Token expiration |
| token_scopes | JSON | NULL | Granted scopes |
| status | ENUM | NOT NULL | ACTIVE, EXPIRED, REVOKED, DISCONNECTED, ERROR |
| last_sync_at | TIMESTAMP | NULL | Last data sync |
| last_error | TEXT | NULL | Last error message |
| metadata | JSON | NULL | Platform-specific metadata |
| connected_by | UUID | FK → User | User who connected |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |
| disconnected_at | TIMESTAMP | NULL | Disconnection timestamp |

**Unique Constraint:** (workspace_id, platform_code, external_id)

**Account Types:**
```
PERSONAL   - Personal profile (LinkedIn, Twitter)
PAGE       - Business page (Facebook, LinkedIn)
BUSINESS   - Business account (Instagram, WhatsApp)
CREATOR    - Creator account (Instagram, TikTok)
CHANNEL    - Channel (YouTube, Telegram)
```

**Account Status:**
```
ACTIVE       - Connected and working
EXPIRED      - Token expired, needs refresh
REVOKED      - User revoked access on platform
DISCONNECTED - Manually disconnected
ERROR        - Connection error
```

---

## 9. Domain: Content

### 9.1 Post

Social media posts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| workspace_id | UUID | FK → Workspace | Associated workspace |
| content | TEXT | NULL | Post content/caption |
| content_variations | JSON | NULL | Platform-specific content |
| status | ENUM | NOT NULL | DRAFT, SCHEDULED, PENDING_APPROVAL, APPROVED, PUBLISHING, PUBLISHED, FAILED, CANCELLED |
| post_type | ENUM | NOT NULL | STANDARD, REEL, STORY, THREAD, ARTICLE |
| scheduled_at | TIMESTAMP | NULL | Scheduled publish time |
| published_at | TIMESTAMP | NULL | Actual publish time |
| timezone | VARCHAR(50) | NOT NULL | Scheduling timezone |
| hashtags | JSON | NULL | Array of hashtags |
| mentions | JSON | NULL | Array of mentions |
| link_url | VARCHAR(500) | NULL | Link in post |
| link_preview | JSON | NULL | Link preview data |
| first_comment | TEXT | NULL | First comment content |
| created_by | UUID | FK → User | Creator |
| approved_by | UUID | FK → User | Approver |
| approved_at | TIMESTAMP | NULL | Approval timestamp |
| rejection_reason | TEXT | NULL | If rejected |
| metadata | JSON | NULL | Additional metadata |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Post Status Flow:**
```
DRAFT → SCHEDULED → PUBLISHING → PUBLISHED
                ↘           ↗
                  FAILED
      ↓
PENDING_APPROVAL → APPROVED → SCHEDULED
                ↘
                  (rejected) → DRAFT
```

### 9.2 PostTarget

Target social accounts for a post.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| post_id | UUID | FK → Post | Associated post |
| social_account_id | UUID | FK → SocialAccount | Target account |
| platform_code | VARCHAR(50) | NOT NULL | Denormalized |
| content_override | TEXT | NULL | Platform-specific content |
| status | ENUM | NOT NULL | PENDING, PUBLISHING, PUBLISHED, FAILED |
| external_post_id | VARCHAR(255) | NULL | Platform's post ID |
| external_post_url | VARCHAR(500) | NULL | Post URL on platform |
| published_at | TIMESTAMP | NULL | Publish timestamp |
| error_code | VARCHAR(100) | NULL | Error code if failed |
| error_message | TEXT | NULL | Error message |
| retry_count | INTEGER | DEFAULT 0 | Retry attempts |
| metrics | JSON | NULL | Engagement metrics |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Unique Constraint:** (post_id, social_account_id)

### 9.3 PostMedia

Media attachments for posts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| post_id | UUID | FK → Post | Associated post |
| type | ENUM | NOT NULL | IMAGE, VIDEO, GIF, DOCUMENT |
| file_name | VARCHAR(255) | NOT NULL | Original filename |
| file_size | BIGINT | NOT NULL | Size in bytes |
| mime_type | VARCHAR(100) | NOT NULL | MIME type |
| storage_path | VARCHAR(500) | NOT NULL | Storage location |
| cdn_url | VARCHAR(500) | NULL | CDN URL |
| thumbnail_url | VARCHAR(500) | NULL | Thumbnail URL |
| dimensions | JSON | NULL | Width/height |
| duration_seconds | INTEGER | NULL | Video duration |
| alt_text | VARCHAR(500) | NULL | Alt text for accessibility |
| sort_order | INTEGER | DEFAULT 0 | Display order |
| processing_status | ENUM | NOT NULL | PENDING, PROCESSING, COMPLETED, FAILED |
| metadata | JSON | NULL | Additional metadata |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

---

## 10. Domain: Inbox & Engagement

### 10.1 InboxItem

Comments, mentions, and messages from social platforms.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| workspace_id | UUID | FK → Workspace | Associated workspace |
| social_account_id | UUID | FK → SocialAccount | Source account |
| platform_code | VARCHAR(50) | NOT NULL | Platform code |
| type | ENUM | NOT NULL | COMMENT, MENTION, REPLY, MESSAGE, REVIEW |
| external_id | VARCHAR(255) | NOT NULL | Platform's item ID |
| external_post_id | VARCHAR(255) | NULL | Related post ID |
| external_post_url | VARCHAR(500) | NULL | Related post URL |
| parent_id | UUID | FK → InboxItem | Parent for threads |
| author_external_id | VARCHAR(255) | NOT NULL | Author's platform ID |
| author_name | VARCHAR(255) | NULL | Author display name |
| author_username | VARCHAR(255) | NULL | Author username |
| author_avatar_url | VARCHAR(500) | NULL | Author avatar |
| author_profile_url | VARCHAR(500) | NULL | Author profile URL |
| content | TEXT | NOT NULL | Message content |
| content_html | TEXT | NULL | HTML formatted content |
| attachments | JSON | NULL | Media attachments |
| sentiment | ENUM | NULL | POSITIVE, NEUTRAL, NEGATIVE |
| status | ENUM | NOT NULL | UNREAD, READ, REPLIED, ARCHIVED, HIDDEN, DELETED |
| is_spam | BOOLEAN | DEFAULT false | Spam flag |
| platform_created_at | TIMESTAMP | NOT NULL | When created on platform |
| read_at | TIMESTAMP | NULL | When marked read |
| assigned_to | UUID | FK → User | Assigned team member |
| metadata | JSON | NULL | Additional metadata |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Unique Constraint:** (workspace_id, platform_code, external_id)

### 10.2 InboxReply

Replies sent from the platform.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| inbox_item_id | UUID | FK → InboxItem | Original item |
| content | TEXT | NOT NULL | Reply content |
| status | ENUM | NOT NULL | PENDING, SENT, FAILED |
| external_id | VARCHAR(255) | NULL | Platform's reply ID |
| sent_at | TIMESTAMP | NULL | Send timestamp |
| error_message | TEXT | NULL | Error if failed |
| sent_by | UUID | FK → User | Sender |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

---

## 11. Domain: Knowledge Base

### 11.1 KBCategory

Knowledge base categories.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| parent_id | UUID | FK → KBCategory | Parent category |
| name | VARCHAR(100) | NOT NULL | Category name |
| slug | VARCHAR(100) | UNIQUE, NOT NULL | URL slug |
| description | TEXT | NULL | Category description |
| icon | VARCHAR(50) | NULL | Icon identifier |
| sort_order | INTEGER | DEFAULT 0 | Display order |
| is_published | BOOLEAN | DEFAULT true | Visibility |
| article_count | INTEGER | DEFAULT 0 | Cached article count |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 11.2 KBArticle

Knowledge base articles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| category_id | UUID | FK → KBCategory | Associated category |
| title | VARCHAR(255) | NOT NULL | Article title |
| slug | VARCHAR(255) | NOT NULL | URL slug |
| excerpt | TEXT | NULL | Short description |
| content | TEXT | NOT NULL | Article content (Markdown) |
| content_html | TEXT | NOT NULL | Rendered HTML |
| status | ENUM | NOT NULL | DRAFT, PUBLISHED, ARCHIVED |
| author_id | UUID | FK → SuperAdminUser | Author |
| featured_image | VARCHAR(500) | NULL | Cover image URL |
| video_url | VARCHAR(500) | NULL | Embedded video URL |
| tags | JSON | NULL | Array of tags |
| view_count | INTEGER | DEFAULT 0 | View counter |
| helpful_count | INTEGER | DEFAULT 0 | Helpful votes |
| not_helpful_count | INTEGER | DEFAULT 0 | Not helpful votes |
| applies_to_plans | JSON | NULL | Plan restrictions |
| applies_to_tenant_types | JSON | NULL | Tenant type restrictions |
| seo_title | VARCHAR(255) | NULL | SEO title |
| seo_description | TEXT | NULL | SEO description |
| published_at | TIMESTAMP | NULL | Publish date |
| last_reviewed_at | TIMESTAMP | NULL | Last review date |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

**Unique Constraint:** (category_id, slug)

### 11.3 KBArticleFeedback

Article feedback from users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| article_id | UUID | FK → KBArticle | Associated article |
| tenant_id | UUID | FK → Tenant | Feedback source |
| user_id | UUID | FK → User | User who gave feedback |
| is_helpful | BOOLEAN | NOT NULL | Was it helpful |
| feedback_text | TEXT | NULL | Additional feedback |
| created_at | TIMESTAMP | NOT NULL | Record creation |

---

## 12. Domain: Feedback & Roadmap

### 12.1 Feedback

User feedback submissions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | FK → Tenant | Source tenant |
| user_id | UUID | FK → User | Submitting user |
| type | ENUM | NOT NULL | FEATURE_REQUEST, BUG_REPORT, GENERAL, NPS |
| category | VARCHAR(100) | NULL | Feature category |
| title | VARCHAR(255) | NOT NULL | Feedback title |
| description | TEXT | NOT NULL | Detailed description |
| priority | ENUM | NULL | NICE_TO_HAVE, IMPORTANT, CRITICAL |
| status | ENUM | NOT NULL | NEW, REVIEWING, PLANNED, IN_PROGRESS, COMPLETED, DECLINED, DUPLICATE |
| vote_count | INTEGER | DEFAULT 1 | Upvote count |
| nps_score | INTEGER | NULL | NPS score (0-10) |
| attachments | JSON | NULL | Attached files |
| browser_info | JSON | NULL | Browser/device info |
| linked_roadmap_item_id | UUID | FK → RoadmapItem | Linked roadmap item |
| internal_notes | TEXT | NULL | Super admin notes |
| responded_at | TIMESTAMP | NULL | Response timestamp |
| response | TEXT | NULL | Public response |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 12.2 FeedbackVote

User votes on feedback items.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| feedback_id | UUID | FK → Feedback | Associated feedback |
| user_id | UUID | FK → User | Voting user |
| created_at | TIMESTAMP | NOT NULL | Vote timestamp |

**Unique Constraint:** (feedback_id, user_id)

### 12.3 RoadmapItem

Public roadmap items.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| title | VARCHAR(255) | NOT NULL | Feature title |
| description | TEXT | NULL | Feature description |
| category | VARCHAR(100) | NULL | Feature category |
| status | ENUM | NOT NULL | UNDER_CONSIDERATION, PLANNED, IN_PROGRESS, COMPLETED, CANCELLED |
| target_quarter | VARCHAR(10) | NULL | Q1 2026, etc. |
| visibility | ENUM | NOT NULL | PUBLIC, PLAN_SPECIFIC, INTERNAL |
| visible_to_plans | JSON | NULL | Plan restrictions |
| vote_count | INTEGER | DEFAULT 0 | Interest votes |
| released_at | TIMESTAMP | NULL | Release date |
| release_note_id | UUID | FK → ReleaseNote | Associated release |
| sort_order | INTEGER | DEFAULT 0 | Display order |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 12.4 ReleaseNote

Product release notes.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| version | VARCHAR(50) | NOT NULL | Version number |
| title | VARCHAR(255) | NOT NULL | Release title |
| content | TEXT | NOT NULL | Release notes (Markdown) |
| content_html | TEXT | NOT NULL | Rendered HTML |
| release_type | ENUM | NOT NULL | MAJOR, MINOR, PATCH, HOTFIX |
| is_published | BOOLEAN | DEFAULT false | Published status |
| published_at | TIMESTAMP | NULL | Publish date |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

---

## 13. Domain: Support

### 13.1 SupportTicket

Support tickets (L2/L3).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| ticket_number | VARCHAR(20) | UNIQUE, NOT NULL | Ticket reference |
| tenant_id | UUID | FK → Tenant | Source tenant |
| user_id | UUID | FK → User | Submitting user |
| category | VARCHAR(100) | NOT NULL | Issue category |
| subject | VARCHAR(255) | NOT NULL | Ticket subject |
| description | TEXT | NOT NULL | Issue description |
| priority | ENUM | NOT NULL | LOW, MEDIUM, HIGH, URGENT |
| status | ENUM | NOT NULL | NEW, OPEN, PENDING, RESOLVED, CLOSED |
| level | ENUM | NOT NULL | L2, L3 | Support level |
| assigned_to | UUID | FK → SuperAdminUser | Assigned agent |
| sla_due_at | TIMESTAMP | NULL | SLA deadline |
| sla_breached | BOOLEAN | DEFAULT false | SLA breached |
| first_response_at | TIMESTAMP | NULL | First response time |
| resolved_at | TIMESTAMP | NULL | Resolution time |
| closed_at | TIMESTAMP | NULL | Close time |
| satisfaction_rating | INTEGER | NULL | 1-5 rating |
| satisfaction_feedback | TEXT | NULL | Feedback text |
| metadata | JSON | NULL | Additional metadata |
| created_at | TIMESTAMP | NOT NULL | Record creation |
| updated_at | TIMESTAMP | NOT NULL | Last update |

### 13.2 TicketMessage

Messages within a ticket.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| ticket_id | UUID | FK → SupportTicket | Associated ticket |
| sender_type | ENUM | NOT NULL | USER, AGENT, SYSTEM |
| sender_id | UUID | NULL | User or SuperAdmin ID |
| content | TEXT | NOT NULL | Message content |
| attachments | JSON | NULL | Attached files |
| is_internal | BOOLEAN | DEFAULT false | Internal note |
| created_at | TIMESTAMP | NOT NULL | Record creation |

---

## 14. Domain: Audit & Security

### 14.1 AuditLog

Platform-wide audit trail.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Unique identifier |
| tenant_id | UUID | NULL | Associated tenant (NULL for platform) |
| user_id | UUID | NULL | Acting user |
| super_admin_id | UUID | NULL | Acting super admin |
| action | VARCHAR(100) | NOT NULL | Action performed |
| entity_type | VARCHAR(100) | NOT NULL | Entity type affected |
| entity_id | UUID | NULL | Entity ID |
| old_values | JSON | NULL | Before state |
| new_values | JSON | NULL | After state |
| ip_address | VARCHAR(45) | NULL | Client IP |
| user_agent | TEXT | NULL | Client info |
| context | JSON | NULL | Additional context |
| created_at | TIMESTAMP | NOT NULL | Record creation |

**Index:** (tenant_id, created_at), (entity_type, entity_id)

**Action Examples:**
```
user.login, user.logout, user.password_changed
tenant.created, tenant.suspended, tenant.plan_changed
workspace.created, workspace.deleted
social_account.connected, social_account.disconnected
post.created, post.published, post.deleted
subscription.created, subscription.cancelled
payment.success, payment.failed
```

---

## 15. Indexes Strategy

### 15.1 Critical Indexes

```sql
-- Tenant isolation (on every tenant-scoped table)
CREATE INDEX idx_[table]_tenant_id ON [table](tenant_id);

-- Workspace isolation
CREATE INDEX idx_[table]_workspace_id ON [table](workspace_id);

-- User lookups
CREATE UNIQUE INDEX idx_users_tenant_email ON users(tenant_id, email);

-- Post scheduling
CREATE INDEX idx_posts_scheduled ON posts(status, scheduled_at)
  WHERE status = 'SCHEDULED';

-- Inbox management
CREATE INDEX idx_inbox_workspace_status ON inbox_items(workspace_id, status, created_at);

-- Subscription billing
CREATE INDEX idx_subscriptions_renewal ON subscriptions(status, current_period_end);

-- Audit log queries
CREATE INDEX idx_audit_tenant_date ON audit_logs(tenant_id, created_at);
```

---

## 16. Data Retention Policies

| Data Type | Retention Period | Action |
|-----------|-----------------|--------|
| Active tenant data | Indefinite | Keep |
| Deleted tenant data | 30 days | Hard delete |
| Audit logs | 2 years | Archive then delete |
| Session data | 30 days | Delete |
| Analytics raw data | 1 year | Aggregate then delete |
| Support tickets | 3 years | Archive |
| Feedback | Indefinite | Keep |
| Media files | 90 days after post deletion | Delete |

---

**Document Version:** 2.0
**Last Updated:** February 2026
**Status:** Draft - Pending Review
