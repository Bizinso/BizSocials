# TC-010: AI Assist Test Cases

**Feature:** AI-Powered Content Suggestions
**Priority:** Medium
**Related Docs:** [API Contract - AI](../04_phase1_api_contract.md)

---

## Overview

Tests for AI-powered caption suggestions, hashtag recommendations, and content optimization. Phase-1 uses OpenAI API for basic suggestions (no auto-scheduling).

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── AI Settings:
│   └── ai_enabled: true
│
├── Industry: Marketing Agency
├── Brand Voice: Professional, Friendly
│
└── Posts for testing:
    ├── post-draft: Draft with no AI suggestions
    └── post-with-ai: Draft with generated suggestions

WORKSPACE B
└── AI Settings: ai_enabled: false

OPENAI TEST MODE
├── Mock API responses for testing
└── Rate limiting simulation
```

---

## Unit Tests (Codex to implement)

### UT-010-001: AI prompt construction
- **File:** `tests/Unit/Services/AIServiceTest.php`
- **Description:** Verify prompt is constructed with context
- **Test Pattern:**
```php
public function test_prompt_includes_brand_context(): void
{
    $workspace = Workspace::factory()->create([
        'settings' => [
            'brand_voice' => 'Professional',
            'industry' => 'Technology'
        ]
    ]);
    $post = Post::factory()->for($workspace)->create([
        'content' => 'Check out our new feature'
    ]);

    $prompt = $this->aiService->buildCaptionPrompt($post);

    $this->assertStringContains('Professional', $prompt);
    $this->assertStringContains('Technology', $prompt);
}
```
- **Status:** [ ] Pending

### UT-010-002: AI response parsing
- **File:** `tests/Unit/Services/AIServiceTest.php`
- **Description:** Verify OpenAI response is correctly parsed
- **Status:** [ ] Pending

### UT-010-003: AI suggestion storage
- **File:** `tests/Unit/Models/AISuggestionTest.php`
- **Description:** Verify suggestions are stored with metadata
- **Status:** [ ] Pending

### UT-010-004: Hashtag extraction and validation
- **File:** `tests/Unit/Services/AIServiceTest.php`
- **Description:** Verify hashtags are properly formatted
- **Status:** [ ] Pending

### UT-010-005: Platform-specific content limits
- **File:** `tests/Unit/Services/AIServiceTest.php`
- **Description:** Verify generated content respects platform limits
- **Status:** [ ] Pending

### UT-010-006: AI error handling
- **File:** `tests/Unit/Services/AIServiceTest.php`
- **Description:** Verify graceful handling of API errors
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-010-001: Generate caption suggestions
- **File:** `tests/Feature/Api/V1/AI/CaptionSuggestionsTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/ai/suggest-caption`
- **Request:**
```json
{
  "content": "Announcing our new product launch",
  "platform": "LINKEDIN",
  "tone": "professional"
}
```
- **Expected:** 200 OK, array of caption suggestions
- **Status:** [ ] Pending

### IT-010-002: Generate caption - no content
- **File:** `tests/Feature/Api/V1/AI/CaptionSuggestionsTest.php`
- **Request:** `{ "platform": "LINKEDIN" }` (missing content)
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-010-003: Generate hashtag suggestions
- **File:** `tests/Feature/Api/V1/AI/HashtagSuggestionsTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/ai/suggest-hashtags`
- **Request:**
```json
{
  "content": "Tips for successful social media marketing",
  "platform": "INSTAGRAM",
  "count": 10
}
```
- **Expected:** 200 OK, array of hashtags
- **Status:** [ ] Pending

### IT-010-004: Improve existing content
- **File:** `tests/Feature/Api/V1/AI/ImproveContentTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/ai/improve-content`
- **Request:**
```json
{
  "content": "Our product is good, you should buy it",
  "goal": "more_engaging"
}
```
- **Expected:** 200 OK, improved content versions
- **Status:** [ ] Pending

### IT-010-005: AI assist - workspace with AI disabled
- **File:** `tests/Feature/Api/V1/AI/PermissionsTest.php`
- **Setup:** Workspace B (AI disabled)
- **Expected:** 403 "AI features not enabled for this workspace"
- **Status:** [ ] Pending

### IT-010-006: AI assist - viewer can use
- **File:** `tests/Feature/Api/V1/AI/PermissionsTest.php`
- **Setup:** Viewer requests suggestions
- **Expected:** 200 OK (read-only AI is allowed)
- **Status:** [ ] Pending

### IT-010-007: AI assist - rate limiting
- **File:** `tests/Feature/Api/V1/AI/RateLimitTest.php`
- **Setup:** Exceed rate limit (e.g., 100 requests/hour)
- **Expected:** 429 Too Many Requests
- **Status:** [ ] Pending

### IT-010-008: Apply AI suggestion to post
- **File:** `tests/Feature/Api/V1/AI/ApplySuggestionTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/apply-suggestion`
- **Request:** `{ "suggestion_id": "ai-sugg-123" }`
- **Expected:** 200 OK, post content updated
- **Status:** [ ] Pending

### IT-010-009: Get post AI suggestions
- **File:** `tests/Feature/Api/V1/AI/GetSuggestionsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts/{post_id}/ai-suggestions`
- **Expected:** 200 OK, list of previous suggestions
- **Status:** [ ] Pending

### IT-010-010: Regenerate suggestions
- **File:** `tests/Feature/Api/V1/AI/RegenerateSuggestionsTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/posts/{post_id}/ai-suggestions/regenerate`
- **Expected:** 200 OK, new suggestions generated
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-010-001: Use AI to create post
- **File:** `tests/e2e/ai/ai-assisted-post.spec.ts`
- **Steps:**
  1. Login and create new post
  2. Enter brief content
  3. Click "AI Suggest"
  4. View suggestions
  5. Select a suggestion
  6. Verify content applied
  7. Generate hashtags
  8. Apply hashtags
  9. Save post
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-010-001: AI suggestion quality
- **Steps:**
  1. Generate suggestions for various industries
  2. Evaluate relevance and quality
  3. Test different tones (professional, casual, humorous)
  4. Verify platform-appropriate content
- **Status:** [ ] Not tested

### MT-010-002: AI response time
- **Steps:**
  1. Measure response time for caption generation
  2. Measure response time for hashtag generation
  3. Verify acceptable UX (<3 seconds)
- **Status:** [ ] Not tested

### MT-010-003: AI content safety
- **Steps:**
  1. Request suggestions with potentially problematic topics
  2. Verify AI doesn't generate inappropriate content
  3. Verify content moderation working
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-010-001: AI prompt injection
- **Attack:** Include malicious instructions in content
- **Expected:** Instructions ignored, normal response
- **Status:** [ ] Not tested

### ST-010-002: API key not exposed
- **Verify:** OpenAI API key not in client responses
- **Expected:** Key server-side only
- **Status:** [ ] Not tested

### ST-010-003: AI usage isolation
- **Attack:** Try to access other workspace's AI suggestions
- **Expected:** 404 Not Found
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 6 | - | - | 6 |
| Integration | 10 | - | - | 10 |
| E2E | 1 | - | - | 1 |
| Manual | 3 | - | - | 3 |
| Security | 3 | - | - | 3 |
| **Total** | **23** | **-** | **-** | **23** |

---

**Last Updated:** February 2026
**Status:** Draft
