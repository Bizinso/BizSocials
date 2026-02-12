export interface DashboardMetricsData {
  impressions: number
  reach: number
  engagements: number
  likes: number
  comments: number
  shares: number
  posts_published: number
  followers_total: number
  followers_gained: number
  engagement_rate: number
  impressions_change: number | null
  reach_change: number | null
  engagement_change: number | null
  followers_change: number | null
  period: string
  start_date: string
  end_date: string
}

export interface TrendDataPoint {
  date: string
  value: number
  previous_value: number | null
  change_percent: number | null
}

export interface PlatformMetricsData {
  platform: string
  platform_label: string
  impressions: number
  reach: number
  engagements: number
  likes: number
  comments: number
  shares: number
  posts_published: number
  followers_total: number
  followers_gained: number
  engagement_rate: number
  impressions_change: number | null
  reach_change: number | null
  engagement_change: number | null
  followers_change: number | null
}

export interface ContentPerformanceData {
  content_type: string
  content_type_label: string
  total_posts: number
  total_impressions: number
  total_reach: number
  total_engagements: number
  avg_impressions: number
  avg_reach: number
  avg_engagements: number
  avg_engagement_rate: number
  share_of_posts: number
  share_of_engagement: number
}

export interface TopPostData {
  id: string
  title: string
  content_excerpt: string | null
  platform: string
  platform_label: string
  thumbnail_url: string | null
  published_at: string
  impressions: number
  reach: number
  engagements: number
  likes: number
  comments: number
  shares: number
  engagement_rate: number
  rank: number
}

export interface BestTimesData {
  day: string
  hour: number
  engagement_rate: number
  post_count: number
}
