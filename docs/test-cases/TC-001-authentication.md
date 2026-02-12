# TC-001: Authentication Test Cases

**Feature:** User Authentication
**Priority:** Critical
**Related Docs:** [API Contract - Auth](../04_phase1_api_contract.md)

---

## Overview

Tests for user registration, login, logout, token management, and password reset.

---

## Unit Tests (Codex to implement)

### UT-001-001: Password hashing on user creation
- **File:** `tests/Unit/Models/UserTest.php`
- **Description:** Verify password is hashed when creating a user
- **Test Code Pattern:**
```php
public function test_password_is_hashed_on_creation(): void
{
    $user = User::factory()->create(['password' => 'plaintext']);
    $this->assertNotEquals('plaintext', $user->password);
    $this->assertTrue(Hash::check('plaintext', $user->password));
}
```
- **Status:** [ ] Pending

### UT-001-002: Email must be unique
- **File:** `tests/Unit/Models/UserTest.php`
- **Description:** Verify duplicate emails are rejected
- **Status:** [ ] Pending

### UT-001-003: Email must be valid format
- **File:** `tests/Unit/Requests/RegisterRequestTest.php`
- **Description:** Verify invalid email formats are rejected
- **Test Data:** `invalid`, `test@`, `@test.com`, `test@.com`
- **Status:** [ ] Pending

### UT-001-004: Password minimum length enforced
- **File:** `tests/Unit/Requests/RegisterRequestTest.php`
- **Description:** Verify passwords under 8 characters are rejected
- **Status:** [ ] Pending

### UT-001-005: JWT token generation
- **File:** `tests/Unit/Services/AuthServiceTest.php`
- **Description:** Verify JWT contains correct claims
- **Expected Claims:** `sub`, `email`, `exp`, `iat`
- **Status:** [ ] Pending

### UT-001-006: JWT token expiration
- **File:** `tests/Unit/Services/AuthServiceTest.php`
- **Description:** Verify access token expires in 15 minutes
- **Status:** [ ] Pending

### UT-001-007: Refresh token expiration
- **File:** `tests/Unit/Services/AuthServiceTest.php`
- **Description:** Verify refresh token expires in 7 days
- **Status:** [ ] Pending

### UT-001-008: Password reset token generation
- **File:** `tests/Unit/Services/PasswordResetServiceTest.php`
- **Description:** Verify reset token is unique and expires in 1 hour
- **Status:** [ ] Pending

### UT-001-009: User status check on login
- **File:** `tests/Unit/Services/AuthServiceTest.php`
- **Description:** Verify suspended users cannot login
- **Status:** [ ] Pending

### UT-001-010: Email verification token generation
- **File:** `tests/Unit/Services/EmailVerificationServiceTest.php`
- **Description:** Verify verification token is created on registration
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-001-001: User registration success
- **File:** `tests/Feature/Api/V1/Auth/RegisterTest.php`
- **Endpoint:** `POST /v1/auth/register`
- **Request:**
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
- **Expected:** 201 Created, user object returned, no password in response
- **Status:** [ ] Pending

### IT-001-002: User registration - duplicate email
- **File:** `tests/Feature/Api/V1/Auth/RegisterTest.php`
- **Endpoint:** `POST /v1/auth/register`
- **Precondition:** User with email already exists
- **Expected:** 422 Validation Error, "email already taken"
- **Status:** [ ] Pending

### IT-001-003: User registration - weak password
- **File:** `tests/Feature/Api/V1/Auth/RegisterTest.php`
- **Request:** Password = "123"
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-001-004: Login success
- **File:** `tests/Feature/Api/V1/Auth/LoginTest.php`
- **Endpoint:** `POST /v1/auth/login`
- **Request:**
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```
- **Expected:** 200 OK, access_token and refresh_token returned
- **Status:** [ ] Pending

### IT-001-005: Login - wrong password
- **File:** `tests/Feature/Api/V1/Auth/LoginTest.php`
- **Expected:** 401 Unauthorized
- **Status:** [ ] Pending

### IT-001-006: Login - non-existent user
- **File:** `tests/Feature/Api/V1/Auth/LoginTest.php`
- **Expected:** 401 Unauthorized (same as wrong password for security)
- **Status:** [ ] Pending

### IT-001-007: Login - suspended user
- **File:** `tests/Feature/Api/V1/Auth/LoginTest.php`
- **Precondition:** User status = SUSPENDED
- **Expected:** 403 Forbidden, "account suspended"
- **Status:** [ ] Pending

### IT-001-008: Token refresh success
- **File:** `tests/Feature/Api/V1/Auth/RefreshTest.php`
- **Endpoint:** `POST /v1/auth/refresh`
- **Request:** Valid refresh token
- **Expected:** 200 OK, new access_token returned
- **Status:** [ ] Pending

### IT-001-009: Token refresh - expired token
- **File:** `tests/Feature/Api/V1/Auth/RefreshTest.php`
- **Expected:** 401 Unauthorized
- **Status:** [ ] Pending

### IT-001-010: Token refresh - invalid token
- **File:** `tests/Feature/Api/V1/Auth/RefreshTest.php`
- **Expected:** 401 Unauthorized
- **Status:** [ ] Pending

### IT-001-011: Logout success
- **File:** `tests/Feature/Api/V1/Auth/LogoutTest.php`
- **Endpoint:** `POST /v1/auth/logout`
- **Expected:** 200 OK, token invalidated
- **Status:** [ ] Pending

### IT-001-012: Access protected route with valid token
- **File:** `tests/Feature/Api/V1/Auth/ProtectedRouteTest.php`
- **Endpoint:** `GET /v1/me`
- **Expected:** 200 OK, user data returned
- **Status:** [ ] Pending

### IT-001-013: Access protected route without token
- **File:** `tests/Feature/Api/V1/Auth/ProtectedRouteTest.php`
- **Expected:** 401 Unauthorized
- **Status:** [ ] Pending

### IT-001-014: Access protected route with expired token
- **File:** `tests/Feature/Api/V1/Auth/ProtectedRouteTest.php`
- **Expected:** 401 Unauthorized
- **Status:** [ ] Pending

### IT-001-015: Password reset request
- **File:** `tests/Feature/Api/V1/Auth/PasswordResetTest.php`
- **Endpoint:** `POST /v1/auth/forgot-password`
- **Expected:** 200 OK (always, for security)
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-001-001: Complete registration flow
- **File:** `tests/e2e/auth/register.spec.ts`
- **Steps:**
  1. Navigate to /register
  2. Fill in name, email, password
  3. Click register button
  4. Verify redirect to email verification page
- **Status:** [ ] Pending

### E2E-001-002: Complete login flow
- **File:** `tests/e2e/auth/login.spec.ts`
- **Steps:**
  1. Navigate to /login
  2. Fill in email, password
  3. Click login button
  4. Verify redirect to dashboard
  5. Verify user name displayed
- **Status:** [ ] Pending

### E2E-001-003: Logout flow
- **File:** `tests/e2e/auth/logout.spec.ts`
- **Steps:**
  1. Login as user
  2. Click logout
  3. Verify redirect to login page
  4. Verify cannot access dashboard
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-001-001: Registration email received
- **Steps:**
  1. Register new user
  2. Check email inbox
  3. Verify verification email received
  4. Verify email content correct
- **Status:** [ ] Not tested

### MT-001-002: Password reset email received
- **Steps:**
  1. Request password reset
  2. Check email inbox
  3. Verify reset email received
  4. Click link, verify redirect to reset page
- **Status:** [ ] Not tested

### MT-001-003: Session persistence
- **Steps:**
  1. Login
  2. Close browser
  3. Reopen browser
  4. Navigate to app
  5. Verify still logged in (if remember me) or logged out
- **Status:** [ ] Not tested

### MT-001-004: Concurrent session handling
- **Steps:**
  1. Login on browser A
  2. Login on browser B
  3. Verify both sessions work
  4. Logout on browser A
  5. Verify browser B still works
- **Status:** [ ] Not tested

### MT-001-005: Login error messages user-friendly
- **Steps:**
  1. Attempt login with wrong password
  2. Verify error message is clear but not revealing
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-001-001: Brute force protection
- **Attack:** Rapid login attempts with wrong password
- **Expected:** Account locked after 5 failed attempts
- **Status:** [ ] Not tested

### ST-001-002: Token not exposed in URL
- **Verify:** Tokens never appear in URL parameters
- **Status:** [ ] Not tested

### ST-001-003: Password not logged
- **Verify:** Passwords never appear in logs
- **Status:** [ ] Not tested

### ST-001-004: Secure cookie flags
- **Verify:** Auth cookies have Secure, HttpOnly, SameSite flags
- **Status:** [ ] Not tested

### ST-001-005: HTTPS enforced
- **Verify:** HTTP redirects to HTTPS
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 10 | - | - | 10 |
| Integration | 15 | - | - | 15 |
| E2E | 3 | - | - | 3 |
| Manual | 5 | - | - | 5 |
| Security | 5 | - | - | 5 |
| **Total** | **38** | **-** | **-** | **38** |

---

**Last Updated:** February 2026
**Status:** Draft
