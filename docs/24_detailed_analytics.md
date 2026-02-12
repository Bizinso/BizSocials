# Detailed Analytics Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2025-02-06
- **Module**: Analytics & Reporting
- **Scope**: Platform Analytics + Usage Analytics

---

## 1. Overview

### 1.1 Analytics Philosophy
```
┌─────────────────────────────────────────────────────────────────┐
│              ANALYTICS COVERAGE                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  SOCIAL MEDIA ANALYTICS (From Platforms)                        │
│  ├── All metrics provided by each social platform API           │
│  ├── Real-time where available                                  │
│  ├── Historical data collection                                 │
│  └── Cross-platform aggregation                                 │
│                                                                 │
│  USAGE ANALYTICS (Platform Activity)                            │
│  ├── User activity tracking                                     │
│  ├── Feature usage metrics                                      │
│  ├── Content performance                                        │
│  └── Team productivity                                          │
│                                                                 │
│  BUSINESS ANALYTICS (For Super Admin)                           │
│  ├── Tenant growth & health                                     │
│  ├── Revenue metrics                                            │
│  ├── Platform usage trends                                      │
│  └── Feature adoption                                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Social Media Platform Analytics

### 2.1 LinkedIn Analytics
```
┌─────────────────────────────────────────────────────────────────┐
│                 LINKEDIN METRICS                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  POST METRICS                                                   │
│  ├── Impressions (total views)                                  │
│  ├── Unique impressions                                         │
│  ├── Clicks (total)                                             │
│  │   ├── Content clicks                                         │
│  │   ├── Link clicks                                            │
│  │   ├── Other clicks (hashtag, company, etc.)                  │
│  ├── Reactions (by type)                                        │
│  │   ├── Like                                                   │
│  │   ├── Celebrate                                              │
│  │   ├── Support                                                │
│  │   ├── Love                                                   │
│  │   ├── Insightful                                             │
│  │   └── Funny                                                  │
│  ├── Comments                                                   │
│  ├── Shares (reposts)                                           │
│  ├── Engagement rate                                            │
│  └── Video metrics (if applicable)                              │
│      ├── Video views                                            │
│      ├── Watch time                                             │
│      └── Completion rate                                        │
│                                                                 │
│  PAGE/PROFILE METRICS                                           │
│  ├── Followers                                                  │
│  │   ├── Total followers                                        │
│  │   ├── New followers (period)                                 │
│  │   └── Follower demographics                                  │
│  ├── Profile views                                              │
│  ├── Post impressions (aggregate)                               │
│  ├── Unique visitors                                            │
│  └── Search appearances                                         │
│                                                                 │
│  AUDIENCE INSIGHTS                                              │
│  ├── Demographics                                               │
│  │   ├── Location (country, region)                             │
│  │   ├── Industry                                               │
│  │   ├── Company size                                           │
│  │   ├── Job function                                           │
│  │   └── Seniority                                              │
│  └── Growth trends                                              │
│                                                                 │
│  COLLECTION FREQUENCY                                           │
│  ├── Post metrics: Every 4 hours (first 7 days)                 │
│  ├── Post metrics: Daily (7-30 days)                            │
│  ├── Post metrics: Weekly (30+ days)                            │
│  └── Page metrics: Daily                                        │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Facebook Analytics
```
┌─────────────────────────────────────────────────────────────────┐
│                 FACEBOOK METRICS                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  POST METRICS                                                   │
│  ├── Reach                                                      │
│  │   ├── Organic reach                                          │
│  │   ├── Paid reach (if applicable)                             │
│  │   └── Viral reach                                            │
│  ├── Impressions                                                │
│  │   ├── Organic impressions                                    │
│  │   ├── Paid impressions                                       │
│  │   └── Viral impressions                                      │
│  ├── Engagement                                                 │
│  │   ├── Reactions (by type)                                    │
│  │   ├── Comments                                               │
│  │   ├── Shares                                                 │
│  │   ├── Clicks                                                 │
│  │   │   ├── Link clicks                                        │
│  │   │   ├── Photo views                                        │
│  │   │   └── Other clicks                                       │
│  │   └── Engagement rate                                        │
│  ├── Video metrics                                              │
│  │   ├── Video views (3 sec+)                                   │
│  │   ├── Video views (10 sec+)                                  │
│  │   ├── Average watch time                                     │
│  │   ├── Video retention                                        │
│  │   └── Sound on/off ratio                                     │
│  └── Negative feedback                                          │
│      ├── Hide post                                              │
│      ├── Report as spam                                         │
│      └── Unlike page                                            │
│                                                                 │
│  PAGE METRICS                                                   │
│  ├── Page likes                                                 │
│  │   ├── Total likes                                            │
│  │   ├── New likes                                              │
│  │   └── Unlikes                                                │
│  ├── Page followers                                             │
│  ├── Page reach                                                 │
│  ├── Page views                                                 │
│  ├── Actions on page                                            │
│  │   ├── Website clicks                                         │
│  │   ├── Phone clicks                                           │
│  │   ├── Direction clicks                                       │
│  │   └── CTA button clicks                                      │
│  └── Response rate & time                                       │
│                                                                 │
│  AUDIENCE INSIGHTS                                              │
│  ├── Demographics                                               │
│  │   ├── Age & gender                                           │
│  │   ├── Location (country, city)                               │
│  │   └── Language                                               │
│  ├── When fans are online                                       │
│  └── Top countries/cities                                       │
└─────────────────────────────────────────────────────────────────┘
```

### 2.3 Instagram Analytics
```
┌─────────────────────────────────────────────────────────────────┐
│                 INSTAGRAM METRICS                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  POST METRICS (Feed, Reels, Carousel)                           │
│  ├── Reach                                                      │
│  ├── Impressions                                                │
│  ├── Engagement                                                 │
│  │   ├── Likes                                                  │
│  │   ├── Comments                                               │
│  │   ├── Shares                                                 │
│  │   ├── Saves                                                  │
│  │   └── Engagement rate                                        │
│  ├── Profile visits (from post)                                 │
│  ├── Website clicks (from post)                                 │
│  └── Follows (from post)                                        │
│                                                                 │
│  REELS SPECIFIC                                                 │
│  ├── Plays                                                      │
│  ├── Replays                                                    │
│  ├── Average watch time                                         │
│  ├── Watch through rate                                         │
│  └── Audio usage                                                │
│                                                                 │
│  STORIES METRICS                                                │
│  ├── Reach                                                      │
│  ├── Impressions                                                │
│  ├── Exits                                                      │
│  ├── Replies                                                    │
│  ├── Taps forward/back                                          │
│  ├── Link clicks (swipe up)                                     │
│  └── Sticker interactions                                       │
│                                                                 │
│  ACCOUNT METRICS                                                │
│  ├── Followers                                                  │
│  │   ├── Total                                                  │
│  │   ├── Growth (new/lost)                                      │
│  │   └── Demographics                                           │
│  ├── Profile views                                              │
│  ├── Website clicks                                             │
│  ├── Email clicks                                               │
│  ├── Call/direction clicks                                      │
│  └── Content interactions                                       │
│                                                                 │
│  AUDIENCE INSIGHTS                                              │
│  ├── Age range                                                  │
│  ├── Gender                                                     │
│  ├── Top locations                                              │
│  ├── Most active times                                          │
│  └── Follower growth chart                                      │
└─────────────────────────────────────────────────────────────────┘
```

### 2.4 Twitter/X Analytics
```
┌─────────────────────────────────────────────────────────────────┐
│                 TWITTER/X METRICS                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  TWEET METRICS                                                  │
│  ├── Impressions                                                │
│  ├── Engagements                                                │
│  │   ├── Likes                                                  │
│  │   ├── Retweets                                               │
│  │   ├── Quote tweets                                           │
│  │   ├── Replies                                                │
│  │   ├── Bookmarks                                              │
│  │   ├── Link clicks                                            │
│  │   ├── Profile clicks                                         │
│  │   ├── Media views                                            │
│  │   └── Detail expands                                         │
│  ├── Engagement rate                                            │
│  ├── Video metrics                                              │
│  │   ├── Video views                                            │
│  │   ├── Video completion rate                                  │
│  │   └── Video watch time                                       │
│  └── Hashtag performance                                        │
│                                                                 │
│  PROFILE METRICS                                                │
│  ├── Followers                                                  │
│  │   ├── Total                                                  │
│  │   ├── New followers                                          │
│  │   └── Lost followers                                         │
│  ├── Profile visits                                             │
│  ├── Mentions                                                   │
│  └── Reach                                                      │
│                                                                 │
│  AUDIENCE INSIGHTS                                              │
│  ├── Top interests                                              │
│  ├── Demographics                                               │
│  └── Active hours                                               │
└─────────────────────────────────────────────────────────────────┘
```

### 2.5 YouTube Analytics
```
┌─────────────────────────────────────────────────────────────────┐
│                 YOUTUBE METRICS                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  VIDEO METRICS                                                  │
│  ├── Views                                                      │
│  ├── Watch time (hours)                                         │
│  ├── Average view duration                                      │
│  ├── Average percentage viewed                                  │
│  ├── Likes/dislikes                                             │
│  ├── Comments                                                   │
│  ├── Shares                                                     │
│  ├── Subscribers gained                                         │
│  ├── Subscribers lost                                           │
│  ├── Click-through rate (CTR)                                   │
│  ├── Impressions                                                │
│  └── Traffic sources                                            │
│      ├── YouTube search                                         │
│      ├── Suggested videos                                       │
│      ├── Browse features                                        │
│      ├── External                                               │
│      └── Direct/unknown                                         │
│                                                                 │
│  AUDIENCE RETENTION                                             │
│  ├── Retention curve                                            │
│  ├── Key moments                                                │
│  │   ├── Intro                                                  │
│  │   ├── Continuous segments                                    │
│  │   └── Drop-off points                                        │
│  └── Relative audience retention                                │
│                                                                 │
│  CHANNEL METRICS                                                │
│  ├── Subscribers                                                │
│  │   ├── Total                                                  │
│  │   ├── Gained                                                 │
│  │   └── Lost                                                   │
│  ├── Total views                                                │
│  ├── Total watch time                                           │
│  ├── RPM (Revenue per mille, if monetized)                      │
│  └── Top videos                                                 │
│                                                                 │
│  AUDIENCE DEMOGRAPHICS                                          │
│  ├── Age                                                        │
│  ├── Gender                                                     │
│  ├── Geography                                                  │
│  ├── Subscription status                                        │
│  └── Device type                                                │
└─────────────────────────────────────────────────────────────────┘
```

### 2.6 TikTok Analytics
```
┌─────────────────────────────────────────────────────────────────┐
│                 TIKTOK METRICS                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  VIDEO METRICS                                                  │
│  ├── Views                                                      │
│  ├── Likes                                                      │
│  ├── Comments                                                   │
│  ├── Shares                                                     │
│  ├── Saves                                                      │
│  ├── Average watch time                                         │
│  ├── Watched full video (%)                                     │
│  ├── Traffic source                                             │
│  │   ├── For You page                                           │
│  │   ├── Following feed                                         │
│  │   ├── Profile                                                │
│  │   ├── Search                                                 │
│  │   └── Sound                                                  │
│  └── Reach by location                                          │
│                                                                 │
│  PROFILE METRICS                                                │
│  ├── Video views                                                │
│  ├── Profile views                                              │
│  ├── Followers                                                  │
│  │   ├── Total                                                  │
│  │   ├── Growth                                                 │
│  │   └── Demographics                                           │
│  └── LIVE metrics (if applicable)                               │
│                                                                 │
│  AUDIENCE INSIGHTS                                              │
│  ├── Gender distribution                                        │
│  ├── Age ranges                                                 │
│  ├── Top territories                                            │
│  └── Follower activity (when online)                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Usage Analytics (Platform Activity)

### 3.1 User Activity Metrics
```sql
-- User Activity Tracking
CREATE TABLE user_activity_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    workspace_id BIGINT UNSIGNED NULL,

    -- Activity
    activity_type VARCHAR(50) NOT NULL,
    activity_category VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50) NULL,
    resource_id VARCHAR(100) NULL,

    -- Context
    page_url VARCHAR(500) NULL,
    referrer_url VARCHAR(500) NULL,
    session_id VARCHAR(100) NULL,

    -- Device
    device_type ENUM('desktop', 'mobile', 'tablet') NULL,
    browser VARCHAR(50) NULL,
    os VARCHAR(50) NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant_user (tenant_id, user_id, created_at),
    INDEX idx_activity (activity_type, created_at),
    INDEX idx_session (session_id)
);

-- Activity Categories
/*
 * content_creation: post_created, post_edited, media_uploaded
 * publishing: post_scheduled, post_published, post_cancelled
 * engagement: inbox_viewed, reply_sent, comment_liked
 * analytics: dashboard_viewed, report_generated, export_created
 * settings: account_connected, settings_changed, team_invited
 * ai: caption_generated, hashtag_suggested, best_time_checked
 */
```

### 3.2 Feature Usage Metrics
```php
<?php

namespace App\Services\Analytics;

class UsageAnalyticsService
{
    /**
     * Track feature usage for analytics
     */
    public function trackFeatureUsage(string $feature, array $metadata = []): void
    {
        $data = [
            'tenant_id' => app('tenant.id'),
            'user_id' => auth()->id(),
            'feature' => $feature,
            'metadata' => $metadata,
            'session_id' => session()->getId(),
            'created_at' => now(),
        ];

        // Async write to avoid latency
        dispatch(new RecordFeatureUsage($data));
    }

    /**
     * Get feature adoption metrics for a tenant
     */
    public function getFeatureAdoption(int $tenantId, string $period = '30d'): array
    {
        $startDate = $this->parseStartDate($period);

        return [
            'post_creation' => $this->getUsageStats($tenantId, 'post_create', $startDate),
            'scheduling' => $this->getUsageStats($tenantId, 'post_schedule', $startDate),
            'ai_caption' => $this->getUsageStats($tenantId, 'ai_caption_generate', $startDate),
            'ai_hashtag' => $this->getUsageStats($tenantId, 'ai_hashtag_suggest', $startDate),
            'analytics_view' => $this->getUsageStats($tenantId, 'analytics_view', $startDate),
            'inbox_usage' => $this->getUsageStats($tenantId, 'inbox_view', $startDate),
            'team_collaboration' => $this->getUsageStats($tenantId, 'approval_submit', $startDate),
            'media_upload' => $this->getUsageStats($tenantId, 'media_upload', $startDate),
        ];
    }

    private function getUsageStats(int $tenantId, string $feature, \DateTime $startDate): array
    {
        $total = \DB::table('feature_usage_logs')
            ->where('tenant_id', $tenantId)
            ->where('feature', $feature)
            ->where('created_at', '>=', $startDate)
            ->count();

        $uniqueUsers = \DB::table('feature_usage_logs')
            ->where('tenant_id', $tenantId)
            ->where('feature', $feature)
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count();

        $dailyAvg = $total / max(1, $startDate->diffInDays(now()));

        return [
            'total_uses' => $total,
            'unique_users' => $uniqueUsers,
            'daily_average' => round($dailyAvg, 1),
        ];
    }
}
```

### 3.3 Content Performance Metrics
```php
<?php

namespace App\Services\Analytics;

class ContentPerformanceService
{
    public function getPerformanceOverview(int $workspaceId, string $period = '30d'): array
    {
        $startDate = $this->parseStartDate($period);

        // Get all published posts in period
        $posts = Post::where('workspace_id', $workspaceId)
            ->where('published_at', '>=', $startDate)
            ->where('status', 'published')
            ->with(['analytics', 'targets.socialAccount'])
            ->get();

        return [
            'summary' => [
                'total_posts' => $posts->count(),
                'total_reach' => $posts->sum('analytics.reach'),
                'total_impressions' => $posts->sum('analytics.impressions'),
                'total_engagement' => $posts->sum('analytics.total_engagement'),
                'avg_engagement_rate' => $this->calculateAvgEngagementRate($posts),
            ],
            'by_platform' => $this->groupByPlatform($posts),
            'by_content_type' => $this->groupByContentType($posts),
            'top_performing' => $this->getTopPosts($posts, 5),
            'worst_performing' => $this->getWorstPosts($posts, 5),
            'posting_frequency' => $this->getPostingFrequency($posts),
            'best_posting_times' => $this->analyzeBestTimes($posts),
            'engagement_trend' => $this->getEngagementTrend($workspaceId, $startDate),
        ];
    }

    private function groupByPlatform(Collection $posts): array
    {
        return $posts->groupBy('targets.0.socialAccount.platform')
            ->map(function ($platformPosts, $platform) {
                return [
                    'platform' => $platform,
                    'posts' => $platformPosts->count(),
                    'reach' => $platformPosts->sum('analytics.reach'),
                    'engagement' => $platformPosts->sum('analytics.total_engagement'),
                    'engagement_rate' => $this->calculateAvgEngagementRate($platformPosts),
                ];
            })
            ->values()
            ->toArray();
    }

    private function groupByContentType(Collection $posts): array
    {
        return $posts->groupBy('content_type') // text, image, video, carousel
            ->map(function ($typePosts, $type) {
                return [
                    'type' => $type,
                    'posts' => $typePosts->count(),
                    'avg_engagement_rate' => $this->calculateAvgEngagementRate($typePosts),
                    'avg_reach' => $typePosts->avg('analytics.reach'),
                ];
            })
            ->values()
            ->toArray();
    }

    private function analyzeBestTimes(Collection $posts): array
    {
        return $posts->groupBy(function ($post) {
            return $post->published_at->format('l H'); // "Monday 14"
        })
        ->map(function ($timePosts, $time) {
            return [
                'time' => $time,
                'posts' => $timePosts->count(),
                'avg_engagement_rate' => $this->calculateAvgEngagementRate($timePosts),
            ];
        })
        ->sortByDesc('avg_engagement_rate')
        ->take(10)
        ->values()
        ->toArray();
    }
}
```

---

## 4. Analytics Dashboard

### 4.1 Dashboard Components
```vue
<template>
  <div class="analytics-dashboard">
    <!-- Period Selector -->
    <div class="dashboard-header">
      <h1>Analytics</h1>
      <div class="period-selector">
        <button
          v-for="period in periods"
          :key="period.value"
          :class="{ active: selectedPeriod === period.value }"
          @click="selectedPeriod = period.value"
        >
          {{ period.label }}
        </button>
        <DateRangePicker
          v-if="selectedPeriod === 'custom'"
          v-model="customDateRange"
        />
      </div>
    </div>

    <!-- Overview Cards -->
    <div class="overview-cards">
      <MetricCard
        title="Total Reach"
        :value="formatNumber(metrics.reach)"
        :change="metrics.reachChange"
        :previousValue="metrics.previousReach"
        icon="visibility"
      />
      <MetricCard
        title="Total Impressions"
        :value="formatNumber(metrics.impressions)"
        :change="metrics.impressionsChange"
        icon="eye"
      />
      <MetricCard
        title="Total Engagement"
        :value="formatNumber(metrics.engagement)"
        :change="metrics.engagementChange"
        icon="heart"
      />
      <MetricCard
        title="Engagement Rate"
        :value="`${metrics.engagementRate}%`"
        :change="metrics.engagementRateChange"
        icon="trending-up"
      />
      <MetricCard
        title="Posts Published"
        :value="metrics.postsPublished"
        :change="metrics.postsChange"
        icon="send"
      />
      <MetricCard
        title="New Followers"
        :value="formatNumber(metrics.newFollowers)"
        :change="metrics.followersChange"
        icon="users"
      />
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
      <!-- Engagement Trend -->
      <div class="chart-card full-width">
        <h3>Engagement Trend</h3>
        <LineChart
          :data="engagementTrendData"
          :options="chartOptions"
        />
      </div>

      <!-- Platform Breakdown -->
      <div class="chart-card">
        <h3>Platform Performance</h3>
        <BarChart :data="platformData" />
      </div>

      <!-- Content Type Performance -->
      <div class="chart-card">
        <h3>Content Type Analysis</h3>
        <DonutChart :data="contentTypeData" />
      </div>

      <!-- Best Posting Times -->
      <div class="chart-card">
        <h3>Best Posting Times</h3>
        <HeatmapChart :data="bestTimesData" />
      </div>

      <!-- Audience Growth -->
      <div class="chart-card">
        <h3>Audience Growth</h3>
        <AreaChart :data="audienceGrowthData" />
      </div>
    </div>

    <!-- Top Performing Posts -->
    <div class="top-posts-section">
      <h3>Top Performing Posts</h3>
      <div class="posts-grid">
        <PostPerformanceCard
          v-for="post in topPosts"
          :key="post.id"
          :post="post"
        />
      </div>
    </div>

    <!-- Detailed Tables -->
    <div class="detailed-analytics">
      <Tabs>
        <Tab label="By Account">
          <AccountAnalyticsTable :data="accountAnalytics" />
        </Tab>
        <Tab label="By Post">
          <PostAnalyticsTable :data="postAnalytics" />
        </Tab>
        <Tab label="Audience">
          <AudienceAnalyticsTable :data="audienceAnalytics" />
        </Tab>
      </Tabs>
    </div>
  </div>
</template>
```

### 4.2 Account-Level Analytics Table
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      ACCOUNT ANALYTICS TABLE                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Account        │ Platform  │ Followers │ Posts │ Reach    │ Eng Rate │    │
│  ───────────────┼───────────┼───────────┼───────┼──────────┼──────────┼──  │
│  @company       │ LinkedIn  │ 15,432    │ 24    │ 125,000  │ 4.2%     │ ↑  │
│  @company.page  │ Facebook  │ 8,921     │ 18    │ 89,000   │ 2.8%     │ ↓  │
│  @company_ig    │ Instagram │ 22,100    │ 32    │ 245,000  │ 5.1%     │ ↑  │
│  @company       │ Twitter   │ 5,670     │ 45    │ 67,000   │ 1.9%     │ →  │
│  CompanyYT      │ YouTube   │ 3,200     │ 8     │ 45,000   │ 6.2%     │ ↑  │
│  ───────────────┴───────────┴───────────┴───────┴──────────┴──────────┴──  │
│                                                                             │
│  [Export CSV]  [Export PDF]  [Schedule Report]                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Custom Reports

### 5.1 Report Builder
```php
<?php

namespace App\Services\Reports;

class CustomReportService
{
    public function buildReport(array $config): array
    {
        $builder = new ReportBuilder($config);

        // Apply date range
        $builder->setDateRange($config['start_date'], $config['end_date']);

        // Apply filters
        if (!empty($config['platforms'])) {
            $builder->filterByPlatforms($config['platforms']);
        }

        if (!empty($config['accounts'])) {
            $builder->filterByAccounts($config['accounts']);
        }

        if (!empty($config['workspaces'])) {
            $builder->filterByWorkspaces($config['workspaces']);
        }

        // Select metrics
        foreach ($config['metrics'] as $metric) {
            $builder->addMetric($metric);
        }

        // Apply grouping
        if (!empty($config['group_by'])) {
            $builder->groupBy($config['group_by']); // day, week, month, platform, account
        }

        // Generate report
        return $builder->generate();
    }

    public function scheduleReport(int $tenantId, array $config, array $schedule): void
    {
        ScheduledReport::create([
            'tenant_id' => $tenantId,
            'name' => $config['name'],
            'config' => $config,
            'frequency' => $schedule['frequency'], // daily, weekly, monthly
            'day_of_week' => $schedule['day_of_week'] ?? null,
            'day_of_month' => $schedule['day_of_month'] ?? null,
            'time' => $schedule['time'],
            'recipients' => $schedule['recipients'],
            'format' => $schedule['format'], // pdf, csv, excel
            'is_active' => true,
        ]);
    }

    public function exportReport(array $data, string $format): string
    {
        return match ($format) {
            'pdf' => $this->exportToPdf($data),
            'csv' => $this->exportToCsv($data),
            'excel' => $this->exportToExcel($data),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }
}
```

### 5.2 Available Metrics
```
┌─────────────────────────────────────────────────────────────────┐
│              AVAILABLE REPORT METRICS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  REACH & AWARENESS                                              │
│  ├── Total Reach                                                │
│  ├── Unique Reach                                               │
│  ├── Impressions                                                │
│  ├── Video Views                                                │
│  └── Profile/Page Views                                         │
│                                                                 │
│  ENGAGEMENT                                                     │
│  ├── Total Engagement                                           │
│  ├── Engagement Rate                                            │
│  ├── Likes/Reactions                                            │
│  ├── Comments                                                   │
│  ├── Shares/Reposts                                             │
│  ├── Saves/Bookmarks                                            │
│  └── Clicks (link, media, profile)                              │
│                                                                 │
│  AUDIENCE                                                       │
│  ├── Total Followers                                            │
│  ├── Follower Growth                                            │
│  ├── Follower Demographics                                      │
│  └── Audience Activity Times                                    │
│                                                                 │
│  CONTENT                                                        │
│  ├── Posts Published                                            │
│  ├── Posts by Type                                              │
│  ├── Posts by Platform                                          │
│  ├── Average Performance                                        │
│  └── Top/Bottom Performers                                      │
│                                                                 │
│  PRODUCTIVITY                                                   │
│  ├── Posts Created                                              │
│  ├── Posts Scheduled                                            │
│  ├── Team Activity                                              │
│  ├── Response Time (Inbox)                                      │
│  └── Approval Turnaround                                        │
│                                                                 │
│  COMPARISON                                                     │
│  ├── Period over Period                                         │
│  ├── Platform Comparison                                        │
│  ├── Account Comparison                                         │
│  └── Team Member Comparison                                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 6. Data Collection & Storage

### 6.1 Analytics Data Model
```sql
-- Post-level analytics (collected from platforms)
CREATE TABLE post_analytics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    post_id BIGINT UNSIGNED NOT NULL,
    post_target_id BIGINT UNSIGNED NOT NULL,
    social_account_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,

    -- Core Metrics
    impressions INT DEFAULT 0,
    reach INT DEFAULT 0,
    engagement INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0,

    -- Engagement Breakdown
    likes INT DEFAULT 0,
    comments INT DEFAULT 0,
    shares INT DEFAULT 0,
    saves INT DEFAULT 0,
    clicks INT DEFAULT 0,
    link_clicks INT DEFAULT 0,
    profile_clicks INT DEFAULT 0,

    -- Video Metrics (if applicable)
    video_views INT DEFAULT 0,
    video_watch_time INT DEFAULT 0,
    video_completion_rate DECIMAL(5,2) DEFAULT 0,

    -- Platform-specific (JSON for flexibility)
    platform_metrics JSON NULL,

    -- Collection metadata
    collected_at TIMESTAMP NOT NULL,
    collection_source ENUM('api', 'webhook', 'manual') DEFAULT 'api',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id, collected_at),
    INDEX idx_account (social_account_id, collected_at),
    INDEX idx_tenant (tenant_id, collected_at)
);

-- Daily aggregated analytics
CREATE TABLE analytics_daily (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    workspace_id BIGINT UNSIGNED NULL,
    social_account_id BIGINT UNSIGNED NULL,

    -- Date
    date DATE NOT NULL,

    -- Aggregated Metrics
    total_posts INT DEFAULT 0,
    total_impressions BIGINT DEFAULT 0,
    total_reach BIGINT DEFAULT 0,
    total_engagement BIGINT DEFAULT 0,
    avg_engagement_rate DECIMAL(5,2) DEFAULT 0,

    total_likes BIGINT DEFAULT 0,
    total_comments BIGINT DEFAULT 0,
    total_shares BIGINT DEFAULT 0,
    total_saves BIGINT DEFAULT 0,
    total_clicks BIGINT DEFAULT 0,

    -- Follower Metrics
    followers_start INT DEFAULT 0,
    followers_end INT DEFAULT 0,
    followers_gained INT DEFAULT 0,
    followers_lost INT DEFAULT 0,

    -- Content Breakdown
    posts_by_type JSON NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_day (tenant_id, social_account_id, date),
    INDEX idx_tenant_date (tenant_id, date),
    INDEX idx_account_date (social_account_id, date)
);

-- Account analytics snapshots
CREATE TABLE account_analytics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    social_account_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,

    -- Snapshot Time
    snapshot_date DATE NOT NULL,

    -- Follower Metrics
    followers INT DEFAULT 0,
    following INT DEFAULT 0,

    -- Demographics (JSON for flexibility)
    demographics JSON NULL,
    /*
    {
        "age_ranges": {"18-24": 15, "25-34": 35, ...},
        "gender": {"male": 55, "female": 42, "other": 3},
        "locations": [{"country": "IN", "count": 5000}, ...],
        "interests": ["technology", "business", ...]
    }
    */

    -- Activity Insights
    active_times JSON NULL,
    /*
    {
        "monday": [0, 0, 0, 1, 2, 5, 8, 12, ...],
        "tuesday": [...],
        ...
    }
    */

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_snapshot (social_account_id, snapshot_date),
    INDEX idx_account (social_account_id, snapshot_date)
);
```

---

## 7. API Endpoints

### 7.1 Analytics API
```
# Overview
GET  /api/v1/analytics/overview
     ?period={7d|30d|90d|custom}
     &start_date={date}
     &end_date={date}
     &workspace_id={id}

# Post Analytics
GET  /api/v1/analytics/posts
     ?period={period}
     &platform={platform}
     &sort_by={reach|engagement|engagement_rate}
     &order={asc|desc}

GET  /api/v1/analytics/posts/{post_id}

# Account Analytics
GET  /api/v1/analytics/accounts
GET  /api/v1/analytics/accounts/{account_id}
GET  /api/v1/analytics/accounts/{account_id}/audience
GET  /api/v1/analytics/accounts/{account_id}/growth

# Content Analysis
GET  /api/v1/analytics/content/performance
GET  /api/v1/analytics/content/best-times
GET  /api/v1/analytics/content/top-posts
GET  /api/v1/analytics/content/hashtags

# Comparison
GET  /api/v1/analytics/compare/periods
GET  /api/v1/analytics/compare/platforms
GET  /api/v1/analytics/compare/accounts

# Reports
GET  /api/v1/analytics/reports
POST /api/v1/analytics/reports
GET  /api/v1/analytics/reports/{id}
GET  /api/v1/analytics/reports/{id}/download
POST /api/v1/analytics/reports/{id}/schedule
DELETE /api/v1/analytics/reports/{id}

# Export
POST /api/v1/analytics/export
     {
       "metrics": ["reach", "engagement", ...],
       "period": "30d",
       "format": "csv"
     }
```

---

## 8. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-02-06 | System | Initial specification |
