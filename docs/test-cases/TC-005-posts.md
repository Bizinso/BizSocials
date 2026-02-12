# TC-005: Posts & Scheduling Test Cases

**Feature:** Post Creation, Scheduling, and Publishing
**Priority:** Critical
**Related Docs:** [API Contract - Posts](../04_phase1_api_contract.md)

---

## Overview

Tests for post CRUD operations, media handling, scheduling, multi-platform publishing, and post lifecycle management. This is the core content engine of BizSocials.

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── Social Accounts:
│   ├── social-a1: LinkedIn Company Page (ACTIVE)
│   ├── social-a2: Facebook Page (ACTIVE)
│   └── social-a3: Instagram Business (ACTIVE)
│
├── Posts:
│   ├── post-draft: Draft post (no targets)
│   ├── post-scheduled: Scheduled for future
│   ├── post-pending: Pending approval
│   ├── post-published: Already published
│   └── post-failed: Failed to publish
│
└── Members:
    ├── Owner: alice@acme.test
    ├── Admin: admin@acme.test
    ├── Editor: editor@acme.test (approval required)
    └── Viewer: viewer@acme.test

WORKSPACE B
└── Posts: post-b1, post-b2
```

---

## Unit Tests (Codex to implement)

### UT-005-001: Post status validation
- **File:** `tests/Unit/Models/PostTest.php`
- **Description:** Verify valid statuses (DRAFT, SCHEDULED, PENDING_APPROVAL, APPROVED, PUBLISHING, PUBLISHED, FAILED)
- **Status:** [ ] Pending

### UT-005-002: Post status transitions
- **File:** `tests/Unit/Models/PostTest.php`
- **Description:** Verify valid status transitions
- **Test Pattern:**
```php
public function test_draft_can_transition_to_scheduled(): void
{
    $post = Post::factory()->draft()->create();
    $post->schedule(now()->addDay());
    $this->assertEquals('SCHEDULED', $post->status);
}

public function test_published_cannot_transition_to_draft(): void
{
    $post = Post::factory()->published()->create();
    $this->expectException(InvalidStateTransitionException::class);
    $post->transitionTo('DRAFT');
}
```
- **Status:** [ ] Pending

### UT-005-003: Post content length validation per platform
- **File:** `tests/Unit/Models/PostTest.php`
- **Description:** Verify LinkedIn (3000), Facebook (63206), Instagram (2200) limits
- **Status:** [ ] Pending

### UT-005-004: Scheduled time must be in future
- **File:** `tests/Unit/Requests/CreatePostRequestTest.php`
- **Description:** Verify scheduled_at cannot be in the past
- **Status:** [ ] Pending

### UT-005-005: Post requires at least one target
- **File:** `tests/Unit/Services/PostServiceTest.php`
- **Description:** Verify scheduling requires target social accounts
- **Status:** [ ] Pending

### UT-005-006: Media attachment validation
- **File:** `tests/Unit/Models/PostMediaTest.php`
- **Description:** Verify media type and size limits
- **Status:** [ ] Pending

### UT-005-007: Post workspace scoping
- **File:** `tests/Unit/Models/PostTest.php`
- **Description:** Verify posts are workspace-scoped
- **Status:** [ ] Pending

### UT-005-008: PostTarget relationship
- **File:** `tests/Unit/Models/PostTargetTest.php`
- **Description:** Verify post-to-social-account targeting
- **Status:** [ ] Pending

### UT-005-009: Platform-specific content accessor
- **File:** `tests/Unit/Models/PostTest.php`
- **Description:** Verify content can be customized per platform
- **Status:** [ ] Pending

### UT-005-010: Hashtag extraction
- **File:** `tests/Unit/Services/PostServiceTest.php`
- **Description:** Verify hashtags are extracted and stored
- **Status:** [ ] Pending

### UT-005-011: Post author tracking
- **File:** `tests/Unit/Models/PostTest.php`
- **Description:** Verify created_by_user_id is set
- **Status:** [ ] Pending

### UT-005-012: Calendar date filtering
- **File:** `tests/Unit/Repositories/PostRepositoryTest.php`
- **Description:** Verify date range queries work correctly
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-005-001: Create draft post
- **File:** `tests/Feature/Api/V1/Posts/CreatePostTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts`
- **Request:**
```json
{
  "content": "Check out our latest update!",
  "status": "DRAFT"
}
```
- **Expected:** 201 Created, post in DRAFT status
- **Status:** [ ] Pending

### IT-005-002: Create and schedule post
- **File:** `tests/Feature/Api/V1/Posts/CreatePostTest.php`
- **Request:**
```json
{
  "content": "Scheduled post content",
  "scheduled_at": "2026-03-01T10:00:00Z",
  "target_social_account_ids": ["social-a1", "social-a2"]
}
```
- **Expected:** 201 Created, post in SCHEDULED status
- **Status:** [ ] Pending

### IT-005-003: Create post - content too long
- **File:** `tests/Feature/Api/V1/Posts/CreatePostTest.php`
- **Request:** Content exceeds platform limit
- **Expected:** 422 Validation Error with platform-specific message
- **Status:** [ ] Pending

### IT-005-004: Create post - invalid target account
- **File:** `tests/Feature/Api/V1/Posts/CreatePostTest.php`
- **Request:** Target account from different workspace
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-005-005: Create post - expired social account
- **File:** `tests/Feature/Api/V1/Posts/CreatePostTest.php`
- **Request:** Target account with expired token
- **Expected:** 422 "Social account requires reconnection"
- **Status:** [ ] Pending

### IT-005-006: Create post - viewer forbidden
- **File:** `tests/Feature/Api/V1/Posts/CreatePostTest.php`
- **Setup:** Viewer tries to create post
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-005-007: List workspace posts
- **File:** `tests/Feature/Api/V1/Posts/ListPostsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts`
- **Expected:** 200 OK, paginated list of posts
- **Status:** [ ] Pending

### IT-005-008: List posts with status filter
- **File:** `tests/Feature/Api/V1/Posts/ListPostsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts?status=SCHEDULED`
- **Expected:** 200 OK, only scheduled posts
- **Status:** [ ] Pending

### IT-005-009: List posts with date range filter
- **File:** `tests/Feature/Api/V1/Posts/ListPostsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts?from=2026-03-01&to=2026-03-31`
- **Expected:** 200 OK, posts in date range
- **Status:** [ ] Pending

### IT-005-010: Get post details
- **File:** `tests/Feature/Api/V1/Posts/GetPostTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts/{post_id}`
- **Expected:** 200 OK, full post with targets and media
- **Status:** [ ] Pending

### IT-005-011: Get post - cross-workspace forbidden
- **File:** `tests/Feature/Api/V1/Posts/GetPostTest.php`
- **Setup:** Request Workspace B post from Workspace A
- **Expected:** 404 Not Found
- **Status:** [ ] Pending

### IT-005-012: Update draft post
- **File:** `tests/Feature/Api/V1/Posts/UpdatePostTest.php`
- **Endpoint:** `PATCH /v1/workspaces/{workspace_id}/posts/{post_id}`
- **Request:** `{ "content": "Updated content" }`
- **Expected:** 200 OK, post updated
- **Status:** [ ] Pending

### IT-005-013: Update scheduled post
- **File:** `tests/Feature/Api/V1/Posts/UpdatePostTest.php`
- **Setup:** Post is scheduled
- **Request:** `{ "content": "New content" }`
- **Expected:** 200 OK (can update before publish time)
- **Status:** [ ] Pending

### IT-005-014: Update published post forbidden
- **File:** `tests/Feature/Api/V1/Posts/UpdatePostTest.php`
- **Setup:** Post is already published
- **Expected:** 422 "Cannot modify published post"
- **Status:** [ ] Pending

### IT-005-015: Reschedule post
- **File:** `tests/Feature/Api/V1/Posts/UpdatePostTest.php`
- **Request:** `{ "scheduled_at": "2026-04-01T10:00:00Z" }`
- **Expected:** 200 OK, new schedule time set
- **Status:** [ ] Pending

### IT-005-016: Delete draft post
- **File:** `tests/Feature/Api/V1/Posts/DeletePostTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}/posts/{post_id}`
- **Expected:** 200 OK, post deleted
- **Status:** [ ] Pending

### IT-005-017: Delete scheduled post
- **File:** `tests/Feature/Api/V1/Posts/DeletePostTest.php`
- **Expected:** 200 OK, scheduled post cancelled
- **Status:** [ ] Pending

### IT-005-018: Delete published post forbidden
- **File:** `tests/Feature/Api/V1/Posts/DeletePostTest.php`
- **Expected:** 422 "Cannot delete published post"
- **Status:** [ ] Pending

### IT-005-019: Publish now
- **File:** `tests/Feature/Api/V1/Posts/PublishPostTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/publish`
- **Expected:** 200 OK, post queued for immediate publishing
- **Status:** [ ] Pending

### IT-005-020: Upload media
- **File:** `tests/Feature/Api/V1/Posts/MediaUploadTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/media`
- **Request:** Multipart form with image file
- **Expected:** 201 Created, media attached to post
- **Status:** [ ] Pending

### IT-005-021: Upload media - invalid type
- **File:** `tests/Feature/Api/V1/Posts/MediaUploadTest.php`
- **Request:** Upload .exe file
- **Expected:** 422 "Invalid file type"
- **Status:** [ ] Pending

### IT-005-022: Upload media - size limit
- **File:** `tests/Feature/Api/V1/Posts/MediaUploadTest.php`
- **Request:** Upload file > 10MB
- **Expected:** 422 "File too large"
- **Status:** [ ] Pending

### IT-005-023: Calendar view endpoint
- **File:** `tests/Feature/Api/V1/Posts/CalendarTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/calendar?month=2026-03`
- **Expected:** 200 OK, posts grouped by date
- **Status:** [ ] Pending

### IT-005-024: Bulk schedule posts
- **File:** `tests/Feature/Api/V1/Posts/BulkScheduleTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/bulk-schedule`
- **Expected:** 200 OK, multiple posts scheduled
- **Status:** [ ] Pending

### IT-005-025: Duplicate post
- **File:** `tests/Feature/Api/V1/Posts/DuplicatePostTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/duplicate`
- **Expected:** 201 Created, new draft post with same content
- **Status:** [ ] Pending

---

## Background Job Tests (Codex to implement)

### JT-005-001: PublishPostJob - success
- **File:** `tests/Feature/Jobs/PublishPostJobTest.php`
- **Description:** Verify post is published to all target platforms
- **Status:** [ ] Pending

### JT-005-002: PublishPostJob - partial failure
- **File:** `tests/Feature/Jobs/PublishPostJobTest.php`
- **Description:** Verify partial success handling (some platforms fail)
- **Status:** [ ] Pending

### JT-005-003: PublishPostJob - complete failure
- **File:** `tests/Feature/Jobs/PublishPostJobTest.php`
- **Description:** Verify post marked FAILED and notifications sent
- **Status:** [ ] Pending

### JT-005-004: PublishPostJob - retry logic
- **File:** `tests/Feature/Jobs/PublishPostJobTest.php`
- **Description:** Verify retries on transient failures
- **Status:** [ ] Pending

### JT-005-005: ScheduledPostsJob - picks up due posts
- **File:** `tests/Feature/Jobs/ScheduledPostsJobTest.php`
- **Description:** Verify scheduled posts are dispatched at correct time
- **Status:** [ ] Pending

### JT-005-006: PublishPostJob - workspace validation
- **File:** `tests/Feature/Jobs/PublishPostJobTest.php`
- **Description:** Verify job validates workspace is active
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-005-001: Create and schedule post flow
- **File:** `tests/e2e/posts/create-post.spec.ts`
- **Steps:**
  1. Login as editor
  2. Navigate to Create Post
  3. Enter content
  4. Select social accounts
  5. Choose date/time
  6. Click Schedule
  7. Verify post appears in calendar
- **Status:** [ ] Pending

### E2E-005-002: Edit scheduled post
- **File:** `tests/e2e/posts/edit-post.spec.ts`
- **Steps:**
  1. Login and view scheduled post
  2. Click Edit
  3. Modify content
  4. Save changes
  5. Verify changes reflected
- **Status:** [ ] Pending

### E2E-005-003: Calendar navigation and filtering
- **File:** `tests/e2e/posts/calendar.spec.ts`
- **Steps:**
  1. Navigate to Calendar view
  2. Switch months
  3. Filter by platform
  4. Verify correct posts displayed
- **Status:** [ ] Pending

### E2E-005-004: Media upload flow
- **File:** `tests/e2e/posts/media-upload.spec.ts`
- **Steps:**
  1. Create new post
  2. Upload image
  3. Verify preview displayed
  4. Upload second image
  5. Reorder images
  6. Save post
- **Status:** [ ] Pending

### E2E-005-005: Publish now flow
- **File:** `tests/e2e/posts/publish-now.spec.ts`
- **Steps:**
  1. Create draft post
  2. Click "Publish Now"
  3. Confirm in modal
  4. Verify publishing status
  5. Verify published status after completion
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-005-001: Real LinkedIn publishing
- **Steps:**
  1. Connect real LinkedIn account
  2. Create post with text + image
  3. Publish immediately
  4. Verify appears on LinkedIn
  5. Verify post URL stored
- **Status:** [ ] Not tested

### MT-005-002: Real Facebook publishing
- **Steps:**
  1. Connect real Facebook Page
  2. Create post
  3. Publish
  4. Verify on Facebook
- **Status:** [ ] Not tested

### MT-005-003: Real Instagram publishing
- **Steps:**
  1. Connect real Instagram Business
  2. Create image post
  3. Publish
  4. Verify on Instagram
- **Status:** [ ] Not tested

### MT-005-004: Scheduled post timing accuracy
- **Steps:**
  1. Schedule post for specific time
  2. Wait for scheduled time
  3. Verify published within 1-minute window
- **Status:** [ ] Not tested

### MT-005-005: Large media file handling
- **Steps:**
  1. Upload various file sizes (1MB, 5MB, 10MB)
  2. Verify upload progress
  3. Verify processing completes
  4. Verify displayed correctly
- **Status:** [ ] Not tested

### MT-005-006: Multi-platform post variations
- **Steps:**
  1. Create post targeting LinkedIn + Instagram
  2. Customize content per platform
  3. Publish
  4. Verify each platform shows correct content
- **Status:** [ ] Not tested

### MT-005-007: Failed post recovery
- **Steps:**
  1. Simulate failed publish
  2. View error details
  3. Retry publish
  4. Verify success
- **Status:** [ ] Not tested

### MT-005-008: Post queue under load
- **Steps:**
  1. Schedule 50+ posts for same time
  2. Verify all process without timeout
  3. Verify queue priorities respected
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-005-001: XSS in post content
- **Attack:** Include `<script>` tags in post content
- **Expected:** Properly escaped on display, never executed
- **Status:** [ ] Not tested

### ST-005-002: SSRF via media URL
- **Attack:** Provide internal URL as media source
- **Expected:** URL validation, no internal access
- **Status:** [ ] Not tested

### ST-005-003: Path traversal in media upload
- **Attack:** Filename with `../` sequences
- **Expected:** Filename sanitized
- **Status:** [ ] Not tested

### ST-005-004: Post to unauthorized social account
- **Attack:** Include social_account_id from different workspace
- **Expected:** Validation error or 404
- **Status:** [ ] Not tested

### ST-005-005: Mass assignment on post
- **Attack:** Include `published_at`, `external_id` in create request
- **Expected:** Fields ignored
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 12 | - | - | 12 |
| Integration | 25 | - | - | 25 |
| Job Tests | 6 | - | - | 6 |
| E2E | 5 | - | - | 5 |
| Manual | 8 | - | - | 8 |
| Security | 5 | - | - | 5 |
| **Total** | **61** | **-** | **-** | **61** |

---

**Last Updated:** February 2026
**Status:** Draft
