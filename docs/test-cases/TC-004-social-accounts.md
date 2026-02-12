# TC-004: Social Accounts Test Cases

**Feature:** Social Account OAuth & Management
**Priority:** Critical
**Related Docs:** [API Contract - Social Accounts](../04_phase1_api_contract.md)

---

## Overview

Tests for OAuth connection flow, social account management, token refresh, and platform-specific functionality for LinkedIn, Facebook, and Instagram.

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── Social Accounts:
│   ├── social-a1: LinkedIn Company Page (ACTIVE)
│   ├── social-a2: Facebook Page (ACTIVE)
│   ├── social-a3: Instagram Business (ACTIVE)
│   └── social-a4: LinkedIn (EXPIRED token)
│
└── Members:
    ├── Owner: alice@acme.test
    ├── Admin: admin@acme.test
    └── Viewer: viewer@acme.test

WORKSPACE B ("Beta Brand")
└── Social Accounts:
    └── social-b1: LinkedIn Company Page

MOCK OAUTH PROVIDERS
├── LinkedIn OAuth Mock
├── Facebook OAuth Mock
└── Instagram OAuth Mock
```

---

## Unit Tests (Codex to implement)

### UT-004-001: Social account platform validation
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify only valid platforms (LINKEDIN, FACEBOOK, INSTAGRAM) accepted
- **Status:** [ ] Pending

### UT-004-002: OAuth token encryption
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify access_token is encrypted at rest
- **Test Pattern:**
```php
public function test_access_token_is_encrypted(): void
{
    $account = SocialAccount::factory()->create([
        'access_token' => 'plaintext_token'
    ]);

    // Raw database value should be encrypted
    $raw = DB::table('social_accounts')->where('id', $account->id)->first();
    $this->assertNotEquals('plaintext_token', $raw->access_token);

    // Model accessor should decrypt
    $this->assertEquals('plaintext_token', $account->access_token);
}
```
- **Status:** [ ] Pending

### UT-004-003: Token expiry calculation
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify token_expires_at is correctly calculated
- **Status:** [ ] Pending

### UT-004-004: isTokenExpired accessor
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify correct expired status based on token_expires_at
- **Status:** [ ] Pending

### UT-004-005: Social account status transitions
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify valid statuses (ACTIVE, EXPIRED, REVOKED, DISCONNECTED)
- **Status:** [ ] Pending

### UT-004-006: LinkedIn page type validation
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify LinkedIn accounts require page_id
- **Status:** [ ] Pending

### UT-004-007: Facebook page permissions validation
- **File:** `tests/Unit/Services/FacebookServiceTest.php`
- **Description:** Verify required permissions are present
- **Status:** [ ] Pending

### UT-004-008: Instagram business account validation
- **File:** `tests/Unit/Services/InstagramServiceTest.php`
- **Description:** Verify Instagram accounts are business accounts
- **Status:** [ ] Pending

### UT-004-009: Refresh token storage
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify refresh_token is stored encrypted
- **Status:** [ ] Pending

### UT-004-010: Workspace scope on social accounts
- **File:** `tests/Unit/Models/SocialAccountTest.php`
- **Description:** Verify workspace_id is required and scoped
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-004-001: List workspace social accounts
- **File:** `tests/Feature/Api/V1/SocialAccounts/ListAccountsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/social-accounts`
- **Expected:** 200 OK, list of accounts for workspace only
- **Status:** [ ] Pending

### IT-004-002: List accounts - viewer can view
- **File:** `tests/Feature/Api/V1/SocialAccounts/ListAccountsTest.php`
- **Setup:** Viewer queries accounts
- **Expected:** 200 OK (read access)
- **Status:** [ ] Pending

### IT-004-003: List accounts - non-member forbidden
- **File:** `tests/Feature/Api/V1/SocialAccounts/ListAccountsTest.php`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-004-004: Initiate LinkedIn OAuth
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/social-accounts/oauth/linkedin`
- **Expected:** 302 Redirect to LinkedIn with correct params
- **Status:** [ ] Pending

### IT-004-005: LinkedIn OAuth callback success
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Endpoint:** `GET /v1/oauth/linkedin/callback`
- **Setup:** Mock LinkedIn token exchange
- **Expected:** Social account created, redirect to success page
- **Status:** [ ] Pending

### IT-004-006: LinkedIn OAuth callback - denied
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Setup:** User denies OAuth permission
- **Expected:** Redirect to error page, no account created
- **Status:** [ ] Pending

### IT-004-007: LinkedIn OAuth callback - invalid state
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Setup:** State parameter doesn't match
- **Expected:** 400 Bad Request, CSRF protection
- **Status:** [ ] Pending

### IT-004-008: Initiate Facebook OAuth
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/social-accounts/oauth/facebook`
- **Expected:** 302 Redirect with correct scopes
- **Status:** [ ] Pending

### IT-004-009: Facebook OAuth callback - page selection
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Setup:** User manages multiple pages
- **Expected:** Return page list for selection
- **Status:** [ ] Pending

### IT-004-010: Initiate Instagram OAuth
- **File:** `tests/Feature/Api/V1/SocialAccounts/OAuthTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/social-accounts/oauth/instagram`
- **Expected:** 302 Redirect via Facebook OAuth
- **Status:** [ ] Pending

### IT-004-011: Get social account details
- **File:** `tests/Feature/Api/V1/SocialAccounts/GetAccountTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/social-accounts/{account_id}`
- **Expected:** 200 OK, account details (without token)
- **Status:** [ ] Pending

### IT-004-012: Get account - cross-workspace forbidden
- **File:** `tests/Feature/Api/V1/SocialAccounts/GetAccountTest.php`
- **Setup:** Request Workspace B account from Workspace A context
- **Expected:** 404 Not Found
- **Status:** [ ] Pending

### IT-004-013: Refresh account token
- **File:** `tests/Feature/Api/V1/SocialAccounts/RefreshTokenTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/social-accounts/{account_id}/refresh`
- **Setup:** Account has refresh_token
- **Expected:** 200 OK, token refreshed
- **Status:** [ ] Pending

### IT-004-014: Refresh token - no refresh token available
- **File:** `tests/Feature/Api/V1/SocialAccounts/RefreshTokenTest.php`
- **Expected:** 422 "Reconnection required"
- **Status:** [ ] Pending

### IT-004-015: Disconnect social account - owner
- **File:** `tests/Feature/Api/V1/SocialAccounts/DisconnectTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}/social-accounts/{account_id}`
- **Expected:** 200 OK, account marked as DISCONNECTED
- **Status:** [ ] Pending

### IT-004-016: Disconnect account - editor forbidden
- **File:** `tests/Feature/Api/V1/SocialAccounts/DisconnectTest.php`
- **Expected:** 403 Forbidden (only owner/admin can disconnect)
- **Status:** [ ] Pending

### IT-004-017: Account with pending posts - disconnect warning
- **File:** `tests/Feature/Api/V1/SocialAccounts/DisconnectTest.php`
- **Setup:** Account has scheduled posts
- **Expected:** 422 with warning, require force flag
- **Status:** [ ] Pending

---

## Background Job Tests (Codex to implement)

### JT-004-001: Token refresh job
- **File:** `tests/Feature/Jobs/RefreshSocialTokenJobTest.php`
- **Description:** Verify tokens approaching expiry are refreshed
- **Status:** [ ] Pending

### JT-004-002: Token refresh job - failure handling
- **File:** `tests/Feature/Jobs/RefreshSocialTokenJobTest.php`
- **Description:** Verify account marked EXPIRED on refresh failure
- **Status:** [ ] Pending

### JT-004-003: Expired token notification
- **File:** `tests/Feature/Jobs/TokenExpiryNotificationJobTest.php`
- **Description:** Verify workspace admins notified of expiring tokens
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-004-001: Connect LinkedIn account
- **File:** `tests/e2e/social-accounts/connect-linkedin.spec.ts`
- **Steps:**
  1. Login as workspace admin
  2. Navigate to Social Accounts
  3. Click "Connect LinkedIn"
  4. Complete OAuth flow (mocked)
  5. Select company page
  6. Verify account appears in list
- **Status:** [ ] Pending

### E2E-004-002: Disconnect social account
- **File:** `tests/e2e/social-accounts/disconnect.spec.ts`
- **Steps:**
  1. Login as workspace owner
  2. Navigate to Social Accounts
  3. Click disconnect on an account
  4. Confirm in modal
  5. Verify account removed from list
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-004-001: Real LinkedIn OAuth flow
- **Steps:**
  1. Use real LinkedIn developer credentials
  2. Complete full OAuth flow
  3. Verify page selection works
  4. Verify tokens stored correctly
- **Status:** [ ] Not tested

### MT-004-002: Real Facebook/Instagram OAuth flow
- **Steps:**
  1. Use real Facebook app credentials
  2. Complete OAuth for Facebook Page
  3. Complete OAuth for Instagram Business
  4. Verify permissions granted
- **Status:** [ ] Not tested

### MT-004-003: Token expiry workflow
- **Steps:**
  1. Connect account with short-lived token
  2. Wait for token to expire (or manually expire)
  3. Verify EXPIRED status shown
  4. Verify reconnect prompt displayed
  5. Reconnect and verify restored
- **Status:** [ ] Not tested

### MT-004-004: Multiple accounts same platform
- **Steps:**
  1. Connect multiple LinkedIn company pages
  2. Verify all appear in list
  3. Verify can post to each independently
- **Status:** [ ] Not tested

### MT-004-005: Account health indicators
- **Steps:**
  1. View accounts with various statuses
  2. Verify status badges correct
  3. Verify last sync time displayed
  4. Verify error messages for issues
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-004-001: OAuth state parameter validation
- **Attack:** Tamper with state parameter in callback
- **Expected:** Request rejected, CSRF prevention
- **Status:** [ ] Not tested

### ST-004-002: Token not exposed in API responses
- **Attack:** Check API responses for token leakage
- **Expected:** Tokens never returned in responses
- **Status:** [ ] Not tested

### ST-004-003: Token not logged
- **Attack:** Check application logs
- **Expected:** Tokens masked or absent from logs
- **Status:** [ ] Not tested

### ST-004-004: Cross-workspace token access
- **Attack:** Try to use Workspace B account from Workspace A
- **Expected:** 404 Not Found
- **Status:** [ ] Not tested

### ST-004-005: OAuth redirect URI validation
- **Attack:** Modify redirect_uri in OAuth initiation
- **Expected:** Only whitelisted URIs accepted
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 10 | - | - | 10 |
| Integration | 17 | - | - | 17 |
| Job Tests | 3 | - | - | 3 |
| E2E | 2 | - | - | 2 |
| Manual | 5 | - | - | 5 |
| Security | 5 | - | - | 5 |
| **Total** | **42** | **-** | **-** | **42** |

---

**Last Updated:** February 2026
**Status:** Draft
