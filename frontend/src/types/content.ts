import type {
  PostStatus,
  PostType,
  PostTargetStatus,
  MediaType,
  MediaProcessingStatus,
  ApprovalDecisionType,
  SocialPlatform,
} from './enums'

export interface PostData {
  id: string
  workspace_id: string
  author_id: string
  author_name: string | null
  content_text: string | null
  content_variations: Record<string, unknown> | null
  status: PostStatus
  post_type: PostType
  scheduled_at: string | null
  scheduled_timezone: string | null
  published_at: string | null
  hashtags: string[] | null
  mentions: string[] | null
  link_url: string | null
  link_preview: Record<string, unknown> | null
  first_comment: string | null
  rejection_reason: string | null
  target_count: number
  media_count: number
  created_at: string
  updated_at: string
}

export interface PostDetailData {
  post: PostData
  targets: PostTargetData[]
  media: PostMediaData[]
}

export interface PostTargetData {
  id: string
  post_id: string
  social_account_id: string
  platform: SocialPlatform
  account_name: string
  status: PostTargetStatus
  platform_post_id: string | null
  platform_post_url: string | null
  published_at: string | null
  error_message: string | null
}

export interface PostMediaData {
  id: string
  post_id: string
  media_type: MediaType
  file_path: string
  file_url: string | null
  thumbnail_url: string | null
  original_filename: string | null
  file_size: number | null
  mime_type: string | null
  sort_order: number
  metadata: Record<string, unknown> | null
  processing_status: MediaProcessingStatus
}

export interface CreatePostRequest {
  content_text?: string | null
  content_variations?: Record<string, unknown> | null
  post_type?: PostType
  scheduled_at?: string | null
  scheduled_timezone?: string | null
  hashtags?: string[] | null
  mentions?: string[] | null
  link_url?: string | null
  first_comment?: string | null
  social_account_ids?: string[] | null
}

export interface UpdatePostRequest {
  content_text?: string | null
  content_variations?: Record<string, unknown> | null
  hashtags?: string[] | null
  mentions?: string[] | null
  link_url?: string | null
  first_comment?: string | null
}

export interface SchedulePostRequest {
  scheduled_at: string
  timezone?: string | null
}

export interface AttachMediaRequest {
  media_type: MediaType
  file_path: string
  file_url?: string | null
  thumbnail_url?: string | null
  original_filename?: string | null
  file_size?: number | null
  mime_type?: string | null
  sort_order?: number
  metadata?: Record<string, unknown> | null
}

export interface ApprovalDecisionData {
  id: string
  post_id: string
  decided_by_user_id: string
  decided_by_name: string | null
  decision: ApprovalDecisionType
  reason: string | null
  comment: string | null
  is_active: boolean
  decided_at: string
}

export interface ApprovePostRequest {
  comment?: string | null
}

export interface RejectPostRequest {
  reason: string
  comment?: string | null
}
