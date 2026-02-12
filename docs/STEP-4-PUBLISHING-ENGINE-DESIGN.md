# Phase-1A STEP 4 — Publishing Engine (Facebook + Instagram)

## Scope Lock

**IN**: Immediate publish to Facebook Pages + Instagram Business. Text, image, video posts.
**OUT**: Scheduling, Threads, WhatsApp, YouTube, X, AI content gen, cross-platform adaptation, analytics, inbox.

---

## 1. Current State Inventory

Everything below **already exists and works** (built in prior milestones):

| Layer | File | Purpose |
|-------|------|---------|
| Model | `Post.php` | Full status machine (9 states), workspace-scoped |
| Model | `PostTarget.php` | Per-account publish tracking (status, error, retry_count) |
| Model | `PostMedia.php` | Attachments (storage_path, cdn_url, mime_type, getUrl()) |
| Model | `SocialAccount.php` | OAuth creds, `canPublish()` = CONNECTED only |
| Model | `SocialPlatformIntegration.php` | Integration state (STEP 3) |
| Enum | `PostStatus.php` | DRAFT/SUBMITTED/APPROVED/SCHEDULED/PUBLISHING/PUBLISHED/FAILED/CANCELLED |
| Enum | `PostTargetStatus.php` | PENDING/PUBLISHING/PUBLISHED/FAILED |
| Enum | `PostType.php` | STANDARD/REEL/STORY/THREAD/ARTICLE |
| Service | `PublishingService.php` | publishNow(), processTarget(), updatePostStatusFromTargets(), retryFailed() |
| Service | `SocialPlatformAdapterFactory.php` | Creates FB/IG adapters with PlatformCredentials (STEP 3) |
| Adapter | `FacebookAdapter.php` | publishPost() for text/image/link |
| Adapter | `InstagramAdapter.php` | publishPost() for image/video/carousel |
| Contract | `SocialPlatformAdapter.php` | Interface with publishPost() |
| Contract | `PublishResult.php` | Immutable success/failure result DTO |
| Job | `PublishPostJob.php` | Queued on 'content', 3 tries, 180s timeout |
| Controller | `PostController.php` | CRUD + publish/submit/schedule/cancel/duplicate |
| Route | `POST .../posts/{post}/publish` | Immediate publish endpoint |

---

## 2. Gaps Identified (6 issues)

### GAP-1: Integration Status Not Checked During Publish (CRITICAL)

**Where:** `PublishingService::processTarget()` (line 133-173)
**Problem:** Checks `$account->canPublish()` (SocialAccountStatus == CONNECTED) but never checks `SocialPlatformIntegration.isActive()`. If super admin disables the Meta integration (STEP 3), publishing still attempts the API call.
**Requirement:** "Publishing must be blocked if integration is DISABLED"

### GAP-2: Token Expiry Not Pre-Checked

**Where:** `PublishingService::processTarget()` (line 150)
**Problem:** `$account->canPublish()` checks `SocialAccountStatus::CONNECTED` but doesn't call `$account->isTokenExpired()`. A CONNECTED account with an expired timestamp will still attempt the API call and fail with an opaque HTTP 401.
**Requirement:** "Publishing must fail gracefully if token is invalid"

### GAP-3: Media URL Bug

**Where:** `FacebookAdapter::publishPost()` line 174, `InstagramAdapter::publishPost()` line 121
**Problem:** Both adapters reference `$firstMedia->url` — **no such property exists** on PostMedia. The model has `getUrl()` method and `cdn_url` property. Adapters also reference `$firstMedia->mime_type` (valid).
**Impact:** Every publish with media fails at runtime.

### GAP-4: Facebook Video Publishing Missing

**Where:** `FacebookAdapter::publishPost()` (line 170-181)
**Problem:** Code branches on `image/` mime_type → `/photos` endpoint, else falls through to `/feed`. For video, the `/feed` endpoint won't upload a video file. Facebook requires `/{pageId}/videos` with `file_url` param.
**Scope:** STEP 4 must support video posts to FB Pages.

### GAP-5: Retry Policy Too Aggressive

**Where:** `PublishPostJob.php` (lines 45-59)
**Problem:** `$tries = 3` with `$backoff = [60, 120, 240]` (total: up to 7 min retry window).
**Requirement:** "No retry loops beyond 1 immediate retry" = max 2 attempts total.

### GAP-6: Failures Not Audit-Logged

**Where:** `PublishingService::processTarget()`
**Problem:** Failures are stored on PostTarget (error_code, error_message) and logged via `$this->log()`, but not written to the application audit trail. Failures are scattered across target records with no centralized view.
**Requirement:** "All failures must be auditable"

---

## 3. Changes — File by File

### A. `PublishingService::processTarget()` — 3 fixes (GAP-1, GAP-2, GAP-6)

Add integration + token checks **before** the adapter call. Add audit logging on failure.

```
processTarget(PostTarget $target):
  1. markPublishing()
  2. loadMissing(['post.media', 'socialAccount'])
  3. Validate platform ≠ null
  4. Validate $account ≠ null && $account->canPublish()

  // ── NEW: GAP-1 ──────────────────────────
  5. $integration = SocialPlatformIntegration::forPlatform($platform)->first()
  6. If $integration exists && !$integration->isActive():
       markFailed('INTEGRATION_DISABLED', 'Platform integration is disabled by administrator.')
       logPublishFailure(target, post)   ← GAP-6
       return

  // ── NEW: GAP-2 ──────────────────────────
  7. If $account->isTokenExpired():
       $account->markTokenExpired()
       markFailed('TOKEN_EXPIRED', 'Access token has expired. Please reconnect the account.')
       logPublishFailure(target, post)   ← GAP-6
       return

  8. Create adapter, call publishPost()
  9. On success → markPublished()
  10. On failure → markFailed(), logPublishFailure()   ← GAP-6
  11. On exception → markFailed(), logPublishFailure()  ← GAP-6
```

**New private method** in `PublishingService`:

```php
private function logPublishFailure(PostTarget $target, Post $post): void
{
    $this->log('Publishing failed for target', [
        'post_id'        => $post->id,
        'target_id'      => $target->id,
        'workspace_id'   => $post->workspace_id,
        'platform'       => $target->platform_code,
        'account_id'     => $target->social_account_id,
        'error_code'     => $target->error_code,
        'error_message'  => $target->error_message,
        'retry_count'    => $target->retry_count,
    ], 'error');
}
```

This uses the existing `BaseService::log()` infrastructure which writes to Laravel's log channels. The PostTarget record itself (error_code, error_message, retry_count) serves as the persistent audit record — queryable per-post, per-platform, per-workspace.

**Dependency injection addition:** `PublishingService` constructor does NOT change. We query `SocialPlatformIntegration` statically (same pattern as `PlatformCredentialResolver`).

### B. `FacebookAdapter::publishPost()` — 2 fixes (GAP-3, GAP-4)

**GAP-3 fix:** Replace `$firstMedia->url` with `$firstMedia->getUrl()` throughout.

**GAP-4 fix:** Add video branch:

```
Current branching:
  image/* → /{pageId}/photos  (params: message, url, access_token)
  else   → /{pageId}/feed    (params: message, access_token)

New branching:
  image/* → /{pageId}/photos  (params: message, url, access_token)
  video/* → /{pageId}/videos  (params: description, file_url, access_token)
  else   → /{pageId}/feed    (params: message, access_token)
```

For video, Facebook Graph API `/{page-id}/videos`:
- `description` = post text (not `message`)
- `file_url` = publicly accessible video URL
- Response: `{ "id": "video_id" }`
- Post URL: `https://www.facebook.com/{pageId}/videos/{videoId}`

### C. `InstagramAdapter::publishPost()` — 1 fix (GAP-3)

**GAP-3 fix:** Replace `$firstMedia->url` with `$firstMedia->getUrl()` throughout. This applies to:
- Line 121: `$firstMedia->url` → `$firstMedia->getUrl()`
- Line 137: `$item->url` → `$item->getUrl()` (carousel loop)

No other changes needed. IG adapter already handles image, video, and carousel correctly via the container → publish two-step flow.

### D. `PublishPostJob` — 1 fix (GAP-5)

Change retry policy:

```php
// Before:
public int $tries = 3;
public array $backoff = [60, 120, 240];

// After:
public int $tries = 2;
public array $backoff = [10];
```

- **2 total attempts**: 1 initial + 1 immediate retry
- **10 second backoff**: fast retry, not a prolonged loop
- **retryUntil()**: reduce from 15 min to 5 min (safety net)

### E. `PublishingService::publishNow()` — No change

The existing pre-validation is correct:
1. `$post->canPublish()` → only APPROVED or SCHEDULED can transition
2. Target count > 0 check
3. Marks all targets PUBLISHING
4. Dispatches job

No additional validation needed at this layer. Platform/integration checks happen at `processTarget()` time per-target, which is correct because different targets may be on different platforms with different integration states.

### F. No new files, no new routes, no new migrations

Everything needed exists. STEP 4 is purely patching 4 existing files.

---

## 4. Tenant Publishing Flow (End-to-End)

```
Tenant User                     PostController          PublishingService         PublishPostJob         Adapter
    │                                │                        │                       │                    │
    │ POST /posts/{id}/publish       │                        │                       │                    │
    │──────────────────────────────→ │                        │                       │                    │
    │                                │ RBAC: canPublishDirectly()                     │                    │
    │                                │ workspace + tenant check                       │                    │
    │                                │──────────────────────→ │                       │                    │
    │                                │                   publishNow(post)             │                    │
    │                                │                        │ canPublish()? ✓       │                    │
    │                                │                        │ targets > 0? ✓        │                    │
    │                                │                        │ post → PUBLISHING      │                    │
    │                                │                        │ targets → PUBLISHING   │                    │
    │                                │                        │────────dispatch──────→ │                    │
    │  202 { status: "publishing" }  │                        │                       │                    │
    │ ←──────────────────────────────│                        │                       │                    │
    │                                                                                 │                    │
    │                                                         (async, queued)          │                    │
    │                                                                                 │ foreach target:    │
    │                                                                                 │──processTarget()──→│
    │                                                                                 │                    │
    │                                                    processTarget(target):         │                    │
    │                                                    ├─ integration active? ────── INTEGRATION_DISABLED │
    │                                                    ├─ token expired? ─────────── TOKEN_EXPIRED        │
    │                                                    ├─ account canPublish? ────── ACCOUNT_UNAVAILABLE  │
    │                                                    └─ adapter.publishPost() ───→ │                    │
    │                                                                                 │ FB: /photos, /feed,│
    │                                                                                 │     or /videos     │
    │                                                                                 │ IG: /media →       │
    │                                                                                 │     /media_publish  │
    │                                                                                 │←── PublishResult ──│
    │                                                    target → PUBLISHED or FAILED  │                    │
    │                                                    logPublishFailure() if failed │                    │
    │                                                                                 │                    │
    │                                                    updatePostStatusFromTargets() │                    │
    │                                                    ├─ all published → post PUBLISHED                  │
    │                                                    ├─ all failed → post FAILED                        │
    │                                                    └─ partial → post PUBLISHED (with warning log)     │
    │                                                                                 │                    │
    │                                                    notification → author (in-app)│                    │
```

---

## 5. API Contract

No new endpoints. The existing endpoint is sufficient:

### `POST /api/v1/workspaces/{workspace}/posts/{post}/publish`

**Auth:** Bearer token (workspace member with `canPublishDirectly` role)

**Pre-conditions:**
- Post status must be APPROVED or SCHEDULED (enforced by `PostStatus::canPublish()`)
- Post must have >= 1 target
- User must have publish permission in workspace

**Request body:** None (empty)

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Post publishing initiated",
  "data": {
    "id": "uuid",
    "status": "publishing",
    "targets": [
      {
        "id": "uuid",
        "social_account_id": "uuid",
        "platform_code": "facebook",
        "status": "publishing",
        "error_code": null,
        "error_message": null
      }
    ]
  }
}
```

**Error responses:**
- `422` — Post cannot be published from current status, or no targets
- `403` — User lacks publish permission
- `404` — Workspace/post not found

**Async outcome (per target):**
| Target Outcome | error_code | error_message |
|---|---|---|
| Success | null | null |
| Integration disabled | `INTEGRATION_DISABLED` | "Platform integration is disabled by administrator." |
| Token expired | `TOKEN_EXPIRED` | "Access token has expired. Please reconnect the account." |
| Account unavailable | `ACCOUNT_UNAVAILABLE` | "Social account is not available for publishing." |
| Unknown platform | `UNKNOWN_PLATFORM` | "Unknown platform: {code}" |
| FB API error | `{fb_error_code}` | Facebook error message |
| IG API error | `{ig_error_code}` | Instagram error message |
| Unhandled exception | `EXCEPTION` | Exception message |

---

## 6. Adapter Methods

### FacebookAdapter::publishPost()

```
Input: PostTarget, Post, Collection<PostMedia>
Output: PublishResult

1. Get pageId from account metadata (page_id or platform_account_id)
2. Get content from target (content_override ?? post.content_text)
3. Branch on media:

   a. NO MEDIA → POST /{pageId}/feed
      params: { message, access_token }
      optional: { link } if post.link_url set

   b. IMAGE (mime_type starts with "image/") → POST /{pageId}/photos
      params: { message, url: media.getUrl(), access_token }

   c. VIDEO (mime_type starts with "video/") → POST /{pageId}/videos  ← NEW
      params: { description, file_url: media.getUrl(), access_token }

   d. ELSE → POST /{pageId}/feed (fallback, text-only)
      params: { message, access_token }

4. Parse response → PublishResult::success(externalPostId, externalPostUrl)
5. On GuzzleException → PublishResult::failure(errorCode, errorMessage)
```

### InstagramAdapter::publishPost()

```
Input: PostTarget, Post, Collection<PostMedia>
Output: PublishResult

1. Get igUserId from account metadata (ig_user_id or platform_account_id)
2. Get content from target

3. Build media container:
   a. SINGLE IMAGE → POST /{igUserId}/media
      params: { caption, image_url: media.getUrl(), access_token }

   b. SINGLE VIDEO → POST /{igUserId}/media
      params: { caption, media_type: "VIDEO", video_url: media.getUrl(), access_token }

   c. CAROUSEL (multiple media) → for each child:
      POST /{igUserId}/media { is_carousel_item: true, image_url|video_url, access_token }
      Then: POST /{igUserId}/media { media_type: "CAROUSEL", caption, children, access_token }

4. Publish container:
   POST /{igUserId}/media_publish { creation_id, access_token }

5. Fetch permalink:
   GET /{mediaId}?fields=permalink

6. Return PublishResult::success(mediaId, permalink)
7. On GuzzleException → PublishResult::failure(errorCode, errorMessage)
```

---

## 7. Failure States & Error Codes

### Pre-flight failures (synchronous, before API call)

| Error Code | Trigger | Recovery |
|---|---|---|
| `INTEGRATION_DISABLED` | SocialPlatformIntegration not active | Super admin must re-enable integration |
| `TOKEN_EXPIRED` | SocialAccount.token_expires_at is past | User must reconnect account |
| `ACCOUNT_UNAVAILABLE` | SocialAccount status != CONNECTED | User must reconnect account |
| `UNKNOWN_PLATFORM` | platform_code not in SocialPlatform enum | Bug — should not happen |

### API failures (async, from platform)

| Error Code | Trigger | Recovery |
|---|---|---|
| `190` | Facebook: expired/invalid token | Reconnect account |
| `10` | Facebook: permission denied | Re-authorize with required scopes |
| `368` | Facebook: content policy violation | Edit content and retry |
| `36003` | Instagram: rate limit | Wait and retry |
| `FACEBOOK_PUBLISH_ERROR` | Catch-all FB error | Check error_message for details |
| `INSTAGRAM_PUBLISH_ERROR` | Catch-all IG error | Check error_message for details |
| `EXCEPTION` | Unhandled PHP exception | Check logs |

### Post-level status resolution

| Scenario | Post Status | Notes |
|---|---|---|
| All targets published | PUBLISHED | published_at set to now() |
| All targets failed | FAILED | Can retry via retryFailed() |
| Some published, some failed | PUBLISHED | Warning logged, partial success |

---

## 8. Rate Limit Handling

**Facebook Graph API limits:**
- 200 calls per user per hour (app-level)
- Page-level: 4800 calls per 24 hours

**Instagram Content Publishing:**
- 25 API-published posts per IG account per 24 hours
- Container creation counts toward this

**STEP 4 approach (minimal, no queuing):**
1. Do NOT pre-check rate limits before publishing
2. If the API returns a rate-limit error (HTTP 429 or error code `36003`/`32`), the adapter catches it as a GuzzleException
3. The error is recorded on PostTarget as `error_code` + `error_message` (e.g., "Application request limit reached")
4. PublishPostJob will retry once (after 10s) — if rate-limited on retry too, it fails permanently
5. User sees FAILED status with rate-limit message and can manually retry later

**Why not more:**
- Rate-limit queuing, token-bucket tracking, and per-account throttling are **out of scope** for STEP 4
- The 1-retry policy handles transient rate limit spikes
- Persistent rate limits require manual retry (user decision, not automated loop)

---

## 9. Status Model

No changes to enums. The existing status machines are correct:

### Post Status Transitions (relevant to publishing)

```
APPROVED ──→ PUBLISHING ──→ PUBLISHED  (terminal)
                  │
                  └──→ FAILED ──→ PUBLISHING  (retry)
```

### PostTarget Status Transitions

```
PENDING ──→ PUBLISHING ──→ PUBLISHED
                  │
                  └──→ FAILED
```

On retry: FAILED targets reset to PENDING → PUBLISHING.

### SocialAccount Status (unchanged)

Only `CONNECTED` accounts can publish. TOKEN_EXPIRED/REVOKED/DISCONNECTED → fail fast.

---

## 10. Files Modified — Summary

| # | File | Change | LOC Est |
|---|------|--------|---------|
| 1 | `Services/Content/PublishingService.php` | Add integration check, token expiry check, logPublishFailure() | ~35 |
| 2 | `Services/Social/Adapters/FacebookAdapter.php` | Fix media URL (`getUrl()`), add video branch | ~20 |
| 3 | `Services/Social/Adapters/InstagramAdapter.php` | Fix media URL (`getUrl()`) | ~5 |
| 4 | `Jobs/Content/PublishPostJob.php` | $tries=2, $backoff=[10], retryUntil 5min | ~3 |

**Total: 4 files modified, 0 files created, ~63 lines changed.**

---

## 11. Test Plan

### Unit Tests (new)

| Test | Asserts |
|---|---|
| `processTarget` with disabled integration → FAILED with INTEGRATION_DISABLED | |
| `processTarget` with expired token → FAILED with TOKEN_EXPIRED, account marked TOKEN_EXPIRED | |
| `processTarget` with valid account → delegates to adapter | |
| `logPublishFailure` writes to log channel | |

### Feature Tests (new)

| Test | Asserts |
|---|---|
| `POST /publish` with APPROVED post → 202, post becomes PUBLISHING | |
| `POST /publish` with DRAFT post → 422 | |
| `POST /publish` with no targets → 422 | |
| `POST /publish` without publish permission → 403 | |
| Facebook text post → PublishResult::success | |
| Facebook image post → uses /photos endpoint | |
| Facebook video post → uses /videos endpoint with file_url | |
| Instagram image post → container + publish flow | |
| Instagram video post → container with VIDEO type + publish | |
| Instagram carousel → child containers + CAROUSEL + publish | |
| Publish with disabled integration → all targets FAILED, post FAILED | |
| Publish with expired token → target FAILED with TOKEN_EXPIRED | |
| Job retries once then stops (2 max attempts) | |

### Existing Tests (must still pass)

- Social domain: 216 tests
- Admin domain: 333 tests
- Full suite: 3798 tests

---

## 12. Implementation Order

1. **PublishingService.php** — Integration gate + token check + logPublishFailure() (GAP-1, GAP-2, GAP-6)
2. **FacebookAdapter.php** — Fix media URL + add video branch (GAP-3, GAP-4)
3. **InstagramAdapter.php** — Fix media URL (GAP-3)
4. **PublishPostJob.php** — Reduce retries (GAP-5)
5. **Tests** — Unit + feature tests for all 6 gaps
6. **Run full suite** — Verify no regressions

---

## NOT Changed

- No new migrations
- No new routes
- No new controllers
- No new models or enums
- PostController::publish() — already correct
- PublishResult — already correct
- SocialPlatformAdapter interface — already correct
- PostStatus/PostTargetStatus enums — already correct
- Scheduling (explicitly out of scope)
- Frontend (no publishing UI in this step)
