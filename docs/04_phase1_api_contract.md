# BizSocials — Phase-1 API Contract

**Version:** 1.1
**Status:** Draft for Review
**Date:** February 2026
**Source of Truth:** Phase-1 Product Constitution v1.0, Phase-1 Data Model v1.1

---

## 1. Overview

This document defines the complete REST API contract for BizSocials Phase-1. All endpoints follow RESTful conventions and are designed for the Vue 3 frontend to consume.

**Base URL Pattern:** `https://api.bizsocials.com/v1`

---

## 2. API Design Principles

| Principle | Implementation |
|-----------|----------------|
| RESTful | Resource-oriented URLs, standard HTTP methods |
| JSON | All request/response bodies are JSON |
| UTC | All timestamps in ISO 8601 format, UTC timezone |
| UUIDs | All resource IDs are UUIDs |
| Consistent errors | Standard error response format |
| Stateless | No server-side session; JWT for auth |

---

## 3. Authentication Model

### 3.1 Strategy
- **Type:** JWT (JSON Web Tokens)
- **Access Token:** Short-lived (15 minutes), used for API requests
- **Refresh Token:** Long-lived (7 days), used to obtain new access tokens
- **Storage:** Access token in memory; refresh token in HTTP-only cookie

### 3.2 Token Structure

**Access Token Claims:**
```
{
  "sub": "{user_id}",
  "email": "{user_email}",
  "iat": {issued_at_timestamp},
  "exp": {expiry_timestamp}
}
```

**Phase-1 Constraints:**
- No SSO/SAML
- No 2FA
- No social login
- Single device session (refresh token invalidates on new login)

### 3.3 Authentication Header
```
Authorization: Bearer {access_token}
```

---

## 4. Authorization Model

### 4.1 Role-Based Access Control

All workspace-scoped endpoints require:
1. Valid authentication (JWT)
2. Active workspace membership
3. Sufficient role permissions

### 4.2 Permission Matrix Reference

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

### 4.3 Workspace Context

Most endpoints are workspace-scoped. The workspace is identified via URL path:
```
/v1/workspaces/{workspace_id}/posts
/v1/workspaces/{workspace_id}/inbox
```

### 4.4 Workspace Authorization Rule

**Critical behavior for workspace-scoped endpoints:**

| Condition | Response |
|-----------|----------|
| User authenticated, workspace exists, user IS a member | Proceed (check role permissions) |
| User authenticated, workspace exists, user is NOT a member | `403 FORBIDDEN` |
| User authenticated, workspace does NOT exist | `404 NOT_FOUND` |
| User not authenticated | `401 UNAUTHENTICATED` |

**Rationale:** Returning 403 (not 404) when a user isn't a member prevents information leakage while providing clear feedback. The frontend can distinguish "you don't have access" from "this doesn't exist."

---

## 5. Pagination Strategy

### 5.1 Approach: Offset-Based Pagination

**Phase-1 Constraint:** Use simple offset pagination. Cursor-based pagination deferred.

### 5.2 Request Parameters
| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| `page` | Integer | 1 | — | Page number (1-indexed) |
| `per_page` | Integer | 20 | 100 | Items per page |

### 5.3 Response Envelope
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total_items": 156,
    "total_pages": 8
  }
}
```

---

## 6. Standard Response Formats

### 6.1 Success Response (Single Resource)
```json
{
  "data": {
    "id": "uuid",
    "type": "post",
    "attributes": { ... }
  }
}
```

### 6.2 Success Response (Collection)
```json
{
  "data": [
    { "id": "uuid", "type": "post", "attributes": { ... } }
  ],
  "meta": { ... }
}
```

### 6.3 Error Response
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Human-readable message",
    "details": [
      { "field": "email", "message": "Email is required" }
    ]
  }
}
```

### 6.4 Standard Error Codes
| HTTP Status | Code | Description |
|:-----------:|------|-------------|
| 400 | VALIDATION_ERROR | Request validation failed |
| 400 | INVALID_STATE | Action not allowed in current state |
| 401 | UNAUTHENTICATED | Missing or invalid token |
| 403 | FORBIDDEN | Insufficient permissions |
| 404 | NOT_FOUND | Resource does not exist |
| 409 | CONFLICT | Resource already exists |
| 422 | UNPROCESSABLE | Business rule violation |
| 429 | RATE_LIMITED | Too many requests |
| 500 | INTERNAL_ERROR | Server error |

---

## 7. Idempotency Rules

### 7.1 Idempotent by Design
| Method | Idempotent | Notes |
|--------|:----------:|-------|
| GET | Yes | Always safe |
| PUT | Yes | Full replacement |
| DELETE | Yes | Deleting twice = same result |
| POST | No | Creates new resource |
| PATCH | No | Partial update |

### 7.2 Idempotency Key (Optional)
For critical POST operations, clients may send:
```
Idempotency-Key: {client-generated-uuid}
```

**Supported on:**
- `POST /workspaces` (workspace creation)
- `POST /workspaces/{id}/posts` (post creation)
- `POST /workspaces/{id}/invitations` (invitation sending)

**Behavior:** If same key sent within 24 hours, return cached response.

---

## 8. Async/Background Operations

The following operations are processed asynchronously:

| Operation | Trigger | Background Process |
|-----------|---------|-------------------|
| Post publishing | `POST .../posts/{id}/publish` | Queue job to call platform APIs |
| Scheduled post publishing | System clock | Scheduler picks up due posts |
| Inbox sync | Periodic (every 15 min) | Fetch comments/mentions from platforms |
| Metrics sync | Periodic (every 6 hours) | Fetch post metrics from platforms |
| Report generation | `POST .../reports` | Generate PDF/CSV in background |
| Email notifications | Event-driven | Queue email delivery |

**Phase-1 Constraint:** No real-time status updates (polling only). No WebSocket support.

---

## 9. API Endpoints by Domain

---

### DOMAIN 1: Identity & Access

#### 9.1.1 Register User
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/register` |
| **Purpose** | Create a new user account |
| **Auth Required** | No |
| **Roles** | N/A |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| email | string | Yes | Valid email, unique |
| password | string | Yes | Min 8 chars, 1 uppercase, 1 number |
| full_name | string | Yes | Min 2 chars |
| timezone | string | Yes | Valid IANA timezone |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | User ID |
| email | string | User email |
| full_name | string | Display name |
| status | string | "PENDING_VERIFICATION" |
| created_at | timestamp | Account creation time |

**Errors:**
| Code | Condition |
|------|-----------|
| VALIDATION_ERROR | Invalid input |
| CONFLICT | Email already registered |

---

#### 9.1.2 Verify Email
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/verify-email` |
| **Purpose** | Verify user email with token |
| **Auth Required** | No |
| **Roles** | N/A |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| token | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Email verified successfully" |

**Errors:**
| Code | Condition |
|------|-----------|
| VALIDATION_ERROR | Token missing |
| NOT_FOUND | Invalid or expired token |

---

#### 9.1.3 Resend Verification Email
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/resend-verification` |
| **Purpose** | Resend email verification token |
| **Auth Required** | No |
| **Roles** | N/A |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| email | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Verification email sent" |

**Errors:**
| Code | Condition |
|------|-----------|
| NOT_FOUND | Email not registered |
| INVALID_STATE | Email already verified |

---

#### 9.1.4 Login
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/login` |
| **Purpose** | Authenticate user and get tokens |
| **Auth Required** | No |
| **Roles** | N/A |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| email | string | Yes |
| password | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| access_token | string | JWT access token |
| token_type | string | "Bearer" |
| expires_in | integer | Seconds until expiry |
| user.id | uuid | User ID |
| user.email | string | User email |
| user.full_name | string | Display name |
| user.status | string | User status |

**Note:** Refresh token set as HTTP-only cookie.

**Errors:**
| Code | Condition |
|------|-----------|
| UNAUTHENTICATED | Invalid credentials |
| FORBIDDEN | Account suspended or not verified |

---

#### 9.1.5 Refresh Token
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/refresh` |
| **Purpose** | Get new access token using refresh token |
| **Auth Required** | No (uses cookie) |
| **Roles** | N/A |

**Request Payload:** None (refresh token from cookie)

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| access_token | string | New JWT access token |
| token_type | string | "Bearer" |
| expires_in | integer | Seconds until expiry |

**Errors:**
| Code | Condition |
|------|-----------|
| UNAUTHENTICATED | Invalid or expired refresh token |

---

#### 9.1.6 Logout
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/logout` |
| **Purpose** | Invalidate refresh token |
| **Auth Required** | Yes |
| **Roles** | Any |

**Request Payload:** None

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Logged out successfully" |

---

#### 9.1.7 Request Password Reset
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/forgot-password` |
| **Purpose** | Send password reset email |
| **Auth Required** | No |
| **Roles** | N/A |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| email | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Reset email sent if account exists" |

**Note:** Always returns 200 to prevent email enumeration.

---

#### 9.1.8 Reset Password
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/auth/reset-password` |
| **Purpose** | Set new password using reset token |
| **Auth Required** | No |
| **Roles** | N/A |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| token | string | Yes | Valid reset token |
| password | string | Yes | Min 8 chars, 1 uppercase, 1 number |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Password reset successfully" |

**Errors:**
| Code | Condition |
|------|-----------|
| NOT_FOUND | Invalid or expired token |
| VALIDATION_ERROR | Password doesn't meet requirements |

---

#### 9.1.9 Get Current User
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/users/me` |
| **Purpose** | Get authenticated user's profile |
| **Auth Required** | Yes |
| **Roles** | Any |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | User ID |
| email | string | User email |
| full_name | string | Display name |
| status | string | User status |
| timezone | string | IANA timezone |
| email_verified_at | timestamp | Verification time |
| created_at | timestamp | Account creation |
| last_login_at | timestamp | Last login |

---

#### 9.1.10 Update Current User
| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/v1/users/me` |
| **Purpose** | Update authenticated user's profile |
| **Auth Required** | Yes |
| **Roles** | Any |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| full_name | string | No |
| timezone | string | No |

**Response Payload (200 OK):** Same as Get Current User

**Errors:**
| Code | Condition |
|------|-----------|
| VALIDATION_ERROR | Invalid timezone |

---

#### 9.1.11 Change Password
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/users/me/change-password` |
| **Purpose** | Change password (when logged in) |
| **Auth Required** | Yes |
| **Roles** | Any |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| current_password | string | Yes |
| new_password | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Password changed successfully" |

**Errors:**
| Code | Condition |
|------|-----------|
| UNAUTHENTICATED | Current password incorrect |
| VALIDATION_ERROR | New password doesn't meet requirements |

---

### DOMAIN 2: Workspace Management

#### 9.2.1 List User's Workspaces
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces` |
| **Purpose** | List all workspaces user belongs to |
| **Auth Required** | Yes |
| **Roles** | Any |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| page | integer | 1 |
| per_page | integer | 20 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Workspace ID |
| data[].name | string | Workspace name |
| data[].slug | string | URL slug |
| data[].status | string | Workspace status |
| data[].my_role | string | User's role in this workspace |
| data[].member_count | integer | Total members |
| data[].created_at | timestamp | Creation time |
| meta | object | Pagination metadata |

---

#### 9.2.2 Create Workspace
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces` |
| **Purpose** | Create new workspace (creator becomes Owner) |
| **Auth Required** | Yes |
| **Roles** | Any authenticated user |
| **Idempotency** | Supported |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| name | string | Yes | Min 2 chars, max 100 |
| slug | string | No | Auto-generated if not provided; unique |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Workspace ID |
| name | string | Workspace name |
| slug | string | URL slug |
| status | string | "ACTIVE" |
| settings | object | Default settings |
| created_at | timestamp | Creation time |
| subscription.status | string | "TRIALING" |
| subscription.trial_ends_at | timestamp | Trial expiry |

**Errors:**
| Code | Condition |
|------|-----------|
| CONFLICT | Slug already taken |
| VALIDATION_ERROR | Invalid input |

**Note:** Creates workspace with free trial subscription automatically.

---

#### 9.2.3 Get Workspace
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}` |
| **Purpose** | Get workspace details |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Workspace ID |
| name | string | Workspace name |
| slug | string | URL slug |
| status | string | Workspace status |
| settings | object | Workspace settings |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update |
| member_count | integer | Total members |
| social_account_count | integer | Connected accounts |
| my_role | string | Current user's role |

**Errors:**
| Code | Condition |
|------|-----------|
| NOT_FOUND | Workspace doesn't exist |
| FORBIDDEN | User not a member |

---

#### 9.2.4 Update Workspace
| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/v1/workspaces/{workspace_id}` |
| **Purpose** | Update workspace settings |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| name | string | No |
| settings.default_timezone | string | No |

**Response Payload (200 OK):** Same as Get Workspace

**Errors:**
| Code | Condition |
|------|-----------|
| FORBIDDEN | Insufficient permissions |
| VALIDATION_ERROR | Invalid input |

---

#### 9.2.5 Delete Workspace
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}` |
| **Purpose** | Soft-delete workspace (30-day retention) |
| **Auth Required** | Yes |
| **Roles** | Owner only |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Workspace scheduled for deletion" |
| deleted_at | timestamp | Deletion timestamp |
| permanent_deletion_at | timestamp | When data will be purged |

**Errors:**
| Code | Condition |
|------|-----------|
| FORBIDDEN | Not workspace owner |

---

#### 9.2.6 List Workspace Members
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/members` |
| **Purpose** | List all members of a workspace |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| page | integer | 1 |
| per_page | integer | 20 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Membership ID |
| data[].user.id | uuid | User ID |
| data[].user.email | string | User email |
| data[].user.full_name | string | Display name |
| data[].role | string | Member's role |
| data[].joined_at | timestamp | When joined |
| meta | object | Pagination metadata |

---

#### 9.2.7 Update Member Role
| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/v1/workspaces/{workspace_id}/members/{membership_id}` |
| **Purpose** | Change a member's role |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| role | string | Yes | OWNER, ADMIN, EDITOR, VIEWER |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Membership ID |
| user.id | uuid | User ID |
| role | string | New role |
| updated_at | timestamp | Update time |

**Errors:**
| Code | Condition |
|------|-----------|
| FORBIDDEN | Cannot change own role; Admin cannot assign Owner |
| UNPROCESSABLE | Cannot remove last Owner |

---

#### 9.2.8 Remove Member
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/members/{membership_id}` |
| **Purpose** | Remove a member from workspace |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Member removed successfully" |

**Errors:**
| Code | Condition |
|------|-----------|
| FORBIDDEN | Cannot remove self; Admin cannot remove Owner |
| UNPROCESSABLE | Cannot remove last Owner |

---

#### 9.2.9 Leave Workspace
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/leave` |
| **Purpose** | Current user leaves workspace |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Left workspace successfully" |

**Errors:**
| Code | Condition |
|------|-----------|
| UNPROCESSABLE | Owner must transfer ownership first |

---

#### 9.2.10 Create Invitation
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/invitations` |
| **Purpose** | Invite user to workspace |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |
| **Idempotency** | Supported |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| email | string | Yes | Valid email |
| role | string | Yes | ADMIN, EDITOR, VIEWER (not OWNER) |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Invitation ID |
| email | string | Invitee email |
| role | string | Assigned role |
| status | string | "PENDING" |
| expires_at | timestamp | Expiry time |
| invited_by.id | uuid | Inviter user ID |
| invited_by.full_name | string | Inviter name |
| created_at | timestamp | Invitation time |

**Errors:**
| Code | Condition |
|------|-----------|
| CONFLICT | User already a member |
| CONFLICT | Pending invitation exists for email |
| UNPROCESSABLE | Seat limit reached |

---

#### 9.2.11 List Invitations
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/invitations` |
| **Purpose** | List pending invitations |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| status | string | "PENDING" |
| page | integer | 1 |
| per_page | integer | 20 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Invitation ID |
| data[].email | string | Invitee email |
| data[].role | string | Assigned role |
| data[].status | string | Invitation status |
| data[].expires_at | timestamp | Expiry time |
| data[].created_at | timestamp | Sent time |
| meta | object | Pagination metadata |

---

#### 9.2.12 Revoke Invitation
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/invitations/{invitation_id}` |
| **Purpose** | Cancel pending invitation |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Invitation revoked" |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Invitation already accepted/expired |

---

#### 9.2.13 Accept Invitation
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/invitations/{token}/accept` |
| **Purpose** | Accept workspace invitation |
| **Auth Required** | Yes |
| **Roles** | N/A (token-based) |

**Note:** User must be logged in with the email that received the invitation.

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| workspace.id | uuid | Workspace ID |
| workspace.name | string | Workspace name |
| role | string | Assigned role |
| message | string | "Invitation accepted" |

**Errors:**
| Code | Condition |
|------|-----------|
| NOT_FOUND | Invalid or expired token |
| FORBIDDEN | Logged-in email doesn't match invitation |
| CONFLICT | Already a member |

---

### DOMAIN 3: Social Accounts

#### 9.3.1 List Social Accounts
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/social-accounts` |
| **Purpose** | List connected social accounts |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| platform | string | all |
| status | string | all |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Account ID |
| data[].platform | string | LINKEDIN, FACEBOOK, INSTAGRAM |
| data[].account_name | string | Page/account name |
| data[].account_username | string | Handle (if applicable) |
| data[].profile_image_url | string | Avatar URL |
| data[].status | string | Connection status |
| data[].connected_at | timestamp | Connection time |
| data[].connected_by.id | uuid | User who connected |
| data[].connected_by.full_name | string | User name |

---

#### 9.3.2 Initiate OAuth Connection
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/social-accounts/connect` |
| **Purpose** | Get OAuth URL for platform connection |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| platform | string | Yes | LINKEDIN, FACEBOOK, INSTAGRAM |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| oauth_url | string | URL to redirect user to |
| state | string | OAuth state parameter |

**Errors:**
| Code | Condition |
|------|-----------|
| UNPROCESSABLE | Social account limit reached |

---

#### 9.3.3 Complete OAuth Connection (Callback)
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/social-accounts/callback` |
| **Purpose** | Process OAuth callback and store connection |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| platform | string | Yes |
| code | string | Yes |
| state | string | Yes |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | New account ID |
| platform | string | Platform name |
| account_name | string | Page name |
| status | string | "CONNECTED" |
| connected_at | timestamp | Connection time |

**Errors:**
| Code | Condition |
|------|-----------|
| VALIDATION_ERROR | Invalid OAuth response |
| CONFLICT | Account already connected |
| UNPROCESSABLE | Account limit reached |

---

#### 9.3.4 Reconnect Social Account
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/social-accounts/{account_id}/reconnect` |
| **Purpose** | Get OAuth URL to refresh expired token |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| oauth_url | string | URL to redirect user to |
| state | string | OAuth state parameter |

---

#### 9.3.5 Disconnect Social Account
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/social-accounts/{account_id}` |
| **Purpose** | Disconnect social account |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Account disconnected" |

**Errors:**
| Code | Condition |
|------|-----------|
| UNPROCESSABLE | Has scheduled posts targeting this account |

---

#### 9.3.6 Get Social Account Health
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/social-accounts/{account_id}/health` |
| **Purpose** | Check token validity and connection status |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| status | string | Connection status |
| token_valid | boolean | Is token still valid |
| token_expires_at | timestamp | Token expiry (if known) |
| last_successful_action | timestamp | Last successful API call |
| issues | array | List of issues if any |

---

### DOMAIN 4: Content Engine

#### 9.4.1 List Posts
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/posts` |
| **Purpose** | List posts with filtering |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| status | string | all | Filter by PostStatus |
| author_id | uuid | all | Filter by author |
| social_account_id | uuid | all | Filter by target account |
| date_from | date | — | Scheduled/published after |
| date_to | date | — | Scheduled/published before |
| page | integer | 1 | Page number |
| per_page | integer | 20 | Items per page |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Post ID |
| data[].content_text | string | Post content |
| data[].status | string | Post status |
| data[].scheduled_at | timestamp | Scheduled time |
| data[].published_at | timestamp | Published time |
| data[].created_by.id | uuid | Author ID |
| data[].created_by.full_name | string | Author name |
| data[].targets[].social_account.id | uuid | Target account ID |
| data[].targets[].social_account.platform | string | Platform |
| data[].targets[].status | string | Target-specific status |
| data[].media_count | integer | Number of attachments |
| data[].created_at | timestamp | Creation time |
| meta | object | Pagination metadata |

---

#### 9.4.2 Get Post
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}` |
| **Purpose** | Get full post details |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| content_text | string | Post content |
| status | string | Post status |
| scheduled_at | timestamp | Scheduled time |
| scheduled_timezone | string | Timezone |
| published_at | timestamp | Published time |
| submitted_at | timestamp | Submission time |
| created_by.id | uuid | Author ID |
| created_by.full_name | string | Author name |
| media[] | array | Media attachments |
| media[].id | uuid | Media ID |
| media[].media_type | string | "image" or "video" |
| media[].file_url | string | File URL |
| media[].sort_order | integer | Display order |
| targets[] | array | Target accounts |
| targets[].id | uuid | PostTarget ID |
| targets[].social_account | object | Account details |
| targets[].platform_post_url | string | Published URL |
| targets[].published_at | timestamp | When published |
| targets[].failure_reason | string | Error if failed |
| approval | object | Approval information |
| approval.current | object | Active decision (null if none) |
| approval.current.decision | string | APPROVED or REJECTED |
| approval.current.comment | string | Reviewer comment |
| approval.current.decided_by | object | Approver details |
| approval.current.decided_at | timestamp | Decision time |
| approval.history | array | Past decisions (Owner/Admin only) |
| approval.history[].decision | string | APPROVED or REJECTED |
| approval.history[].comment | string | Reviewer comment |
| approval.history[].decided_by | object | Approver details |
| approval.history[].decided_at | timestamp | Decision time |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update |

**Approval Response Notes:**
- `approval.current` contains the active decision (where `is_active = TRUE`)
- `approval.history` contains previous decisions for audit trail (only visible to Owner/Admin)
- Editors see only `approval.current`; history array is omitted for Editors/Viewers
- History is ordered newest-first

---

#### 9.4.3 Create Post
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts` |
| **Purpose** | Create a new post draft |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |
| **Idempotency** | Supported |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| content_text | string | No | Max 10,000 chars |
| target_account_ids | uuid[] | Yes | Min 1; must be connected |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| content_text | string | Post content |
| status | string | "DRAFT" |
| targets[] | array | Target accounts |
| created_at | timestamp | Creation time |

**Errors:**
| Code | Condition |
|------|-----------|
| VALIDATION_ERROR | No targets specified |
| NOT_FOUND | Target account not found |
| UNPROCESSABLE | Target account not connected |

---

#### 9.4.4 Update Post
| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}` |
| **Purpose** | Update post content or targets |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor (own posts) |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| content_text | string | No |
| target_account_ids | uuid[] | No |

**Response Payload (200 OK):** Same as Get Post

**Errors:**
| Code | Condition |
|------|-----------|
| FORBIDDEN | Editor editing another's post |
| INVALID_STATE | Post not in DRAFT or REJECTED status |

**Phase-1 Constraint:** Posts can only be edited when status is DRAFT or REJECTED.

---

#### 9.4.5 Delete Post
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}` |
| **Purpose** | Delete a post |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor (own posts only) |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Post deleted" |

**Errors:**
| Code | Condition |
|------|-----------|
| FORBIDDEN | Editor deleting another's post |
| INVALID_STATE | Post already published |

---

#### 9.4.6 Upload Media
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/media` |
| **Purpose** | Upload media attachment to post |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor (own posts) |

**Request:** Multipart form data

| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| file | binary | Yes | Image or video |
| sort_order | integer | No | Default: append |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Media ID |
| media_type | string | "image" or "video" |
| file_url | string | CDN URL |
| file_size_bytes | integer | File size |
| mime_type | string | MIME type |
| width | integer | Dimensions |
| height | integer | Dimensions |
| sort_order | integer | Display order |

**Errors:**
| Code | Condition |
|------|-----------|
| VALIDATION_ERROR | Invalid file type |
| UNPROCESSABLE | File too large |
| INVALID_STATE | Post not in editable state |

---

#### 9.4.7 Delete Media
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/media/{media_id}` |
| **Purpose** | Remove media from post |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor (own posts) |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Media deleted" |

---

#### 9.4.8 Reorder Media
| | |
|---|---|
| **Method** | PUT |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/media/reorder` |
| **Purpose** | Set media display order |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor (own posts) |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| media_ids | uuid[] | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Media reordered" |

---

#### 9.4.9 Submit Post for Approval
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/submit` |
| **Purpose** | Submit draft for approval |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| status | string | "SUBMITTED" |
| submitted_at | timestamp | Submission time |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not in DRAFT or REJECTED status |
| VALIDATION_ERROR | Post has no content or media |

---

#### 9.4.10 Approve Post
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/approve` |
| **Purpose** | Approve submitted post |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| comment | string | No |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| status | string | "APPROVED" |
| approval.decision | string | "APPROVED" |
| approval.decided_by | object | Approver |
| approval.decided_at | timestamp | Decision time |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not in SUBMITTED status |

---

#### 9.4.11 Reject Post
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/reject` |
| **Purpose** | Reject submitted post |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| comment | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| status | string | "REJECTED" |
| approval.decision | string | "REJECTED" |
| approval.comment | string | Rejection reason |
| approval.decided_by | object | Approver |
| approval.decided_at | timestamp | Decision time |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not in SUBMITTED status |
| VALIDATION_ERROR | Comment required |

---

#### 9.4.12 Schedule Post
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/schedule` |
| **Purpose** | Schedule approved post for future publishing |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor (own posts if approved) |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| scheduled_at | timestamp | Yes | Must be in future |
| timezone | string | Yes | Valid IANA timezone |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| status | string | "SCHEDULED" |
| scheduled_at | timestamp | Scheduled time |
| scheduled_timezone | string | Timezone |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not APPROVED |
| VALIDATION_ERROR | Time in the past |

---

#### 9.4.13 Reschedule Post
| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/schedule` |
| **Purpose** | Change scheduled time |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| scheduled_at | timestamp | Yes |
| timezone | string | No |

**Response Payload (200 OK):** Same as Schedule Post

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not SCHEDULED |

---

#### 9.4.14 Unschedule Post
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/schedule` |
| **Purpose** | Remove from schedule, return to APPROVED |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| status | string | "APPROVED" |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not SCHEDULED |

---

#### 9.4.15 Publish Post Now
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/publish` |
| **Purpose** | Publish post immediately |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Response Payload (202 Accepted):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Post ID |
| status | string | "SCHEDULED" (publishing in progress) |
| message | string | "Publishing initiated" |

**Note:** This is an async operation. Post status will change to PUBLISHED or FAILED.

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | Post not APPROVED or SCHEDULED |
| UNPROCESSABLE | Target account not connected |

---

#### 9.4.16 Get Calendar View
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/calendar` |
| **Purpose** | Get posts for calendar display |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| start_date | date | Yes | Range start |
| end_date | date | Yes | Range end (max 90 days) |
| social_account_ids | uuid[] | No | Filter by accounts |
| statuses | string[] | No | Filter by status |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Post ID |
| data[].content_text | string | Preview (truncated) |
| data[].status | string | Post status |
| data[].date | date | Scheduled or published date |
| data[].time | time | Scheduled or published time |
| data[].platforms | string[] | Target platforms |
| data[].created_by.full_name | string | Author |

**Notes:**
- No pagination; returns all posts in date range
- Max date range: 90 days (enforced)
- Max result set: 500 posts; if exceeded, returns `422 UNPROCESSABLE` with message "Too many posts in range. Please narrow your date range or apply filters."
- This prevents accidental self-DOS from large agencies with high posting volume

---

### DOMAIN 5: Engagement Inbox

#### 9.5.1 List Inbox Items
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/inbox` |
| **Purpose** | List comments and mentions |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| status | string | all | UNREAD, READ, RESOLVED |
| item_type | string | all | COMMENT, MENTION |
| social_account_id | uuid | all | Filter by account |
| assigned_to_me | boolean | false | My assignments only |
| page | integer | 1 | Page number |
| per_page | integer | 20 | Items per page |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Item ID |
| data[].item_type | string | COMMENT or MENTION |
| data[].status | string | Item status |
| data[].content_text | string | Comment/mention text |
| data[].author_name | string | Author name |
| data[].author_username | string | Author handle |
| data[].author_avatar_url | string | Avatar URL |
| data[].social_account.id | uuid | Account ID |
| data[].social_account.platform | string | Platform |
| data[].social_account.account_name | string | Account name |
| data[].platform_created_at | timestamp | When posted |
| data[].assigned_to | object | Assignee (if any) |
| data[].reply_count | integer | Number of replies sent |
| data[].created_at | timestamp | Sync time |
| meta | object | Pagination metadata |
| meta.unread_count | integer | Total unread items |

---

#### 9.5.2 Get Inbox Item
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}` |
| **Purpose** | Get inbox item with replies |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Item ID |
| item_type | string | COMMENT or MENTION |
| status | string | Item status |
| content_text | string | Full text |
| author_name | string | Author name |
| author_username | string | Author handle |
| author_avatar_url | string | Avatar URL |
| author_profile_url | string | Profile link |
| social_account | object | Connected account |
| post | object | Related post (if comment on our post) |
| platform_created_at | timestamp | When posted |
| assigned_to | object | Assignee |
| assigned_at | timestamp | Assignment time |
| resolved_at | timestamp | Resolution time |
| resolved_by | object | Resolver |
| replies[] | array | Sent replies |
| replies[].id | uuid | Reply ID |
| replies[].content_text | string | Reply text |
| replies[].replied_by | object | Who replied |
| replies[].sent_at | timestamp | When sent |
| created_at | timestamp | Sync time |

**Side effect:** Marks item as READ if UNREAD.

---

#### 9.5.3 Reply to Inbox Item
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}/replies` |
| **Purpose** | Send reply to comment |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| content_text | string | Yes | Max 1000 chars |

**Response Payload (201 Created):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Reply ID |
| content_text | string | Reply text |
| replied_by | object | User |
| sent_at | timestamp | Send time |
| platform_reply_id | string | Platform's reply ID |

**Errors:**
| Code | Condition |
|------|-----------|
| UNPROCESSABLE | Cannot reply to mentions (platform limitation) |
| UNPROCESSABLE | Social account disconnected |

---

#### 9.5.4 Mark Item as Read
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}/read` |
| **Purpose** | Mark item as read |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Item ID |
| status | string | "READ" |

---

#### 9.5.5 Mark Item as Resolved
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}/resolve` |
| **Purpose** | Mark item as handled |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Item ID |
| status | string | "RESOLVED" |
| resolved_at | timestamp | Resolution time |
| resolved_by | object | Resolver |

---

#### 9.5.6 Reopen Item
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}/reopen` |
| **Purpose** | Reopen resolved item |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Item ID |
| status | string | "READ" |

---

#### 9.5.7 Assign Item
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}/assign` |
| **Purpose** | Assign item to team member |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| user_id | uuid | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Item ID |
| assigned_to | object | Assignee |
| assigned_at | timestamp | Assignment time |

**Errors:**
| Code | Condition |
|------|-----------|
| NOT_FOUND | User not a workspace member |

---

#### 9.5.8 Unassign Item
| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/{item_id}/assign` |
| **Purpose** | Remove assignment |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Item ID |
| assigned_to | null | No assignee |

---

#### 9.5.9 Bulk Mark as Read
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/inbox/bulk/read` |
| **Purpose** | Mark multiple items as read |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| item_ids | uuid[] | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| updated_count | integer | Items updated |

---

### DOMAIN 6: Analytics & Reports

#### 9.6.1 Get Post Analytics
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/posts/{post_id}/analytics` |
| **Purpose** | Get metrics for a published post |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| post_id | uuid | Post ID |
| targets[] | array | Per-platform metrics |
| targets[].social_account.id | uuid | Account ID |
| targets[].social_account.platform | string | Platform |
| targets[].platform_post_url | string | Post URL |
| targets[].metrics.likes | integer | Likes/reactions |
| targets[].metrics.comments | integer | Comments |
| targets[].metrics.shares | integer | Shares/reposts |
| targets[].metrics.impressions | integer | Impressions |
| targets[].metrics.reach | integer | Unique reach |
| targets[].metrics.clicks | integer | Clicks |
| targets[].metrics.engagement_rate | decimal | Engagement % |
| targets[].last_updated | timestamp | When metrics fetched |
| aggregated.total_engagement | integer | Sum of interactions |
| aggregated.avg_engagement_rate | decimal | Average rate |

**Errors:**
| Code | Condition |
|------|-----------|
| NOT_FOUND | Post not published |

---

#### 9.6.2 Get Workspace Analytics Summary
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/analytics` |
| **Purpose** | Aggregated metrics across workspace |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Required | Validation |
|-----------|------|:--------:|------------|
| start_date | date | Yes | Max 90 days range |
| end_date | date | Yes | Max 90 days range |
| social_account_ids | uuid[] | No | Filter by accounts |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| period.start | date | Range start |
| period.end | date | Range end |
| summary.total_posts | integer | Posts published |
| summary.total_likes | integer | Total likes |
| summary.total_comments | integer | Total comments |
| summary.total_shares | integer | Total shares |
| summary.total_impressions | integer | Total impressions |
| summary.total_reach | integer | Total reach |
| summary.avg_engagement_rate | decimal | Average rate |
| by_platform[] | array | Breakdown by platform |
| by_platform[].platform | string | Platform name |
| by_platform[].metrics | object | Platform totals |
| top_posts[] | array | Top 5 performing posts |
| daily_breakdown[] | array | Metrics by day |

---

#### 9.6.3 List Report Exports
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/reports` |
| **Purpose** | List generated reports |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| page | integer | 1 |
| per_page | integer | 20 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Report ID |
| data[].report_type | string | Template name |
| data[].format | string | PDF or CSV |
| data[].date_range_start | date | Period start |
| data[].date_range_end | date | Period end |
| data[].file_url | string | Download URL |
| data[].expires_at | timestamp | Link expiry |
| data[].exported_by | object | Who generated |
| data[].created_at | timestamp | Generation time |
| meta | object | Pagination metadata |

---

#### 9.6.4 Generate Report
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/reports` |
| **Purpose** | Generate new report (async) |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Request Payload:**
| Field | Type | Required | Validation |
|-------|------|:--------:|------------|
| report_type | string | Yes | "performance_summary", "engagement_detail" |
| format | string | Yes | "PDF" or "CSV" |
| start_date | date | Yes | Max 90 days range |
| end_date | date | Yes | Max 90 days range |
| social_account_ids | uuid[] | No | Filter accounts |

**Response Payload (202 Accepted):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Report ID |
| status | string | "GENERATING" |
| message | string | "Report generation started" |

**Note:** Poll GET /reports/{id} for completion.

---

#### 9.6.5 Get Report
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/reports/{report_id}` |
| **Purpose** | Get report status and download URL |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Report ID |
| report_type | string | Template name |
| format | string | PDF or CSV |
| status | string | "GENERATING", "READY", "FAILED" |
| file_url | string | Download URL (if ready) |
| file_size_bytes | integer | File size (if ready) |
| expires_at | timestamp | Download expiry |
| error_message | string | Error (if failed) |
| created_at | timestamp | Request time |

---

### DOMAIN 7: Billing & Plans

#### 9.7.1 List Available Plans
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/plans` |
| **Purpose** | List all active subscription plans |
| **Auth Required** | No |
| **Roles** | N/A |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Plan ID |
| data[].name | string | Plan name |
| data[].slug | string | Plan identifier |
| data[].description | string | Marketing copy |
| data[].price_cents | integer | Monthly price |
| data[].currency | string | Currency code |
| data[].seat_limit | integer | Max seats |
| data[].social_account_limit | integer | Max accounts |
| data[].ai_suggestions_monthly_limit | integer | AI quota |

---

#### 9.7.2 Get Workspace Subscription
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/subscription` |
| **Purpose** | Get current subscription details |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Subscription ID |
| plan | object | Current plan details |
| status | string | Subscription status |
| trial_ends_at | timestamp | Trial expiry (if trialing) |
| current_period_start | timestamp | Period start |
| current_period_end | timestamp | Period end |
| cancel_at_period_end | boolean | Cancellation pending? |
| usage.seats_used | integer | Current members |
| usage.seats_limit | integer | Plan limit |
| usage.social_accounts_used | integer | Connected accounts |
| usage.social_accounts_limit | integer | Plan limit |
| usage.ai_suggestions_used | integer | AI requests this month |
| usage.ai_suggestions_limit | integer | Monthly limit |

---

#### 9.7.3 Get Billing Portal URL
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/subscription/portal` |
| **Purpose** | Get Stripe billing portal URL |
| **Auth Required** | Yes |
| **Roles** | Owner only |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| portal_url | string | Stripe portal URL |
| expires_at | timestamp | URL expiry |

**Note:** Stripe portal handles payment method, invoices, and plan changes.

---

#### 9.7.4 Create Checkout Session
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/subscription/checkout` |
| **Purpose** | Create Stripe checkout for plan upgrade |
| **Auth Required** | Yes |
| **Roles** | Owner only |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| plan_id | uuid | Yes |
| success_url | string | Yes |
| cancel_url | string | Yes |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| checkout_url | string | Stripe checkout URL |
| session_id | string | Checkout session ID |

---

#### 9.7.5 Cancel Subscription
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/subscription/cancel` |
| **Purpose** | Cancel subscription at period end |
| **Auth Required** | Yes |
| **Roles** | Owner only |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Subscription will cancel at period end" |
| cancel_at | timestamp | When access ends |

---

#### 9.7.6 Resume Subscription
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/subscription/resume` |
| **Purpose** | Undo pending cancellation |
| **Auth Required** | Yes |
| **Roles** | Owner only |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| message | string | "Subscription resumed" |
| status | string | Current status |

**Errors:**
| Code | Condition |
|------|-----------|
| INVALID_STATE | No pending cancellation |

---

#### 9.7.7 List Invoices
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/invoices` |
| **Purpose** | List billing history |
| **Auth Required** | Yes |
| **Roles** | Owner only |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| page | integer | 1 |
| per_page | integer | 20 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Invoice ID |
| data[].amount_cents | integer | Total |
| data[].currency | string | Currency |
| data[].status | string | paid, open, etc. |
| data[].invoice_url | string | Hosted invoice |
| data[].invoice_pdf_url | string | PDF download |
| data[].period_start | timestamp | Period |
| data[].period_end | timestamp | Period |
| data[].paid_at | timestamp | Payment time |
| data[].created_at | timestamp | Invoice date |
| meta | object | Pagination metadata |

---

### DOMAIN 8: AI Assist

**Important: Best-Effort Service**

AI suggestion endpoints are **best-effort** and non-blocking:
- If the LLM service is unavailable or slow, endpoints return `503 SERVICE_UNAVAILABLE`
- Failure to generate suggestions does NOT block post creation or publishing
- Frontend should gracefully handle AI unavailability (show fallback UI, not error)
- Suggestions are assistive only; user workflow continues regardless of AI status

This ensures post creation UX is never deadlocked by external LLM API issues.

#### 9.8.1 Generate Caption Suggestions
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/ai/captions` |
| **Purpose** | Get AI-generated caption suggestions |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Request Payload:**
| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| context | string | No | Topic or keywords |
| platform | string | No | Target platform for tone |
| tone | string | No | "professional", "casual", "engaging" |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| suggestions[] | array | Caption options |
| suggestions[].text | string | Suggested caption |
| suggestions[].character_count | integer | Length |
| usage.requests_used | integer | Monthly usage |
| usage.requests_limit | integer | Monthly limit |

**Errors:**
| Code | Condition |
|------|-----------|
| RATE_LIMITED | Monthly limit exceeded |

---

#### 9.8.2 Generate Hashtag Suggestions
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/workspaces/{workspace_id}/ai/hashtags` |
| **Purpose** | Get AI-generated hashtag suggestions |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor |

**Request Payload:**
| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| content | string | Yes | Post content to analyze |
| platform | string | No | Target platform |
| count | integer | No | Number of hashtags (default: 10) |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| hashtags[] | string[] | Suggested hashtags |
| usage.requests_used | integer | Monthly usage |
| usage.requests_limit | integer | Monthly limit |

**Errors:**
| Code | Condition |
|------|-----------|
| RATE_LIMITED | Monthly limit exceeded |

---

#### 9.8.3 Get AI Usage
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/ai/usage` |
| **Purpose** | Get current AI usage stats |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin, Editor, Viewer |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| period_start | date | Current month start |
| period_end | date | Current month end |
| requests_used | integer | Requests this month |
| requests_limit | integer | Monthly limit |
| remaining | integer | Requests remaining |

---

### DOMAIN 9: Notifications

#### 9.9.1 List Notifications
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/notifications` |
| **Purpose** | List user's notifications |
| **Auth Required** | Yes |
| **Roles** | Any |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| is_read | boolean | all |
| workspace_id | uuid | all |
| page | integer | 1 |
| per_page | integer | 20 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Notification ID |
| data[].type | string | Notification type |
| data[].title | string | Short title |
| data[].body | string | Message body |
| data[].workspace | object | Context (if any) |
| data[].resource_type | string | Related entity type |
| data[].resource_id | uuid | Related entity ID |
| data[].is_read | boolean | Read status |
| data[].created_at | timestamp | Notification time |
| meta | object | Pagination metadata |
| meta.unread_count | integer | Total unread |

---

#### 9.9.2 Mark Notification as Read
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/notifications/{notification_id}/read` |
| **Purpose** | Mark single notification as read |
| **Auth Required** | Yes |
| **Roles** | Any |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| id | uuid | Notification ID |
| is_read | boolean | true |
| read_at | timestamp | Read time |

---

#### 9.9.3 Mark All as Read
| | |
|---|---|
| **Method** | POST |
| **Path** | `/v1/notifications/read-all` |
| **Purpose** | Mark all notifications as read |
| **Auth Required** | Yes |
| **Roles** | Any |

**Request Payload:**
| Field | Type | Required |
|-------|------|:--------:|
| workspace_id | uuid | No |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| updated_count | integer | Notifications marked |

---

#### 9.9.4 Get Unread Count
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/notifications/unread-count` |
| **Purpose** | Get unread notification count |
| **Auth Required** | Yes |
| **Roles** | Any |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| count | integer | Unread count |

---

### DOMAIN 10: Audit Log

#### 9.10.1 List Audit Logs
| | |
|---|---|
| **Method** | GET |
| **Path** | `/v1/workspaces/{workspace_id}/audit-logs` |
| **Purpose** | List workspace activity history |
| **Auth Required** | Yes |
| **Roles** | Owner, Admin |

**Query Parameters:**
| Parameter | Type | Default |
|-----------|------|---------|
| action | string | all |
| user_id | uuid | all |
| resource_type | string | all |
| start_date | date | 30 days ago |
| end_date | date | today |
| page | integer | 1 |
| per_page | integer | 50 |

**Response Payload (200 OK):**
| Field | Type | Description |
|-------|------|-------------|
| data[].id | uuid | Log ID |
| data[].action | string | Action type |
| data[].user | object | Actor (if not system) |
| data[].resource_type | string | Affected entity |
| data[].resource_id | uuid | Entity ID |
| data[].details | object | Additional context |
| data[].ip_address | string | Actor IP |
| data[].created_at | timestamp | When occurred |
| meta | object | Pagination metadata |

---

## 10. Endpoint Summary

### Count by Domain

| Domain | Endpoints |
|--------|:---------:|
| Identity & Access | 11 |
| Workspace Management | 13 |
| Social Accounts | 6 |
| Content Engine | 16 |
| Engagement Inbox | 9 |
| Analytics & Reports | 5 |
| Billing & Plans | 7 |
| AI Assist | 3 |
| Notifications | 4 |
| Audit Log | 1 |
| **TOTAL** | **75** |

---

## 11. Rate Limiting

| Scope | Limit | Window |
|-------|-------|--------|
| Authentication endpoints | 10 requests | per minute per IP |
| AI suggestion endpoints | Plan-based | per month per workspace |
| General API | 1000 requests | per minute per user |
| File uploads | 100 uploads | per hour per workspace |

---

## 12. Phase-1 API Constraints

| Constraint | Rationale |
|------------|-----------|
| No WebSocket support | Polling-based updates only |
| No public API / API keys | Internal use only |
| No webhooks | No external integrations |
| No batch endpoints (except inbox) | Keep implementation simple |
| No GraphQL | REST only |
| Max 90-day date range | Analytics/report limitation |
| No custom fields on entities | Fixed schema |

---

## 13. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | API Architecture | Initial Phase-1 API contract |
| 1.1 | Feb 2026 | API Architecture | Added: workspace authorization rule (403 vs 404); approval history in Get Post response; calendar max result set constraint; AI Assist best-effort service note |

---

**END OF PHASE-1 API CONTRACT**
