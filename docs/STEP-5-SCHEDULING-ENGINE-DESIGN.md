# Phase-1A STEP 5 — Scheduling Engine Design

## 1. Scope

Fix the scheduling pipeline so that time-based scheduled posts actually get published. FB + IG only. No new models, no new migrations, no new queue infrastructure.

### In Scope
- Fix the critical coordinator→publisher handoff bug (posts stuck in SCHEDULED forever)
- Timezone-safe scheduling (honor the user-supplied timezone)
- Reschedule capability (update scheduled_at on a SCHEDULED post)
- Batch-size safety on the coordinator job
- BulkPostController type-safety fix
- Duplicate-dispatch protection for scheduled posts

### Non-Goals
- No new platforms (Threads, WhatsApp, YouTube, X)
- No AI assist, analytics, or inbox
- No rate-limit enforcement at schedule time (STEP 4's processTarget handles it at publish time)
- No recurring/repeat scheduling
- No queue infrastructure changes (same `content` queue, same every-minute cron)
- No new models or migrations
- No UI changes

---

## 2. Existing Infrastructure (As-Built)

### Scheduling Flow (Current)

```
User → POST /posts/{post}/schedule
     → SchedulePostRequest (validates: after:now, optional timezone)
     → PostController::schedule()
     → PostService::schedule(Post, DateTimeInterface, ?timezone)
     → Post::schedule() → status = SCHEDULED, scheduled_at = value, scheduled_timezone = value

Cron (every minute) → PublishScheduledPostsJob::handle()
     → Query: WHERE status=SCHEDULED AND scheduled_at <= now()
     → For each: PublishPostJob::dispatch(post_id, workspace_id)  ← BUG

PublishPostJob::handle(PublishingService)
     → Fetch post → Check status === PUBLISHING  ← BLOCKS SCHEDULED
     → Process each target via PublishingService::processTarget()
```

### Key Files

| File | Role |
|------|------|
| `app/Jobs/Content/PublishScheduledPostsJob.php` | Coordinator: finds due posts, dispatches publisher |
| `app/Jobs/Content/PublishPostJob.php` | Publisher: processes each target via adapter |
| `app/Services/Content/PublishingService.php` | Service: `publishNow()`, `publishScheduled()`, `processTarget()` |
| `app/Services/Content/PostService.php` | Service: `schedule()`, `cancel()` |
| `app/Http/Controllers/Api/V1/Content/PostController.php` | API: `schedule()` endpoint |
| `app/Http/Controllers/Api/V1/Content/BulkPostController.php` | API: `bulkSchedule()` endpoint |
| `app/Http/Requests/Content/SchedulePostRequest.php` | Validation: `scheduled_at`, `timezone` |
| `app/Models/Content/Post.php` | Model: `schedule()`, `markPublishing()`, `markFailed()` |
| `app/Enums/Content/PostStatus.php` | States: SCHEDULED → PUBLISHING → PUBLISHED/FAILED |
| `routes/console.php` | Scheduler: every-minute cron for PublishScheduledPostsJob |

---

## 3. GAPs Found

### GAP-1 — Coordinator bypasses service layer (CRITICAL — posts never publish)

**Location:** `PublishScheduledPostsJob::handle()` (line 85-87)

**Bug:** The coordinator dispatches `PublishPostJob::dispatch()` directly without transitioning the post to PUBLISHING state. Meanwhile, `PublishPostJob::handle()` (line 114) checks `$post->status !== PostStatus::PUBLISHING` and returns early if not.

**Result:** Every scheduled post is dispatched → PublishPostJob sees status=SCHEDULED → logs warning → returns. Post stays SCHEDULED forever. Re-dispatched every minute. Never publishes.

**Unused fix already exists:** `PublishingService::publishScheduled()` (line 69-89) correctly iterates due posts and calls `publishNow()` which marks post+targets as PUBLISHING before dispatching. Nobody calls this method.

**Fix:** Replace the hand-rolled dispatch loop in `PublishScheduledPostsJob` with a single call to `PublishingService::publishScheduled()`.

### GAP-2 — No reschedule path

**Location:** `PostStatus::canTransitionTo()` (line 117), `PostService::schedule()` (line 250)

**Bug:** `PostService::schedule()` calls `canTransitionTo(SCHEDULED)` which checks the transition map. For SCHEDULED → SCHEDULED, the transition is not listed. So a post that is already scheduled cannot have its `scheduled_at` updated.

**Result:** To change a scheduled time, the user must cancel (SCHEDULED → CANCELLED, terminal) and create an entirely new post. This is a broken UX.

**Fix:** Add a `reschedule()` method on `PostService` that:
1. Validates the post is in SCHEDULED status
2. Validates new time is in the future
3. Updates `scheduled_at` and `scheduled_timezone` directly (no state transition needed — it stays SCHEDULED)

Add a `PUT /posts/{post}/schedule` route and `ReschedulePostRequest` form request.

### GAP-3 — Timezone not applied when parsing scheduled_at

**Location:** `PostController::schedule()` (line 242)

**Code:**
```php
$scheduledAt = Carbon::parse($data->scheduled_at);
```

**Bug:** If the user sends `scheduled_at: "2026-02-15T15:00:00"` (no offset) with `timezone: "America/New_York"`, the time is parsed as UTC 15:00, not New York 15:00. The timezone parameter is stored as metadata but never applied to the actual time.

**Fix:** Apply the timezone when parsing:
```php
$scheduledAt = $data->timezone
    ? Carbon::parse($data->scheduled_at, $data->timezone)->utc()
    : Carbon::parse($data->scheduled_at);
```

This converts user-local time to UTC for storage. If the input already has an offset (e.g., `2026-02-15T15:00:00-05:00`), Carbon handles it correctly regardless.

### GAP-4 — BulkPostController passes string to DateTimeInterface parameter

**Location:** `BulkPostController::bulkSchedule()` (line 95, 105)

**Code:**
```php
$scheduledAt = $request->input('scheduled_at'); // string
$this->postService->schedule($post, $scheduledAt); // expects DateTimeInterface
```

**Bug:** `PostService::schedule()` declares `\DateTimeInterface $scheduledAt` as its type. Passing a raw string works only because PHP doesn't enforce interface types on strings at the call site — but `$scheduledAt->format('c')` inside the method would crash on a string.

**Fix:** Parse to Carbon before passing, with timezone support:
```php
$scheduledAt = Carbon::parse($request->input('scheduled_at'));
```

### GAP-5 — No batch-size limit on coordinator

**Location:** `PublishScheduledPostsJob::handle()` (line 69-72)

**Code:**
```php
$posts = Post::query()
    ->where('status', PostStatus::SCHEDULED)
    ->where('scheduled_at', '<=', now())
    ->get();
```

**Risk:** If a scheduling backlog builds up (e.g., after downtime), this loads all due posts into memory at once. With thousands of posts, this could OOM the worker.

**Fix:** After switching to `PublishingService::publishScheduled()`, update that method to use `chunk()` or `limit()` to process at most 100 posts per run. The next minute's cron will catch the rest.

### GAP-6 — No idempotency guard on scheduled dispatch

**Location:** `PublishScheduledPostsJob::handle()` / `PublishingService::publishScheduled()`

**Risk:** If the coordinator job runs twice in quick succession (overlapping cron race, retry after error), the same SCHEDULED post could be dispatched twice. `PublishPostJob` has `uniqueId()` which provides some protection, but only if the queue supports unique jobs (requires Redis/ShouldBeUnique).

**Fix:** In `PublishingService::publishScheduled()`, use `publishNow()` which marks the post as PUBLISHING inside a transaction. The second call will see `canPublish()` returns false for a PUBLISHING post and will throw, preventing double-dispatch.

This is already handled by the existing `publishNow()` flow — no additional code needed once GAP-1 is fixed.

---

## 4. Data Flow (After Fix)

### Scheduling (API → Database)

```
User → POST /api/v1/workspaces/{w}/posts/{p}/schedule
     → SchedulePostRequest validates: scheduled_at (after:now), timezone (optional)
     → PostController::schedule()
     → Carbon::parse(scheduled_at, timezone)->utc()  ← GAP-3 fix
     → PostService::schedule(post, Carbon, timezone)
     → Validates: canTransitionTo(SCHEDULED), has content, has targets, future
     → Post::schedule(scheduledAt, timezone) → DB: status=SCHEDULED, scheduled_at=UTC, scheduled_timezone=tz
```

### Rescheduling (API → Database)

```
User → PUT /api/v1/workspaces/{w}/posts/{p}/schedule
     → ReschedulePostRequest validates: scheduled_at (after:now), timezone (optional)
     → PostController::reschedule()
     → Carbon::parse(scheduled_at, timezone)->utc()
     → PostService::reschedule(post, Carbon, ?timezone)
     → Validates: status === SCHEDULED, future time
     → Post: updates scheduled_at + scheduled_timezone, stays SCHEDULED
```

### Publishing (Cron → Job → Adapter)

```
console.php cron (every minute)
  → PublishScheduledPostsJob::handle(PublishingService)      ← GAP-1 fix
  → PublishingService::publishScheduled()                    ← uses existing method
  → chunk(100): Post WHERE status=SCHEDULED AND scheduled_at <= now()  ← GAP-5 fix
  → For each post:
      → publishNow(post)
        → Validate canPublish() → true (SCHEDULED is publishable)
        → Transaction:
          → post.markPublishing() → status=PUBLISHING
          → targets.update(status=PUBLISHING)
          → PublishPostJob::dispatch(post_id, workspace_id)
  → PublishPostJob::handle(PublishingService)
      → Fetch post → status === PUBLISHING ✓
      → For each target:
          → PublishingService::processTarget(target)  ← STEP 4 gateway checks apply
```

### Cancellation (Existing — No Changes)

```
User → POST /api/v1/workspaces/{w}/posts/{p}/cancel
     → PostService::cancel()
     → Post::cancel() → status=CANCELLED (terminal)
```

---

## 5. Status Machine (Complete)

```
DRAFT → SUBMITTED → APPROVED ─┬→ SCHEDULED ─┬→ PUBLISHING → PUBLISHED
                               │             │       ↑
                               │             ├→ CANCELLED
                               │             │
                               │             └→ FAILED ──────────┘
                               │
                               └→ PUBLISHING → PUBLISHED
                                      ↑
                                      └── FAILED ────────────────┘
```

Allowed transitions (from PostStatus::canTransitionTo):
- APPROVED → SCHEDULED, PUBLISHING
- SCHEDULED → PUBLISHING, CANCELLED, FAILED
- PUBLISHING → PUBLISHED, FAILED
- FAILED → PUBLISHING (retry)

No new transitions needed. SCHEDULED → SCHEDULED is NOT added — rescheduling updates `scheduled_at` without a state transition.

---

## 6. Failure Scenarios

### At Schedule Time (API validation)

| Scenario | Handler | Response |
|----------|---------|----------|
| Post not in APPROVED status | `PostService::schedule()` | 422 — "Post cannot be scheduled from its current status" |
| No content | `PostService::validatePostHasContent()` | 422 — "Post must have content" |
| No targets | `PostService::validatePostHasTargets()` | 422 — "Post must have at least one target" |
| Past scheduled_at | `SchedulePostRequest` rules | 422 — "Scheduled time must be in the future" |
| Invalid timezone | `SchedulePostRequest` rules | 422 — validation error |
| Unauthorized user | `SchedulePostRequest::authorize()` | 403 |

### At Reschedule Time (API validation)

| Scenario | Handler | Response |
|----------|---------|----------|
| Post not in SCHEDULED status | `PostService::reschedule()` | 422 — "Only scheduled posts can be rescheduled" |
| Past scheduled_at | `ReschedulePostRequest` rules | 422 — "Scheduled time must be in the future" |
| Same time as current | `PostService::reschedule()` | No-op, returns current post |

### At Publish Time (Cron execution)

| Scenario | Handler | Outcome |
|----------|---------|---------|
| Integration disabled | `PublishingService::processTarget()` (STEP 4 GAP-1) | Target FAILED, error_code=INTEGRATION_DISABLED |
| Token expired | `PublishingService::processTarget()` (STEP 4 GAP-2) | Target FAILED, error_code=TOKEN_EXPIRED |
| Account disconnected | `PublishingService::processTarget()` | Target FAILED, error_code=ACCOUNT_UNAVAILABLE |
| Adapter HTTP error | Adapter catch block | Target FAILED, error_code from platform |
| publishNow() fails for post | `PublishingService::publishScheduled()` catch block | Logged, skipped, retried next minute |
| Post deleted between schedule and publish | `PublishPostJob::handle()` | Returns early (post not found) |
| Worker OOM from large batch | `chunk(100)` in publishScheduled() | Processes 100 per run, rest caught next minute |

---

## 7. Files to Change

### Modified (5 files)

| # | File | Change | Lines |
|---|------|--------|-------|
| 1 | `app/Jobs/Content/PublishScheduledPostsJob.php` | Inject PublishingService, call `publishScheduled()` instead of hand-rolled loop | ~15 |
| 2 | `app/Services/Content/PublishingService.php` | Update `publishScheduled()` to use `chunk(100)` with limit | ~8 |
| 3 | `app/Http/Controllers/Api/V1/Content/PostController.php` | Fix timezone parsing in `schedule()`, add `reschedule()` method | ~25 |
| 4 | `app/Services/Content/PostService.php` | Add `reschedule()` method | ~20 |
| 5 | `app/Http/Controllers/Api/V1/Content/BulkPostController.php` | Fix string→Carbon in `bulkSchedule()` | ~3 |

### New (2 files)

| # | File | Purpose |
|---|------|---------|
| 6 | `app/Http/Requests/Content/ReschedulePostRequest.php` | Form request for PUT /posts/{post}/schedule |
| 7 | `routes/api/v1.php` | Add 1 route: PUT /posts/{post}/schedule |

### Test Files Modified/Created (3 files)

| # | File | Tests |
|---|------|-------|
| 8 | `tests/Unit/Jobs/Content/PublishScheduledPostsJobTest.php` | Update existing tests to verify service delegation |
| 9 | `tests/Unit/Services/Content/PublishingServiceTest.php` | Add `publishScheduled()` tests (chunk, markPublishing, skip failed) |
| 10 | `tests/Unit/Services/Content/PostServiceTest.php` | Add `reschedule()` tests |

---

## 8. Implementation Details

### GAP-1 Fix — PublishScheduledPostsJob delegates to service

```php
// PublishScheduledPostsJob::handle()
public function handle(PublishingService $publishingService): void
{
    Log::info('[PublishScheduledPostsJob] Starting scheduled posts check');
    $publishingService->publishScheduled();
    Log::info('[PublishScheduledPostsJob] Completed scheduled posts check');
}
```

The job becomes a thin shell. All logic lives in the service (testable, reusable).

### GAP-1 + GAP-5 Fix — PublishingService::publishScheduled() with chunk

```php
public function publishScheduled(): void
{
    $processed = 0;

    Post::withStatus(PostStatus::SCHEDULED)
        ->where('scheduled_at', '<=', now())
        ->orderBy('scheduled_at', 'asc')   // oldest first
        ->limit(100)                        // safety cap per run
        ->get()
        ->each(function (Post $post) use (&$processed) {
            try {
                $this->publishNow($post);
                $processed++;
            } catch (\Throwable $e) {
                $this->log('Failed to publish scheduled post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ], 'error');
            }
        });

    $this->log('Processed scheduled posts', ['count' => $processed]);
}
```

Uses `limit(100)` instead of `chunk()` because `publishNow()` changes the status (which would break chunk iteration). Overflow caught by next minute's cron.

### GAP-2 Fix — PostService::reschedule()

```php
public function reschedule(Post $post, \DateTimeInterface $scheduledAt, ?string $timezone = null): Post
{
    if ($post->status !== PostStatus::SCHEDULED) {
        throw ValidationException::withMessages([
            'post' => ['Only scheduled posts can be rescheduled.'],
        ]);
    }

    if ($scheduledAt <= now()) {
        throw ValidationException::withMessages([
            'scheduled_at' => ['Scheduled time must be in the future.'],
        ]);
    }

    $post->scheduled_at = $scheduledAt;
    if ($timezone !== null) {
        $post->scheduled_timezone = $timezone;
    }
    $post->save();

    $this->log('Post rescheduled', [
        'post_id' => $post->id,
        'scheduled_at' => $scheduledAt->format('c'),
        'timezone' => $timezone,
    ]);

    return $post->fresh(['author', 'targets.socialAccount', 'media']);
}
```

No state transition — the post stays SCHEDULED. Only `scheduled_at` and optionally `scheduled_timezone` are updated.

### GAP-3 Fix — Timezone-aware parsing in PostController

```php
// PostController::schedule()
$scheduledAt = $data->timezone
    ? Carbon::parse($data->scheduled_at, $data->timezone)->utc()
    : Carbon::parse($data->scheduled_at);
```

If the user sends `15:00` with `timezone: America/New_York`, this becomes `20:00 UTC` in the database. If they send an ISO offset like `15:00-05:00`, Carbon already handles it correctly.

### GAP-4 Fix — BulkPostController type-safety

```php
// BulkPostController::bulkSchedule()
$scheduledAt = Carbon::parse($request->input('scheduled_at'));
```

Parse string to Carbon before passing to `PostService::schedule()`.

### New Route

```php
// routes/api/v1.php (after line 262)
Route::put('/posts/{post}/schedule', [PostController::class, 'reschedule']);
```

### New Form Request — ReschedulePostRequest

Same authorization as `SchedulePostRequest` except checks `$post->status === PostStatus::SCHEDULED` instead of `canTransitionTo(SCHEDULED)`.

---

## 9. Test Plan

### PublishScheduledPostsJobTest (Update 3 existing + add 1 new)

| Test | Assertion |
|------|-----------|
| `dispatches jobs for due posts` | Verify `publishNow()` called (mock service) |
| `skips non-scheduled posts` | No service calls for DRAFT/PUBLISHED posts |
| `handles empty results` | Service called, no errors |
| **NEW** `delegates to PublishingService::publishScheduled` | Service method invoked once |

### PublishingServiceTest (Add 5 new tests)

| Test | Assertion |
|------|-----------|
| `publishScheduled processes due posts` | Posts move from SCHEDULED → PUBLISHING |
| `publishScheduled skips future posts` | Posts with future scheduled_at untouched |
| `publishScheduled limits to 100 posts` | Only 100 processed even if 150 due |
| `publishScheduled handles publishNow failure gracefully` | One failure doesn't block others |
| `publishScheduled orders by scheduled_at ascending` | Oldest post processed first |

### PostServiceTest (Add 4 new tests)

| Test | Assertion |
|------|-----------|
| `reschedule updates scheduled_at` | New time persisted, status stays SCHEDULED |
| `reschedule rejects non-scheduled post` | 422 for DRAFT, APPROVED, PUBLISHED |
| `reschedule rejects past time` | 422 for time in the past |
| `reschedule updates timezone when provided` | scheduled_timezone updated |

---

## 10. Verification Checklist

After implementation, verify these scenarios manually or via tests:

- [ ] Schedule a post → verify it shows status=SCHEDULED with correct scheduled_at in UTC
- [ ] Wait for cron tick → verify post transitions SCHEDULED → PUBLISHING → PUBLISHED
- [ ] Schedule a post with timezone → verify UTC conversion is correct
- [ ] Reschedule a SCHEDULED post → verify scheduled_at updates, status stays SCHEDULED
- [ ] Attempt to reschedule a DRAFT post → verify 422 rejection
- [ ] Bulk schedule 3 posts → verify all 3 become SCHEDULED with correct times
- [ ] Disable integration before scheduled publish time → verify post target gets INTEGRATION_DISABLED error
- [ ] Let token expire before scheduled publish time → verify post target gets TOKEN_EXPIRED error
- [ ] Schedule 150 posts for same time → verify first 100 processed, remaining 50 on next minute

---

## 11. Implementation Order

1. `PublishScheduledPostsJob.php` — Delegate to service (GAP-1, critical fix)
2. `PublishingService.php` — Update `publishScheduled()` with limit (GAP-5)
3. `PostController.php` — Fix timezone parsing (GAP-3)
4. `BulkPostController.php` — Fix type (GAP-4)
5. `PostService.php` — Add `reschedule()` (GAP-2)
6. `ReschedulePostRequest.php` — New form request (GAP-2)
7. `PostController.php` — Add `reschedule()` action (GAP-2)
8. `routes/api/v1.php` — Add PUT route (GAP-2)
9. Tests — Update and add per test plan
10. Full suite run

---

**Phase-1A STEP 5 design complete. Awaiting approval before implementation.**
