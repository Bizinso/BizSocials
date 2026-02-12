export interface FeedbackData {
  id: string
  title: string
  description: string
  type: string
  type_label: string
  category: string | null
  category_label: string | null
  status: string
  status_label: string
  vote_count: number
  comment_count: number
  submitter_name: string | null
  submitter_email: string | null
  is_anonymous: boolean
  user_vote: number | null
  roadmap_item_id: string | null
  created_at: string
  updated_at: string
}

export interface SubmitFeedbackRequest {
  title: string
  description: string
  type?: string
  category?: string
  email?: string
  name?: string
  is_anonymous?: boolean
}

export interface FeedbackCommentData {
  id: string
  feedback_id: string
  content: string
  author_name: string
  is_official_response: boolean
  created_at: string
}

export interface AddFeedbackCommentRequest {
  content: string
  commenter_name?: string
}

export interface VoteFeedbackRequest {
  vote_type?: string
}

export interface FeedbackStatsData {
  total_feedback: number
  new_feedback: number
  under_review: number
  planned: number
  shipped: number
  declined: number
  by_status: Record<string, number>
  by_type: Record<string, number>
  by_category: Record<string, number>
}

export interface RoadmapItemData {
  id: string
  title: string
  description: string | null
  category: string
  category_label: string
  status: string
  status_label: string
  target_quarter: string | null
  target_date: string | null
  progress_percentage: number
  feedback_count: number
  vote_count: number
  created_at: string
}

export interface CreateRoadmapItemRequest {
  title: string
  description?: string
  detailed_description?: string
  category?: string
  status?: string
  target_quarter?: string
  target_date?: string
  is_public?: boolean
}

export interface UpdateRoadmapItemRequest {
  title?: string
  description?: string
  detailed_description?: string
  category?: string
  status?: string
  target_quarter?: string
  target_date?: string
  progress_percentage?: number
  is_public?: boolean
}

export interface ReleaseNoteData {
  id: string
  version: string
  version_name: string | null
  title: string
  summary: string | null
  content: string
  content_format: string
  release_type: string
  status: string
  is_public: boolean
  published_at: string | null
  created_at: string
  items: ReleaseNoteItemData[]
}

export interface ReleaseNoteItemData {
  id: string
  title: string
  description: string | null
  change_type: string
  roadmap_item_id: string | null
  sort_order: number
}

export interface SubscribeChangelogRequest {
  email: string
  notify_major?: boolean
  notify_minor?: boolean
  notify_patch?: boolean
}
