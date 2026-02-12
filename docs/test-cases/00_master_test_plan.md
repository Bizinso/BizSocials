# BizSocials — Master Test Plan

**Version:** 1.0
**Date:** February 2026
**Author:** QA Lead (Claude)
**Status:** Active

---

## 1. Test Plan Overview

### 1.1 Purpose

This document defines the comprehensive testing strategy for BizSocials Phase-1. It establishes test objectives, scope, approach, and criteria for success.

### 1.2 Scope

| In Scope | Out of Scope |
|----------|--------------|
| All Phase-1 features | Phase-2+ features |
| API endpoints (75) | Mobile native apps |
| Frontend components | Social listening |
| Multi-tenant isolation | Additional platforms (X, TikTok) |
| Security testing | Load/stress testing (separate) |

---

## 2. Test Strategy

### 2.1 Testing Pyramid

```
┌─────────────────────────────────────────────────────────────────┐
│                      TESTING APPROACH                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                         ▲                                       │
│                        /│\         E2E Tests (10%)              │
│                       / │ \        Playwright                   │
│                      /  │  \       ~20 critical flows           │
│                     /   │   \                                   │
│                    ─────┼─────                                  │
│                   /     │     \    Integration Tests (30%)      │
│                  /      │      \   PHPUnit Feature              │
│                 /       │       \  ~150 API tests               │
│                /        │        \                              │
│               ──────────┼──────────                             │
│              /          │          \   Unit Tests (60%)         │
│             /           │           \  PHPUnit + Vitest         │
│            /            │            \ ~300 tests               │
│           ─────────────────────────────                         │
│                                                                 │
│  Target Coverage: 80% overall, 95% critical paths               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Test Types

| Type | Tool | Responsibility | Frequency |
|------|------|----------------|-----------|
| Unit Tests | PHPUnit, Vitest | Codex writes, Claude reviews | Every commit |
| Integration Tests | PHPUnit Feature | Codex writes, Claude reviews | Every commit |
| E2E Tests | Playwright | Codex writes, Claude specifies | Before merge |
| Manual Tests | Checklist | Claude executes | Before milestone |
| Security Tests | Custom + OWASP ZAP | Claude designs/executes | Before release |
| Smoke Tests | Automated | CI/CD | Every deployment |

---

## 3. Test Case Index

### 3.1 Test Case Documents

| ID | Document | Domain | Priority | Status |
|----|----------|--------|----------|--------|
| TC-001 | [Authentication](./TC-001-authentication.md) | Identity | Critical | Draft |
| TC-002 | [Workspaces](./TC-002-workspaces.md) | Workspace | Critical | Draft |
| TC-003 | [Team Management](./TC-003-team-management.md) | Workspace | High | Draft |
| TC-004 | [Social Accounts](./TC-004-social-accounts.md) | Social | Critical | Draft |
| TC-005 | [Posts & Scheduling](./TC-005-posts.md) | Content | Critical | Draft |
| TC-006 | [Approvals](./TC-006-approvals.md) | Content | High | Draft |
| TC-007 | [Inbox & Replies](./TC-007-inbox.md) | Engagement | High | Draft |
| TC-008 | [Analytics](./TC-008-analytics.md) | Analytics | Medium | Draft |
| TC-009 | [Billing](./TC-009-billing.md) | Billing | Critical | Draft |
| TC-010 | [AI Assist](./TC-010-ai-assist.md) | AI | Medium | Draft |
| TC-011 | [Multi-Tenancy](./TC-011-multi-tenancy.md) | Security | Critical | Draft |
| TC-012 | [Security](./TC-012-security.md) | Security | Critical | Draft |

### 3.2 Test Case Counts (Actual)

| Domain | Unit | Integration | Job/Webhook | E2E | Manual | Security | Total |
|--------|:----:|:-----------:|:-----------:|:---:|:------:|:--------:|:-----:|
| Authentication | 10 | 15 | - | 3 | 5 | 5 | 38 |
| Workspaces | 10 | 12 | - | 2 | 4 | 3 | 31 |
| Team Management | 8 | 20 | - | 2 | 3 | 3 | 36 |
| Social Accounts | 10 | 17 | 3 | 2 | 5 | 5 | 42 |
| Posts | 12 | 25 | 6 | 5 | 8 | 5 | 61 |
| Approvals | 8 | 15 | - | 2 | 4 | 3 | 32 |
| Inbox | 8 | 18 | 5 | 3 | 5 | 3 | 42 |
| Analytics | 6 | 13 | 4 | 1 | 3 | 2 | 29 |
| Billing | 8 | 15 | 6 | 3 | 5 | 5 | 42 |
| AI Assist | 6 | 10 | - | 1 | 3 | 3 | 23 |
| Multi-Tenancy | 5 | 15 | 4 | 2 | 10 | 5 | 41 |
| Security | - | - | - | - | - | 57 | 57 |
| **Total** | **91** | **175** | **28** | **26** | **55** | **99** | **474** |

---

## 4. Test Environments

### 4.1 Environment Matrix

| Environment | Purpose | Data | Access |
|-------------|---------|------|--------|
| Local | Development | Seeded | Developer |
| CI | Automated tests | Fresh per run | CI system |
| Staging | Integration + Manual | Sanitized copy | QA + Dev |
| Production | Smoke tests only | Real | Limited |

### 4.2 Test Data Strategy

```
┌─────────────────────────────────────────────────────────────────┐
│                      TEST DATA                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  WORKSPACE A ("Acme Agency")                                    │
│  ├── Owner: owner@acme.test                                     │
│  ├── Admin: admin@acme.test                                     │
│  ├── Editor: editor@acme.test                                   │
│  ├── Viewer: viewer@acme.test                                   │
│  ├── Social Accounts:                                           │
│  │   ├── LinkedIn: Acme LinkedIn Page                           │
│  │   ├── Facebook: Acme Facebook Page                           │
│  │   └── Instagram: @acme_official                              │
│  ├── Posts: 50 (various statuses)                               │
│  └── Inbox Items: 100                                           │
│                                                                 │
│  WORKSPACE B ("Beta Brand")                                     │
│  ├── Owner: owner@beta.test                                     │
│  ├── Editor: editor@beta.test                                   │
│  ├── Social Accounts:                                           │
│  │   └── LinkedIn: Beta LinkedIn Page                           │
│  └── Posts: 20                                                  │
│                                                                 │
│  SHARED USER (Multi-workspace)                                  │
│  └── shared@test.com (Editor in A, Viewer in B)                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Entry & Exit Criteria

### 5.1 Test Entry Criteria

Testing can begin when:

- [ ] Feature code is complete
- [ ] Code compiles without errors
- [ ] Unit tests written by developer
- [ ] Test environment available
- [ ] Test data seeded

### 5.2 Test Exit Criteria

Testing is complete when:

- [ ] All planned tests executed
- [ ] 95% of critical path tests pass
- [ ] 90% of high priority tests pass
- [ ] No critical bugs open
- [ ] No high bugs open (or approved exceptions)
- [ ] Code coverage ≥ 80%
- [ ] Security tests pass

---

## 6. Defect Management

### 6.1 Severity Definitions

| Severity | Definition | Example |
|----------|------------|---------|
| **Critical** | System unusable, data loss | Login broken, data leaking |
| **High** | Major feature broken | Cannot create posts |
| **Medium** | Feature works with issues | UI glitch, slow response |
| **Low** | Minor cosmetic issue | Typo, alignment |

### 6.2 Bug Lifecycle

```
NEW → ASSIGNED → IN PROGRESS → FIXED → VERIFIED → CLOSED
                      ↓
                   REOPENED
```

### 6.3 Bug Resolution SLA

| Severity | Fix Target | Verify Target |
|----------|------------|---------------|
| Critical | 4 hours | Same day |
| High | 24 hours | 48 hours |
| Medium | 3 days | 5 days |
| Low | Sprint end | Sprint end |

---

## 7. Risk Assessment

### 7.1 Testing Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Insufficient test coverage | Bugs in production | Enforce 80% coverage gate |
| Flaky tests | False failures | Fix immediately when found |
| Test environment issues | Blocked testing | Maintain backup environment |
| Time pressure | Skipped tests | Prioritize critical paths |
| Missing test data | Incomplete testing | Comprehensive seed data |

### 7.2 Critical Test Areas

| Area | Risk Level | Extra Testing |
|------|------------|---------------|
| Multi-tenant isolation | Critical | Dedicated test suite |
| Payment processing | Critical | Manual + automated |
| OAuth token handling | Critical | Security review |
| Post publishing | High | End-to-end validation |
| Data migrations | High | Rollback testing |

---

## 8. Test Execution Schedule

### 8.1 Per Milestone Testing

| Milestone | Features | Test Days | Sign-off |
|-----------|----------|-----------|----------|
| M1: Foundation | Auth, Workspace | 2 days | Claude |
| M2: Social | OAuth, Accounts | 2 days | Claude |
| M3: Content | Posts, Media | 3 days | Claude |
| M4: Publishing | Schedule, Publish | 2 days | Claude |
| M5: Inbox | Comments, Replies | 2 days | Claude |
| M6: Analytics | Metrics, Reports | 2 days | Claude |
| M7: Billing | Stripe, Plans | 3 days | Claude |
| M8: AI | Suggestions | 1 day | Claude |
| M9: Polish | Bug fixes | 2 days | Claude |
| M10: Launch | Final QA | 3 days | Claude |

### 8.2 Daily Test Activities

| Time | Activity |
|------|----------|
| AM | Review overnight CI results |
| AM | Execute manual tests for completed features |
| PM | Review Codex completion reports |
| PM | Verify fixes, update test status |
| EOD | Report test summary |

---

## 9. Deliverables

### 9.1 Test Artifacts

| Artifact | Format | Frequency |
|----------|--------|-----------|
| Test Cases | Markdown | Per feature |
| Test Results | CI output | Per run |
| Bug Reports | Markdown | As found |
| Coverage Report | HTML/JSON | Daily |
| Test Summary | Markdown | Per milestone |

### 9.2 Reporting

**Daily:** CI test results, new bugs
**Weekly:** Coverage trends, bug aging
**Milestone:** Full test report, sign-off

---

## 10. Approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| QA Lead | Claude | Feb 2026 | ✓ |
| Project Owner | Punit | - | Pending |

---

**END OF MASTER TEST PLAN**
