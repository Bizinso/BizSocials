export type WhatsAppAutomationTrigger = 'new_conversation' | 'keyword_match' | 'outside_business_hours' | 'no_response_timeout'
export type WhatsAppAutomationAction = 'auto_reply' | 'assign_user' | 'assign_team' | 'add_tag' | 'send_template'

export interface WhatsAppAutomationRuleData {
  id: string
  workspace_id: string
  name: string
  is_active: boolean
  trigger_type: WhatsAppAutomationTrigger
  trigger_conditions: Record<string, unknown> | null
  action_type: WhatsAppAutomationAction
  action_params: Record<string, unknown> | null
  priority: number
  execution_count: number
  created_at: string
  updated_at: string
}

export interface CreateAutomationRuleRequest {
  name: string
  trigger_type: WhatsAppAutomationTrigger
  trigger_conditions?: Record<string, unknown>
  action_type: WhatsAppAutomationAction
  action_params?: Record<string, unknown>
  priority?: number
}

export interface WhatsAppQuickReplyData {
  id: string
  workspace_id: string
  title: string
  content: string
  shortcut: string | null
  category: string | null
  usage_count: number
  created_at: string
  updated_at: string
}

export interface CreateQuickReplyRequest {
  title: string
  content: string
  shortcut?: string
  category?: string
}

export interface WhatsAppDailyMetricData {
  date: string
  conversations_started: number
  conversations_resolved: number
  messages_sent: number
  messages_delivered: number
  messages_read: number
  messages_failed: number
  templates_sent: number
  campaigns_sent: number
  avg_first_response_seconds: number | null
  avg_resolution_seconds: number | null
  block_count: number
}

export interface InboxHealthData {
  conversations_open: number
  conversations_pending: number
  avg_response_time: number | null
  unassigned: number
}

export interface MarketingPerformanceData {
  total_sent: number
  total_delivered: number
  total_read: number
  total_failed: number
  delivery_rate: number
  read_rate: number
}

export interface ComplianceHealthData {
  block_count: number
}

export interface AgentProductivityData {
  user_id: string
  conversations_handled: number
}
