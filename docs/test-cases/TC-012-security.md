# TC-012: Security Test Cases

**Feature:** Application Security
**Priority:** Critical
**Related Docs:** [Tenancy Enforcement](../07_saas_tenancy_enforcement.md), [SDLC Security](../08_sdlc_comprehensive_plan.md)

---

## Overview

Comprehensive security tests covering OWASP Top 10, authentication security, authorization, data protection, and API security. These tests are critical for SaaS platform security.

---

## Test Environment Setup

```
SECURITY TEST USERS
├── attacker@malicious.test (no workspace access)
├── alice@acme.test (Workspace A owner)
├── bob@beta.test (Workspace B owner)
└── suspended@test.com (suspended user)

SECURITY TEST DATA
├── SQL injection payloads
├── XSS payloads
├── CSRF tokens
└── Malformed JWTs
```

---

## A01: Broken Access Control

### ST-012-001: Horizontal privilege escalation - workspace
- **Attack:** User A accesses User B's workspace data
- **Method:** `GET /v1/workspaces/{workspace_b_id}/posts`
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

### ST-012-002: Horizontal privilege escalation - resource ID
- **Attack:** User A accesses resource by guessing ID
- **Method:** `GET /v1/workspaces/{workspace_a_id}/posts/{post_b_id}`
- **Expected:** 404 Not Found (not 403, to prevent ID enumeration)
- **Status:** [ ] Not tested

### ST-012-003: Vertical privilege escalation - role
- **Attack:** Editor tries admin-only action
- **Method:** `DELETE /v1/workspaces/{id}` as Editor
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

### ST-012-004: Insecure direct object reference (IDOR)
- **Attack:** Modify resource ID in URL/body to access other resources
- **Method:** Test all endpoints with cross-workspace IDs
- **Expected:** All return 403 or 404
- **Status:** [ ] Not tested

### ST-012-005: Forced browsing
- **Attack:** Access admin URLs without authentication
- **Method:** `GET /admin/users`, `GET /v1/internal/*`
- **Expected:** 401 or 404
- **Status:** [ ] Not tested

### ST-012-006: API endpoint enumeration
- **Attack:** Scan for undocumented endpoints
- **Method:** Common endpoint wordlist scan
- **Expected:** All endpoints require authentication
- **Status:** [ ] Not tested

---

## A02: Cryptographic Failures

### ST-012-007: Password storage
- **Verify:** Passwords hashed with bcrypt/argon2
- **Method:** Database inspection
- **Expected:** No plaintext or weak hashes
- **Status:** [ ] Not tested

### ST-012-008: Token encryption
- **Verify:** OAuth tokens encrypted at rest
- **Method:** Database inspection
- **Expected:** AES-256 encryption used
- **Status:** [ ] Not tested

### ST-012-009: HTTPS enforcement
- **Verify:** HTTP requests redirect to HTTPS
- **Method:** `curl -I http://app.bizsocials.com`
- **Expected:** 301/302 to HTTPS
- **Status:** [ ] Not tested

### ST-012-010: TLS configuration
- **Verify:** TLS 1.2+ only, strong ciphers
- **Method:** SSL Labs test or similar
- **Expected:** Grade A or better
- **Status:** [ ] Not tested

### ST-012-011: Sensitive data in logs
- **Verify:** Passwords, tokens not in logs
- **Method:** Grep application logs
- **Expected:** All sensitive data masked
- **Status:** [ ] Not tested

---

## A03: Injection

### ST-012-012: SQL injection - login
- **Attack:** `' OR '1'='1' --` in email field
- **Method:** `POST /v1/auth/login`
- **Expected:** Normal auth failure, no SQL error
- **Status:** [ ] Not tested

### ST-012-013: SQL injection - search
- **Attack:** `'; DROP TABLE posts; --` in search
- **Method:** `GET /v1/workspaces/{id}/posts?search=PAYLOAD`
- **Expected:** Parameterized query, no injection
- **Status:** [ ] Not tested

### ST-012-014: NoSQL injection
- **Attack:** JSON injection in filters
- **Method:** `{"$gt": ""}` in filter parameters
- **Expected:** Rejected or sanitized
- **Status:** [ ] Not tested

### ST-012-015: Command injection
- **Attack:** `; cat /etc/passwd` in filenames
- **Method:** Media upload with malicious filename
- **Expected:** Filename sanitized
- **Status:** [ ] Not tested

### ST-012-016: LDAP injection
- **Attack:** LDAP metacharacters in input
- **Expected:** Not applicable (no LDAP) or sanitized
- **Status:** [ ] Not tested

---

## A04: Insecure Design

### ST-012-017: Business logic bypass - approval
- **Attack:** Publish post without approval
- **Method:** Direct POST to publish endpoint
- **Expected:** Server enforces approval workflow
- **Status:** [ ] Not tested

### ST-012-018: Business logic bypass - plan limits
- **Attack:** Exceed plan limits via API
- **Method:** Create resources beyond limit
- **Expected:** Server enforces limits
- **Status:** [ ] Not tested

### ST-012-019: Race condition - double submit
- **Attack:** Submit same action twice rapidly
- **Method:** Parallel POST requests
- **Expected:** Idempotency or mutex protection
- **Status:** [ ] Not tested

### ST-012-020: Enumeration via timing
- **Attack:** Detect valid emails via response time
- **Method:** Compare login times for valid vs invalid
- **Expected:** Consistent timing (timing-safe comparison)
- **Status:** [ ] Not tested

---

## A05: Security Misconfiguration

### ST-012-021: Debug mode disabled
- **Verify:** APP_DEBUG=false in production
- **Method:** Trigger error, check response
- **Expected:** Generic error, no stack trace
- **Status:** [ ] Not tested

### ST-012-022: Default credentials
- **Verify:** No default admin accounts
- **Method:** Try common credentials
- **Expected:** All rejected
- **Status:** [ ] Not tested

### ST-012-023: Directory listing
- **Verify:** No directory browsing enabled
- **Method:** `GET /uploads/`, `GET /storage/`
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

### ST-012-024: Security headers
- **Verify:** Proper security headers set
- **Headers Required:**
  - X-Frame-Options: DENY
  - X-Content-Type-Options: nosniff
  - X-XSS-Protection: 1; mode=block
  - Content-Security-Policy: ...
  - Strict-Transport-Security: max-age=31536000
- **Status:** [ ] Not tested

### ST-012-025: CORS configuration
- **Verify:** CORS allows only expected origins
- **Method:** Preflight from malicious origin
- **Expected:** Rejected
- **Status:** [ ] Not tested

---

## A06: Vulnerable Components

### ST-012-026: Dependency vulnerabilities
- **Method:** `npm audit`, `composer audit`
- **Expected:** No high/critical vulnerabilities
- **Status:** [ ] Not tested

### ST-012-027: Outdated framework
- **Verify:** Laravel/Vue are current versions
- **Expected:** Latest stable versions
- **Status:** [ ] Not tested

---

## A07: Authentication Failures

### ST-012-028: Brute force - login
- **Attack:** Rapid login attempts
- **Expected:** Account lockout after 5 failures
- **Status:** [ ] Not tested

### ST-012-029: Brute force - password reset
- **Attack:** Rapid reset token guessing
- **Expected:** Rate limiting, token expiration
- **Status:** [ ] Not tested

### ST-012-030: Session fixation
- **Attack:** Set session ID before login
- **Expected:** Session regenerated on login
- **Status:** [ ] Not tested

### ST-012-031: JWT manipulation
- **Attack:** Modify JWT claims without re-signing
- **Expected:** Signature validation fails
- **Status:** [ ] Not tested

### ST-012-032: JWT algorithm confusion
- **Attack:** Change algorithm to "none"
- **Expected:** Rejected, algorithm whitelisted
- **Status:** [ ] Not tested

### ST-012-033: Weak password allowed
- **Attack:** Register with "password123"
- **Expected:** Rejected by password policy
- **Status:** [ ] Not tested

### ST-012-034: Credential stuffing protection
- **Attack:** Test with known breached credentials
- **Expected:** HaveIBeenPwned check or similar
- **Status:** [ ] Not tested

---

## A08: Software Integrity Failures

### ST-012-035: Unsigned updates
- **Verify:** Package integrity verified
- **Method:** Check package-lock.json, composer.lock
- **Expected:** Lock files committed, integrity checks enabled
- **Status:** [ ] Not tested

### ST-012-036: CI/CD security
- **Verify:** Pipeline cannot be bypassed
- **Method:** Review GitHub Actions security
- **Expected:** Branch protection, required reviews
- **Status:** [ ] Not tested

---

## A09: Logging & Monitoring Failures

### ST-012-037: Authentication logging
- **Verify:** Failed logins are logged
- **Method:** Attempt failed login, check logs
- **Expected:** IP, timestamp, email logged
- **Status:** [ ] Not tested

### ST-012-038: Authorization failure logging
- **Verify:** Access denied events logged
- **Method:** Attempt unauthorized access, check logs
- **Expected:** Event logged with context
- **Status:** [ ] Not tested

### ST-012-039: Audit trail
- **Verify:** Sensitive operations logged
- **Method:** Perform CRUD on resources, check audit log
- **Expected:** Who, what, when recorded
- **Status:** [ ] Not tested

---

## A10: Server-Side Request Forgery (SSRF)

### ST-012-040: SSRF via webhook URL
- **Attack:** Provide internal URL as webhook
- **Method:** `http://localhost:6379/` as callback
- **Expected:** Internal URLs blocked
- **Status:** [ ] Not tested

### ST-012-041: SSRF via media URL
- **Attack:** Import media from internal URL
- **Method:** `http://169.254.169.254/` as image URL
- **Expected:** Cloud metadata URLs blocked
- **Status:** [ ] Not tested

---

## Cross-Site Scripting (XSS)

### ST-012-042: Stored XSS - post content
- **Attack:** `<script>alert('xss')</script>` in post
- **Expected:** Escaped on display
- **Status:** [ ] Not tested

### ST-012-043: Stored XSS - user name
- **Attack:** `<img src=x onerror=alert('xss')>` as name
- **Expected:** Escaped on display
- **Status:** [ ] Not tested

### ST-012-044: Reflected XSS - search
- **Attack:** XSS payload in search parameter
- **Expected:** Escaped in response
- **Status:** [ ] Not tested

### ST-012-045: DOM XSS
- **Attack:** XSS via client-side routing
- **Expected:** Vue escapes by default
- **Status:** [ ] Not tested

---

## Cross-Site Request Forgery (CSRF)

### ST-012-046: CSRF - state-changing operations
- **Attack:** Forge request without CSRF token
- **Expected:** Request rejected
- **Status:** [ ] Not tested

### ST-012-047: CSRF token validation
- **Verify:** Token validated on all POST/PUT/DELETE
- **Method:** Submit with invalid token
- **Expected:** 419 or 403
- **Status:** [ ] Not tested

---

## API Security

### ST-012-048: API rate limiting
- **Attack:** Rapid API requests
- **Expected:** 429 after threshold
- **Status:** [ ] Not tested

### ST-012-049: API key exposure
- **Verify:** No API keys in client code
- **Method:** View page source, network requests
- **Expected:** Keys server-side only
- **Status:** [ ] Not tested

### ST-012-050: Mass assignment
- **Attack:** Include extra fields in request
- **Method:** `{"role": "OWNER"}` in profile update
- **Expected:** Extra fields ignored
- **Status:** [ ] Not tested

---

## Data Protection

### ST-012-051: PII encryption
- **Verify:** PII encrypted at rest
- **Method:** Database inspection
- **Expected:** Sensitive fields encrypted
- **Status:** [ ] Not tested

### ST-012-052: Data export completeness
- **Verify:** GDPR export includes all user data
- **Method:** Request data export
- **Expected:** All personal data included
- **Status:** [ ] Not tested

### ST-012-053: Data deletion
- **Verify:** Account deletion removes data
- **Method:** Delete account, verify database
- **Expected:** Data deleted or anonymized
- **Status:** [ ] Not tested

---

## File Upload Security

### ST-012-054: File type validation
- **Attack:** Upload .php disguised as .jpg
- **Expected:** Content-type validated, rejected
- **Status:** [ ] Not tested

### ST-012-055: File size limits
- **Attack:** Upload very large file
- **Expected:** Rejected at size limit
- **Status:** [ ] Not tested

### ST-012-056: Path traversal in filename
- **Attack:** `../../../etc/passwd` as filename
- **Expected:** Path sanitized
- **Status:** [ ] Not tested

### ST-012-057: Executable upload
- **Attack:** Upload executable file
- **Expected:** Stored without execute permission
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| A01: Access Control | 6 | - | - | 6 |
| A02: Cryptographic | 5 | - | - | 5 |
| A03: Injection | 5 | - | - | 5 |
| A04: Insecure Design | 4 | - | - | 4 |
| A05: Misconfiguration | 5 | - | - | 5 |
| A06: Vulnerable Components | 2 | - | - | 2 |
| A07: Auth Failures | 7 | - | - | 7 |
| A08: Integrity | 2 | - | - | 2 |
| A09: Logging | 3 | - | - | 3 |
| A10: SSRF | 2 | - | - | 2 |
| XSS | 4 | - | - | 4 |
| CSRF | 2 | - | - | 2 |
| API Security | 3 | - | - | 3 |
| Data Protection | 3 | - | - | 3 |
| File Upload | 4 | - | - | 4 |
| **Total** | **57** | **-** | **-** | **57** |

---

## Security Testing Tools

| Tool | Purpose |
|------|---------|
| OWASP ZAP | Automated vulnerability scanning |
| Burp Suite | Manual penetration testing |
| SQLMap | SQL injection testing |
| JWT_Tool | JWT manipulation testing |
| Nikto | Web server scanner |
| npm audit | Dependency vulnerabilities |
| composer audit | PHP dependency vulnerabilities |

---

## Incident Response

If critical security vulnerability found:
1. **STOP** all testing immediately
2. **DOCUMENT** the vulnerability with minimal detail
3. **REPORT** to security team immediately
4. **DO NOT** exploit beyond proof of concept
5. **DO NOT** share vulnerability details

---

**Last Updated:** February 2026
**Status:** Draft
**Classification:** Security Critical
