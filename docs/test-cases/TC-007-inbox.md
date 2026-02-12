# TC-007: Inbox & Replies Test Cases

**Feature:** Social Engagement Inbox
**Priority:** High
**Related Docs:** [API Contract - Inbox](../04_phase1_api_contract.md)

---

## Overview

Tests for social inbox functionality including comments, mentions, replies, and engagement management. Phase-1 covers comments and mentions only (no DMs or story replies).

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── Social Accounts:
│   ├── social-a1: LinkedIn Page
│   ├── social-a2: Facebook Page
│   └── social-a3: Instagram Business
│
├── Inbox Items:
│   ├── inbox-a1: LinkedIn comment (UNREAD)
│   ├── inbox-a2: Facebook comment (READ)
│   ├── inbox-a3: Instagram mention (UNREAD)
│   ├── inbox-a4: LinkedIn comment (ARCHIVED)
│   └── inbox-a5: Facebook comment (REPLIED)
│
└── Members:
    ├── Owner: alice@acme.test
    ├── Editor: editor@acme.test
    └── Viewer: viewer@acme.test

WORKSPACE B
└── Inbox Items: inbox-b1 (different workspace)
```

---

## Unit Tests (Codex to implement)

### UT-007-001: InboxItem type validation
- **File:** `tests/Unit/Models/InboxItemTest.php`
- **Description:** Verify valid types (COMMENT, MENTION, REPLY)
- **Status:** [ ] Pending

### UT-007-002: InboxItem status transitions
- **File:** `tests/Unit/Models/InboxItemTest.php`
- **Description:** Verify valid statuses (UNREAD, READ, REPLIED, ARCHIVED)
- **Test Pattern:**
```php
public function test_unread_can_transition_to_read(): void
{
    $item = InboxItem::factory()->unread()->create();
    $item->markAsRead();
    $this->assertEquals('READ', $item->status);
}
```
- **Status:** [ ] Pending

### UT-007-003: InboxItem workspace scoping
- **File:** `tests/Unit/Models/InboxItemTest.php`
- **Description:** Verify inbox items are workspace-scoped
- **Status:** [ ] Pending

### UT-007-004: InboxItem social account relationship
- **File:** `tests/Unit/Models/InboxItemTest.php`
- **Description:** Verify relationship to social account
- **Status:** [ ] Pending

### UT-007-005: Reply content validation
- **File:** `tests/Unit/Models/InboxReplyTest.php`
- **Description:** Verify reply content length limits per platform
- **Status:** [ ] Pending

### UT-007-006: External ID uniqueness
- **File:** `tests/Unit/Models/InboxItemTest.php`
- **Description:** Verify external_id + platform is unique
- **Status:** [ ] Pending

### UT-007-007: Inbox item author data
- **File:** `tests/Unit/Models/InboxItemTest.php`
- **Description:** Verify author name/profile stored correctly
- **Status:** [ ] Pending

### UT-007-008: Inbox unread count calculation
- **File:** `tests/Unit/Services/InboxServiceTest.php`
- **Description:** Verify accurate unread count per workspace
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-007-001: List inbox items
- **File:** `tests/Feature/Api/V1/Inbox/ListInboxTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox`
- **Expected:** 200 OK, paginated list of inbox items
- **Status:** [ ] Pending

### IT-007-002: List inbox - filter by status
- **File:** `tests/Feature/Api/V1/Inbox/ListInboxTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox?status=UNREAD`
- **Expected:** 200 OK, only unread items
- **Status:** [ ] Pending

### IT-007-003: List inbox - filter by platform
- **File:** `tests/Feature/Api/V1/Inbox/ListInboxTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox?platform=LINKEDIN`
- **Expected:** 200 OK, only LinkedIn items
- **Status:** [ ] Pending

### IT-007-004: List inbox - filter by type
- **File:** `tests/Feature/Api/V1/Inbox/ListInboxTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox?type=MENTION`
- **Expected:** 200 OK, only mentions
- **Status:** [ ] Pending

### IT-007-005: List inbox - non-member forbidden
- **File:** `tests/Feature/Api/V1/Inbox/ListInboxTest.php`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-007-006: Get inbox item details
- **File:** `tests/Feature/Api/V1/Inbox/GetInboxItemTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox/{item_id}`
- **Expected:** 200 OK, full item details with thread
- **Status:** [ ] Pending

### IT-007-007: Get inbox item - cross-workspace forbidden
- **File:** `tests/Feature/Api/V1/Inbox/GetInboxItemTest.php`
- **Setup:** Request Workspace B item from Workspace A
- **Expected:** 404 Not Found
- **Status:** [ ] Pending

### IT-007-008: Mark item as read
- **File:** `tests/Feature/Api/V1/Inbox/MarkReadTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/inbox/{item_id}/read`
- **Expected:** 200 OK, status = READ
- **Status:** [ ] Pending

### IT-007-009: Mark multiple items as read
- **File:** `tests/Feature/Api/V1/Inbox/MarkReadTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/inbox/bulk-read`
- **Request:** `{ "item_ids": ["inbox-a1", "inbox-a3"] }`
- **Expected:** 200 OK, all items marked read
- **Status:** [ ] Pending

### IT-007-010: Reply to inbox item - editor
- **File:** `tests/Feature/Api/V1/Inbox/ReplyTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/inbox/{item_id}/reply`
- **Request:** `{ "content": "Thanks for your feedback!" }`
- **Expected:** 201 Created, reply queued for posting
- **Status:** [ ] Pending

### IT-007-011: Reply to inbox item - viewer forbidden
- **File:** `tests/Feature/Api/V1/Inbox/ReplyTest.php`
- **Setup:** Viewer tries to reply
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-007-012: Reply - content too long
- **File:** `tests/Feature/Api/V1/Inbox/ReplyTest.php`
- **Request:** Content exceeds platform limit
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-007-013: Archive inbox item
- **File:** `tests/Feature/Api/V1/Inbox/ArchiveTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/inbox/{item_id}/archive`
- **Expected:** 200 OK, status = ARCHIVED
- **Status:** [ ] Pending

### IT-007-014: Unarchive inbox item
- **File:** `tests/Feature/Api/V1/Inbox/ArchiveTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/inbox/{item_id}/unarchive`
- **Expected:** 200 OK, status restored to previous
- **Status:** [ ] Pending

### IT-007-015: Get inbox unread count
- **File:** `tests/Feature/Api/V1/Inbox/UnreadCountTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox/unread-count`
- **Expected:** 200 OK, `{ "count": 2 }`
- **Status:** [ ] Pending

### IT-007-016: Get inbox unread count by platform
- **File:** `tests/Feature/Api/V1/Inbox/UnreadCountTest.php`
- **Expected:** 200 OK with breakdown by platform
- **Status:** [ ] Pending

### IT-007-017: Delete inbox item
- **File:** `tests/Feature/Api/V1/Inbox/DeleteInboxItemTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}/inbox/{item_id}`
- **Expected:** 200 OK, item soft deleted
- **Status:** [ ] Pending

### IT-007-018: Search inbox items
- **File:** `tests/Feature/Api/V1/Inbox/SearchInboxTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/inbox?search=question`
- **Expected:** 200 OK, items matching search term
- **Status:** [ ] Pending

---

## Background Job Tests (Codex to implement)

### JT-007-001: SyncInboxJob - fetches new items
- **File:** `tests/Feature/Jobs/SyncInboxJobTest.php`
- **Description:** Verify job fetches new comments/mentions from platforms
- **Status:** [ ] Pending

### JT-007-002: SyncInboxJob - no duplicates
- **File:** `tests/Feature/Jobs/SyncInboxJobTest.php`
- **Description:** Verify existing items not duplicated
- **Status:** [ ] Pending

### JT-007-003: SyncInboxJob - workspace scoped
- **File:** `tests/Feature/Jobs/SyncInboxJobTest.php`
- **Description:** Verify items created for correct workspace
- **Status:** [ ] Pending

### JT-007-004: PostReplyJob - success
- **File:** `tests/Feature/Jobs/PostReplyJobTest.php`
- **Description:** Verify reply posted to platform
- **Status:** [ ] Pending

### JT-007-005: PostReplyJob - failure handling
- **File:** `tests/Feature/Jobs/PostReplyJobTest.php`
- **Description:** Verify failure marked and user notified
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-007-001: View and reply to comment
- **File:** `tests/e2e/inbox/reply-flow.spec.ts`
- **Steps:**
  1. Login as editor
  2. Navigate to Inbox
  3. Click on unread comment
  4. Verify marked as read
  5. Type reply
  6. Submit reply
  7. Verify reply status shown
- **Status:** [ ] Pending

### E2E-007-002: Filter and search inbox
- **File:** `tests/e2e/inbox/filter-search.spec.ts`
- **Steps:**
  1. View inbox
  2. Filter by platform
  3. Verify filtered results
  4. Search for term
  5. Verify search results
- **Status:** [ ] Pending

### E2E-007-003: Bulk archive
- **File:** `tests/e2e/inbox/bulk-actions.spec.ts`
- **Steps:**
  1. Select multiple inbox items
  2. Click bulk archive
  3. Verify items moved to archive
  4. View archive
  5. Verify items present
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-007-001: Real-time inbox sync
- **Steps:**
  1. Post comment on connected LinkedIn page
  2. Trigger inbox sync (or wait)
  3. Verify comment appears in inbox
  4. Verify author info correct
- **Status:** [ ] Not tested

### MT-007-002: Real reply posting
- **Steps:**
  1. Reply to a real comment via inbox
  2. Check platform to verify reply posted
  3. Verify reply appears in thread
- **Status:** [ ] Not tested

### MT-007-003: Notification badge accuracy
- **Steps:**
  1. Note current unread count
  2. Receive new comment
  3. Verify badge increments
  4. Mark as read
  5. Verify badge decrements
- **Status:** [ ] Not tested

### MT-007-004: Thread conversation view
- **Steps:**
  1. Find comment with replies
  2. View thread
  3. Verify conversation hierarchy correct
  4. Verify all participants shown
- **Status:** [ ] Not tested

### MT-007-005: Multi-platform inbox aggregation
- **Steps:**
  1. Have items from LinkedIn, Facebook, Instagram
  2. View unified inbox
  3. Verify all platforms represented
  4. Filter by each platform
  5. Verify filtering works
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-007-001: Reply to cross-workspace item
- **Attack:** Reply to inbox item from different workspace
- **Expected:** 404 Not Found
- **Status:** [ ] Not tested

### ST-007-002: XSS in inbox item display
- **Attack:** Malicious content in comment from platform
- **Expected:** Properly escaped on display
- **Status:** [ ] Not tested

### ST-007-003: Reply content injection
- **Attack:** Include HTML/scripts in reply
- **Expected:** Content sanitized before posting
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 8 | - | - | 8 |
| Integration | 18 | - | - | 18 |
| Job Tests | 5 | - | - | 5 |
| E2E | 3 | - | - | 3 |
| Manual | 5 | - | - | 5 |
| Security | 3 | - | - | 3 |
| **Total** | **42** | **-** | **-** | **42** |

---

**Last Updated:** February 2026
**Status:** Draft
