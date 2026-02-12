export interface WorkspaceDashboardData {
  total_posts: number
  posts_published: number
  posts_scheduled: number
  posts_draft: number
  pending_approvals: number
  social_accounts_count: number
  inbox_unread_count: number
  member_count: number
  recent_posts: RecentPostData[]
}

export interface RecentPostData {
  id: string
  content_excerpt: string | null
  status: string
  scheduled_at: string | null
  published_at: string | null
  created_at: string
}
