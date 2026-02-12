// Content Categories
export interface ContentCategoryData {
  id: string
  workspace_id: string
  name: string
  slug: string
  color: string | null
  description: string | null
  sort_order: number
  created_at: string
  updated_at: string
}

export interface CreateContentCategoryRequest {
  name: string
  color?: string
  description?: string
}

// Hashtag Groups
export interface HashtagGroupData {
  id: string
  workspace_id: string
  name: string
  hashtags: string[]
  description: string | null
  usage_count: number
  created_at: string
  updated_at: string
}

export interface CreateHashtagGroupRequest {
  name: string
  hashtags: string[]
  description?: string
}

// Short Links
export interface ShortLinkData {
  id: string
  workspace_id: string
  original_url: string
  short_code: string
  custom_alias: string | null
  title: string | null
  click_count: number
  utm_source: string | null
  utm_medium: string | null
  utm_campaign: string | null
  utm_term: string | null
  utm_content: string | null
  expires_at: string | null
  full_url: string
  created_at: string
  updated_at: string
}

export interface CreateShortLinkRequest {
  original_url: string
  title?: string
  custom_alias?: string
  utm_source?: string
  utm_medium?: string
  utm_campaign?: string
  utm_term?: string
  utm_content?: string
  expires_at?: string
}

export interface ShortLinkStatsData {
  click_count: number
  recent_clicks: { clicked_at: string; country: string | null; device_type: string | null }[]
  device_breakdown: Record<string, number>
}

// RSS Feeds
export interface RssFeedData {
  id: string
  workspace_id: string
  url: string
  name: string
  is_active: boolean
  auto_schedule: boolean
  category_id: string | null
  last_fetched_at: string | null
  fetch_interval_hours: number
  created_at: string
  updated_at: string
}

export interface RssFeedItemData {
  id: string
  rss_feed_id: string
  guid: string
  title: string
  link: string
  description: string | null
  image_url: string | null
  published_at: string | null
  is_used: boolean
  created_at: string
}

export interface CreateRssFeedRequest {
  url: string
  name: string
  category_id?: string
  fetch_interval_hours?: number
}

// Evergreen
export interface EvergreenRuleData {
  id: string
  workspace_id: string
  name: string
  is_active: boolean
  source_category_id: string | null
  social_account_ids: string[]
  repost_interval_days: number
  max_reposts: number
  time_slots: { day: number; hour: number }[] | null
  content_variation: boolean
  last_reposted_at: string | null
  created_at: string
  updated_at: string
}

export interface EvergreenPostPoolData {
  id: string
  evergreen_rule_id: string
  post_id: string
  repost_count: number
  next_repost_at: string | null
  is_active: boolean
}

export interface CreateEvergreenRuleRequest {
  name: string
  source_category_id?: string
  social_account_ids: string[]
  repost_interval_days: number
  max_reposts: number
  time_slots?: { day: number; hour: number }[]
  content_variation?: boolean
}
