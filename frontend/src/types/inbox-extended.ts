export interface InboxItemTagData {
  id: string
  workspace_id: string
  name: string
  color: string
  created_at: string
}

export interface InboxInternalNoteData {
  id: string
  inbox_item_id: string
  user_id: string
  user_name?: string
  content: string
  created_at: string
}

export interface SavedReplyData {
  id: string
  workspace_id: string
  title: string
  content: string
  shortcut: string | null
  category: string | null
  usage_count: number
  created_at: string
}

export interface InboxContactData {
  id: string
  workspace_id: string
  platform: string
  platform_user_id: string
  display_name: string
  username: string | null
  avatar_url: string | null
  email: string | null
  phone: string | null
  notes: string | null
  tags: string[] | null
  first_seen_at: string
  last_seen_at: string
  interaction_count: number
  created_at: string
}

export interface InboxAutomationRuleData {
  id: string
  workspace_id: string
  name: string
  is_active: boolean
  trigger_type: string
  trigger_conditions: Record<string, unknown> | null
  action_type: string
  action_params: Record<string, unknown> | null
  priority: number
  execution_count: number
  created_at: string
}

export interface CreateInboxTagRequest {
  name: string
  color?: string
}

export interface CreateNoteRequest {
  content: string
}

export interface CreateSavedReplyRequest {
  title: string
  content: string
  shortcut?: string
  category?: string
}

export interface CreateInboxContactRequest {
  platform: string
  platform_user_id: string
  display_name: string
  username?: string
  email?: string
  phone?: string
  notes?: string
  tags?: string[]
}

export interface CreateAutomationRuleRequest {
  name: string
  trigger_type: string
  trigger_conditions?: Record<string, unknown>
  action_type: string
  action_params?: Record<string, unknown>
  priority?: number
}
