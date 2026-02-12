export interface MonitoredKeywordData {
  id: string
  workspace_id: string
  keyword: string
  platforms: string[]
  is_active: boolean
  notify_on_match: boolean
  match_count: number
  created_at: string
}

export interface KeywordMentionData {
  id: string
  keyword_id: string
  platform: string
  author_name: string
  content_text: string
  sentiment: 'positive' | 'negative' | 'neutral' | 'unknown'
  url: string | null
  platform_created_at: string
  created_at: string
}

export interface CreateMonitoredKeywordRequest {
  keyword: string
  platforms: string[]
  notify_on_match?: boolean
}
