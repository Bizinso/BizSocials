# TC-008: Analytics Test Cases

**Feature:** Social Media Analytics & Reports
**Priority:** Medium
**Related Docs:** [API Contract - Analytics](../04_phase1_api_contract.md)

---

## Overview

Tests for analytics data collection, aggregation, and reporting. Phase-1 includes basic analytics (engagement metrics, post performance) but not advanced attribution.

---

## Test Environment Setup

```
WORKSPACE A ("Acme Agency")
├── Social Accounts:
│   ├── social-a1: LinkedIn (with historical data)
│   ├── social-a2: Facebook (with historical data)
│   └── social-a3: Instagram (with historical data)
│
├── Published Posts: 20 (with engagement data)
│
└── Analytics Data:
    ├── LinkedIn: followers, impressions, clicks
    ├── Facebook: reach, engagement, page_views
    └── Instagram: followers, impressions, reach

WORKSPACE B
└── Different analytics data (for isolation testing)
```

---

## Unit Tests (Codex to implement)

### UT-008-001: Analytics metric calculation
- **File:** `tests/Unit/Services/AnalyticsServiceTest.php`
- **Description:** Verify engagement rate calculation
- **Test Pattern:**
```php
public function test_engagement_rate_calculation(): void
{
    // Engagement Rate = (likes + comments + shares) / impressions * 100
    $metrics = [
        'likes' => 50,
        'comments' => 10,
        'shares' => 5,
        'impressions' => 1000
    ];

    $rate = $this->analyticsService->calculateEngagementRate($metrics);
    $this->assertEquals(6.5, $rate); // (50+10+5)/1000*100 = 6.5%
}
```
- **Status:** [ ] Pending

### UT-008-002: Analytics date range validation
- **File:** `tests/Unit/Requests/AnalyticsRequestTest.php`
- **Description:** Verify date range limits (max 90 days)
- **Status:** [ ] Pending

### UT-008-003: Analytics aggregation by period
- **File:** `tests/Unit/Services/AnalyticsServiceTest.php`
- **Description:** Verify daily, weekly, monthly aggregation
- **Status:** [ ] Pending

### UT-008-004: Platform-specific metric mapping
- **File:** `tests/Unit/Services/AnalyticsServiceTest.php`
- **Description:** Verify platform metrics normalized correctly
- **Status:** [ ] Pending

### UT-008-005: Analytics workspace scoping
- **File:** `tests/Unit/Models/AnalyticsSnapshotTest.php`
- **Description:** Verify analytics are workspace-scoped
- **Status:** [ ] Pending

### UT-008-006: Zero division handling
- **File:** `tests/Unit/Services/AnalyticsServiceTest.php`
- **Description:** Verify no errors when impressions = 0
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-008-001: Get workspace analytics overview
- **File:** `tests/Feature/Api/V1/Analytics/OverviewTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics`
- **Expected:** 200 OK, aggregated metrics across platforms
- **Status:** [ ] Pending

### IT-008-002: Get analytics with date range
- **File:** `tests/Feature/Api/V1/Analytics/OverviewTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics?from=2026-01-01&to=2026-01-31`
- **Expected:** 200 OK, metrics for specified period
- **Status:** [ ] Pending

### IT-008-003: Get analytics - invalid date range
- **File:** `tests/Feature/Api/V1/Analytics/OverviewTest.php`
- **Endpoint:** Date range > 90 days
- **Expected:** 422 Validation Error
- **Status:** [ ] Pending

### IT-008-004: Get platform-specific analytics
- **File:** `tests/Feature/Api/V1/Analytics/PlatformAnalyticsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics/platforms/linkedin`
- **Expected:** 200 OK, LinkedIn-specific metrics
- **Status:** [ ] Pending

### IT-008-005: Get social account analytics
- **File:** `tests/Feature/Api/V1/Analytics/AccountAnalyticsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/social-accounts/{account_id}/analytics`
- **Expected:** 200 OK, account-specific metrics
- **Status:** [ ] Pending

### IT-008-006: Get post performance analytics
- **File:** `tests/Feature/Api/V1/Analytics/PostAnalyticsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/posts/{post_id}/analytics`
- **Expected:** 200 OK, individual post metrics
- **Status:** [ ] Pending

### IT-008-007: Get top performing posts
- **File:** `tests/Feature/Api/V1/Analytics/TopPostsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics/top-posts`
- **Expected:** 200 OK, posts sorted by engagement
- **Status:** [ ] Pending

### IT-008-008: Get audience growth metrics
- **File:** `tests/Feature/Api/V1/Analytics/AudienceTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics/audience`
- **Expected:** 200 OK, follower counts and growth rate
- **Status:** [ ] Pending

### IT-008-009: Export analytics data
- **File:** `tests/Feature/Api/V1/Analytics/ExportTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics/export?format=csv`
- **Expected:** 200 OK, CSV download
- **Status:** [ ] Pending

### IT-008-010: Export analytics - PDF format
- **File:** `tests/Feature/Api/V1/Analytics/ExportTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/analytics/export?format=pdf`
- **Expected:** 200 OK, PDF download
- **Status:** [ ] Pending

### IT-008-011: Analytics - viewer can access
- **File:** `tests/Feature/Api/V1/Analytics/PermissionsTest.php`
- **Setup:** Viewer queries analytics
- **Expected:** 200 OK (read access)
- **Status:** [ ] Pending

### IT-008-012: Analytics - non-member forbidden
- **File:** `tests/Feature/Api/V1/Analytics/PermissionsTest.php`
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

### IT-008-013: Analytics - cross-workspace forbidden
- **File:** `tests/Feature/Api/V1/Analytics/PermissionsTest.php`
- **Setup:** Request Workspace B analytics from Workspace A
- **Expected:** 403 Forbidden
- **Status:** [ ] Pending

---

## Background Job Tests (Codex to implement)

### JT-008-001: FetchAnalyticsJob - daily sync
- **File:** `tests/Feature/Jobs/FetchAnalyticsJobTest.php`
- **Description:** Verify job fetches latest metrics from platforms
- **Status:** [ ] Pending

### JT-008-002: FetchAnalyticsJob - handles API errors
- **File:** `tests/Feature/Jobs/FetchAnalyticsJobTest.php`
- **Description:** Verify graceful handling of platform API failures
- **Status:** [ ] Pending

### JT-008-003: FetchAnalyticsJob - workspace scoped
- **File:** `tests/Feature/Jobs/FetchAnalyticsJobTest.php`
- **Description:** Verify job runs per workspace
- **Status:** [ ] Pending

### JT-008-004: GenerateReportJob - creates report
- **File:** `tests/Feature/Jobs/GenerateReportJobTest.php`
- **Description:** Verify scheduled reports generated correctly
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-008-001: View analytics dashboard
- **File:** `tests/e2e/analytics/dashboard.spec.ts`
- **Steps:**
  1. Login as workspace member
  2. Navigate to Analytics
  3. Verify charts displayed
  4. Verify metrics accurate
  5. Change date range
  6. Verify data updates
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-008-001: Real-time analytics accuracy
- **Steps:**
  1. Publish post to connected account
  2. Generate engagement (likes, comments) externally
  3. Trigger analytics sync
  4. Verify metrics match platform
- **Status:** [ ] Not tested

### MT-008-002: Analytics chart rendering
- **Steps:**
  1. View analytics with various data ranges
  2. Verify charts render correctly
  3. Verify tooltips show accurate data
  4. Verify responsive on mobile
- **Status:** [ ] Not tested

### MT-008-003: Export functionality
- **Steps:**
  1. Export analytics as CSV
  2. Open in spreadsheet
  3. Verify data completeness
  4. Export as PDF
  5. Verify formatting
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-008-001: Analytics data isolation
- **Attack:** Access analytics for other workspace
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

### ST-008-002: Export path traversal
- **Attack:** Manipulate export filename
- **Expected:** Sanitized filename used
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 6 | - | - | 6 |
| Integration | 13 | - | - | 13 |
| Job Tests | 4 | - | - | 4 |
| E2E | 1 | - | - | 1 |
| Manual | 3 | - | - | 3 |
| Security | 2 | - | - | 2 |
| **Total** | **29** | **-** | **-** | **29** |

---

**Last Updated:** February 2026
**Status:** Draft
