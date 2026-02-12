export interface AudienceDemographicData {
  id: string
  social_account_id: string
  snapshot_date: string
  age_ranges: Record<string, number>
  gender_split: Record<string, number>
  top_countries: Record<string, number>[]
  top_cities: Record<string, number>[]
  follower_count: number
  created_at: string
}

export interface HashtagPerformanceData {
  id: string
  workspace_id: string
  hashtag: string
  platform: string
  usage_count: number
  avg_reach: number
  avg_engagement: number
  avg_impressions: number
  last_used_at: string | null
  created_at: string
}

export interface ScheduledReportData {
  id: string
  workspace_id: string
  name: string
  report_type: string
  frequency: 'weekly' | 'monthly' | 'quarterly'
  recipients: string[]
  parameters: Record<string, unknown>
  next_send_at: string | null
  is_active: boolean
  created_at: string
}

export interface CreateScheduledReportRequest {
  name: string
  report_type: string
  frequency: string
  recipients: string[]
  parameters?: Record<string, unknown>
}
