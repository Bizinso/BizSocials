import type { InboxItemStatus, InboxItemType, SocialPlatform } from './enums'

export interface InboxItemData {
  id: string
  workspace_id: string
  social_account_id: string
  post_target_id: string | null
  item_type: InboxItemType
  status: InboxItemStatus
  platform_item_id: string
  platform_post_id: string | null
  author_name: string
  author_username: string | null
  author_profile_url: string | null
  author_avatar_url: string | null
  content_text: string
  platform_created_at: string
  assigned_to_user_id: string | null
  assigned_to_name: string | null
  assigned_at: string | null
  resolved_at: string | null
  resolved_by_user_id: string | null
  resolved_by_name: string | null
  reply_count: number
  created_at: string
  updated_at: string
  platform: SocialPlatform | null
  account_name: string | null
}

export interface InboxReplyData {
  id: string
  inbox_item_id: string
  replied_by_user_id: string
  replied_by_name: string
  content_text: string
  platform_reply_id: string | null
  sent_at: string
  failed_at: string | null
  failure_reason: string | null
  created_at: string
  updated_at: string
}

export interface InboxStatsData {
  total: number
  unread: number
  read: number
  resolved: number
  archived: number
  assigned_to_me: number
  by_type: Record<string, number>
  by_platform: Record<string, number>
}

export interface CreateInboxReplyRequest {
  content_text: string
}

export interface AssignInboxItemRequest {
  user_id: string
}

export interface BulkInboxRequest {
  ids: string[]
}
