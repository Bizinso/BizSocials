# BizSocials — Development Workflow

**Version:** 1.0
**Date:** February 2026
**Purpose:** Defines collaboration between Claude (Architect/QA) and Codex (Developer)

---

## 1. Team Structure

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           TEAM STRUCTURE                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  CLAUDE (Solution Architect + QA Lead)                                      │
│  ├── Solution Architecture                                                  │
│  │   └── System design, API contracts, data models                          │
│  ├── Database Architecture                                                  │
│  │   └── Schema design, migrations review, query optimization               │
│  ├── UI/UX Design                                                           │
│  │   └── Wireframes, component specs, user flows                            │
│  ├── QA Lead (Manual + Automated)                                           │
│  │   └── Test cases, test plans, verification, bug reports                  │
│  ├── DevOps Guidance                                                        │
│  │   └── CI/CD review, deployment verification                              │
│  └── Code Review                                                            │
│      └── Architecture compliance, security, best practices                  │
│                                                                             │
│  CODEX (Development Team)                                                   │
│  ├── Backend Development                                                    │
│  │   └── Laravel API implementation                                         │
│  ├── Frontend Development                                                   │
│  │   └── Vue 3 + TypeScript implementation                                  │
│  ├── Database Migrations                                                    │
│  │   └── Execute migration scripts from specs                               │
│  ├── Unit Tests                                                             │
│  │   └── Write tests per specifications                                     │
│  └── Bug Fixes                                                              │
│      └── Fix issues from QA reports                                         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Development Workflow

### 2.1 Task Assignment Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          DEVELOPMENT CYCLE                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. SPECIFICATION (Claude)                                                  │
│     │                                                                       │
│     ├── Create detailed task specification                                  │
│     ├── Define acceptance criteria                                          │
│     ├── Provide code examples/patterns                                      │
│     └── List test cases to implement                                        │
│     │                                                                       │
│     ▼                                                                       │
│  2. IMPLEMENTATION (Codex)                                                  │
│     │                                                                       │
│     ├── Implement feature per specification                                 │
│     ├── Write unit tests                                                    │
│     ├── Document code                                                       │
│     └── Report completion                                                   │
│     │                                                                       │
│     ▼                                                                       │
│  3. VERIFICATION (Claude)                                                   │
│     │                                                                       │
│     ├── Review code in local repository                                     │
│     ├── Run tests and verify coverage                                       │
│     ├── Execute manual test cases                                           │
│     ├── Check architecture compliance                                       │
│     └── Verify security requirements                                        │
│     │                                                                       │
│     ▼                                                                       │
│  4. FEEDBACK LOOP                                                           │
│     │                                                                       │
│     ├── If PASS: Mark task complete, move to next                          │
│     └── If FAIL: Create bug report, return to step 2                       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Task Specification Template

When I assign tasks to Codex, I will use this format:

```markdown
## Task: [BIZ-XXX] Task Title

### Context
Brief description of why this task is needed and how it fits into the system.

### Requirements
1. Requirement 1
2. Requirement 2
3. Requirement 3

### Acceptance Criteria
- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

### Technical Specifications

**Files to Create/Modify:**
- `path/to/file1.php` - Description
- `path/to/file2.vue` - Description

**API Endpoint (if applicable):**
- Method: POST
- Path: /v1/workspaces/{workspace_id}/resource
- Request body: { ... }
- Response: { ... }

**Database Changes (if applicable):**
- Migration: Create table X with columns Y, Z

### Code Patterns to Follow
Reference existing files or provide code examples.

### Test Cases to Implement
1. Test case 1 description
2. Test case 2 description
3. Test case 3 description

### Dependencies
- Depends on: [BIZ-XXX] (if any)
- Blocks: [BIZ-XXX] (if any)

### Notes
Any additional context or warnings.
```

---

## 3. My Verification Process

### 3.1 Repository Check Procedure

After Codex reports task completion, I will:

```
┌─────────────────────────────────────────────────────────────────┐
│                  VERIFICATION CHECKLIST                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  STEP 1: CODE REVIEW                                            │
│  ☐ Read all new/modified files                                  │
│  ☐ Verify architecture compliance                               │
│  ☐ Check coding standards (PSR-12 / Vue style guide)            │
│  ☐ Verify workspace tenancy is enforced                         │
│  ☐ Check for security vulnerabilities                           │
│  ☐ Ensure no hardcoded values/secrets                           │
│                                                                 │
│  STEP 2: TEST VERIFICATION                                      │
│  ☐ Read test files                                              │
│  ☐ Verify tests cover acceptance criteria                       │
│  ☐ Check test coverage percentage                               │
│  ☐ Verify edge cases are tested                                 │
│  ☐ Ensure multi-tenant isolation tests exist                    │
│                                                                 │
│  STEP 3: DOCUMENTATION CHECK                                    │
│  ☐ API documentation updated                                    │
│  ☐ Code comments present for complex logic                      │
│  ☐ Type hints/interfaces defined                                │
│                                                                 │
│  STEP 4: INTEGRATION CHECK                                      │
│  ☐ Verify integration with existing code                        │
│  ☐ Check for breaking changes                                   │
│  ☐ Ensure backward compatibility                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Verification Commands I Will Use

```bash
# Check file changes
git status
git diff

# Read new/modified files
# (Using Claude's Read tool)

# Run tests (I'll ask Codex or verify test output)
php artisan test --coverage
npm run test:unit

# Check code style
./vendor/bin/php-cs-fixer fix --dry-run --diff
npm run lint
```

---

## 4. Test Case Management

### 4.1 Test Case Categories

| Category | Responsibility | Tool |
|----------|---------------|------|
| Unit Tests | Codex writes, Claude reviews | PHPUnit, Vitest |
| Integration Tests | Codex writes, Claude reviews | PHPUnit Feature tests |
| API Tests | Claude specifies, Codex implements | PHPUnit |
| E2E Tests | Claude specifies, Codex implements | Playwright |
| Manual Tests | Claude executes | Checklist |
| Security Tests | Claude designs, verifies | Custom |

### 4.2 Test Case Documentation Structure

I will maintain test case documentation in:

```
/docs/test-cases/
├── TC-001-authentication.md
├── TC-002-workspaces.md
├── TC-003-social-accounts.md
├── TC-004-posts.md
├── TC-005-approvals.md
├── TC-006-inbox.md
├── TC-007-analytics.md
├── TC-008-billing.md
├── TC-009-ai-assist.md
├── TC-010-multi-tenancy.md
└── TC-011-security.md
```

### 4.3 Test Case Template

```markdown
# TC-XXX: Feature Name Test Cases

## Overview
- Feature: [Feature name]
- Related Docs: [Link to spec]
- Priority: High/Medium/Low

---

## Unit Tests (Codex to implement)

### UT-XXX-001: Test name
- **Description:** What this test verifies
- **Preconditions:** Setup required
- **Test Steps:**
  1. Step 1
  2. Step 2
- **Expected Result:** What should happen
- **Status:** [ ] Pending / [ ] Implemented / [ ] Passing

---

## Integration Tests (Codex to implement)

### IT-XXX-001: Test name
- **Description:** What this test verifies
- **API Endpoint:** POST /v1/...
- **Test Data:** { ... }
- **Expected Response:** { ... }
- **Status:** [ ] Pending / [ ] Implemented / [ ] Passing

---

## Manual Tests (Claude to execute)

### MT-XXX-001: Test name
- **Description:** What this test verifies
- **Preconditions:** Setup required
- **Test Steps:**
  1. Step 1
  2. Step 2
- **Expected Result:** What should observe
- **Status:** [ ] Not tested / [ ] Pass / [ ] Fail

---

## Security Tests (Claude to verify)

### ST-XXX-001: Test name
- **Description:** Security aspect being tested
- **Attack Vector:** What we're testing against
- **Test Method:** How to test
- **Expected Result:** Attack should fail
- **Status:** [ ] Not tested / [ ] Pass / [ ] Fail
```

---

## 5. Bug Report Process

### 5.1 Bug Report Template

When I find issues during verification:

```markdown
## Bug Report: [BUG-XXX] Title

### Severity
- [ ] Critical (blocks release)
- [ ] High (major feature broken)
- [ ] Medium (feature works but has issues)
- [ ] Low (minor issue, cosmetic)

### Environment
- Branch: feature/xxx
- Commit: abc123

### Description
Clear description of the bug.

### Steps to Reproduce
1. Step 1
2. Step 2
3. Step 3

### Expected Behavior
What should happen.

### Actual Behavior
What actually happens.

### Evidence
- Error message: "..."
- File: `path/to/file.php:123`
- Test failure: `test_xxx`

### Suggested Fix
If I have a suggestion for how to fix it.

### Related
- Task: BIZ-XXX
- Test Case: TC-XXX-XXX
```

---

## 6. Communication Protocol

### 6.1 Task Assignment Format

When assigning to Codex:

```
@Codex - Task Assignment

Task ID: BIZ-XXX
Priority: High/Medium/Low
Estimated Complexity: Simple/Moderate/Complex

[Full task specification using template above]

Please confirm understanding before starting.
```

### 6.2 Completion Report Format (Expected from Codex)

```
@Claude - Task Completion Report

Task ID: BIZ-XXX
Status: Complete / Partial / Blocked

Files Created/Modified:
- path/to/file1.php (created)
- path/to/file2.vue (modified)

Tests Added:
- tests/Feature/XxxTest.php
- tests/Unit/XxxTest.php

Test Results:
- All tests passing: Yes/No
- Coverage: XX%

Notes:
Any implementation decisions or concerns.

Ready for review: Yes/No
```

### 6.3 Review Feedback Format

My feedback to Codex:

```
@Codex - Review Feedback

Task ID: BIZ-XXX
Review Status: Approved / Changes Requested

## If Approved:
Task is complete. Moving to next task.

## If Changes Requested:

### Issue 1: [Title]
- File: path/to/file.php
- Line: 123
- Problem: Description
- Required Fix: What needs to change

### Issue 2: [Title]
...

Please address these issues and report back.
```

---

## 7. Quality Gates

### 7.1 Before Task Starts

| Gate | Responsibility | Check |
|------|---------------|-------|
| Spec Complete | Claude | All sections filled |
| Dependencies Met | Claude | Blocking tasks complete |
| Test Cases Defined | Claude | At least basic cases listed |

### 7.2 Before Task Approval

| Gate | Responsibility | Check |
|------|---------------|-------|
| Code Implemented | Codex | All requirements met |
| Tests Written | Codex | Coverage > 80% |
| Tests Passing | Codex | All tests green |
| Code Reviewed | Claude | No critical issues |
| Security Checked | Claude | No vulnerabilities |
| Docs Updated | Codex | API docs current |

### 7.3 Before Milestone Complete

| Gate | Responsibility | Check |
|------|---------------|-------|
| All Tasks Complete | Claude | Verify in task list |
| Integration Tests Pass | Claude | Run full suite |
| Manual Tests Pass | Claude | Execute checklist |
| No Critical Bugs | Claude | Bug list empty |
| Performance Acceptable | Claude | Response times OK |

---

## 8. File Organization Expectations

### 8.1 Backend (Laravel)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/     # Controllers by domain
│   │   ├── Requests/               # Form validation
│   │   └── Resources/              # API transformers
│   ├── Models/                     # Eloquent models
│   ├── Services/                   # Business logic
│   ├── Repositories/               # Data access
│   ├── Jobs/                       # Queue jobs
│   └── Policies/                   # Authorization
├── database/
│   ├── migrations/                 # Schema changes
│   ├── factories/                  # Test data factories
│   └── seeders/                    # Seed data
├── routes/
│   └── api.php                     # API routes
└── tests/
    ├── Feature/                    # Integration tests
    └── Unit/                       # Unit tests
```

### 8.2 Frontend (Vue 3)

```
frontend/
├── src/
│   ├── components/                 # Reusable components
│   │   ├── common/                 # Shared components
│   │   └── [domain]/               # Domain-specific
│   ├── pages/                      # Route pages
│   ├── composables/                # Composition functions
│   ├── services/                   # API services
│   ├── stores/                     # Pinia stores
│   ├── types/                      # TypeScript types
│   └── utils/                      # Utilities
└── tests/
    ├── unit/                       # Unit tests
    └── e2e/                        # End-to-end tests
```

---

## 9. Sprint Planning Integration

### 9.1 Sprint Task Breakdown

For each sprint, I will:

1. **Review milestone requirements**
2. **Break down into implementable tasks**
3. **Prioritize by dependencies**
4. **Assign to Codex in sequence**
5. **Track progress through completion**

### 9.2 Daily Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                     DAILY WORKFLOW                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Morning:                                                       │
│  1. Review any pending Codex completion reports                 │
│  2. Verify completed work in repository                         │
│  3. Provide feedback or approve                                 │
│                                                                 │
│  Midday:                                                        │
│  4. Assign next tasks to Codex                                  │
│  5. Answer any Codex questions                                  │
│                                                                 │
│  Afternoon:                                                     │
│  6. Review completed work                                       │
│  7. Execute manual tests if needed                              │
│  8. Update task status                                          │
│                                                                 │
│  End of Day:                                                    │
│  9. Summarize progress                                          │
│  10. Plan next day's tasks                                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 10. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Claude | Initial workflow document |

---

**END OF DEVELOPMENT WORKFLOW**
