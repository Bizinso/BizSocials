export interface SupportTicketData {
  id: string
  ticket_number: string
  subject: string
  description: string
  status: string
  priority: string
  type: string
  channel: string
  category_id: string | null
  category_name: string | null
  user_id: string
  user_name: string
  user_email: string
  tenant_id: string | null
  tenant_name: string | null
  assigned_to_id: string | null
  assigned_to_name: string | null
  comment_count: number
  first_response_at: string | null
  resolved_at: string | null
  closed_at: string | null
  created_at: string
  updated_at: string
}

export interface SupportTicketSummaryData {
  id: string
  ticket_number: string
  subject: string
  status: string
  priority: string
  category_name: string | null
  assigned_to_name: string | null
  comment_count: number
  created_at: string
  updated_at: string
}

export interface SupportCommentData {
  id: string
  ticket_id: string
  comment_type: string
  content: string
  is_internal: boolean
  author_type: string
  author_id: string | null
  author_name: string
  author_email: string | null
  created_at: string
}

export interface SupportCategoryData {
  id: string
  name: string
  slug: string | null
  description: string | null
  color: string
  icon: string | null
  is_active: boolean
  sort_order: number
  ticket_count: number
}

export interface SupportStatsData {
  total_tickets: number
  open_tickets: number
  pending_tickets: number
  resolved_tickets: number
  closed_tickets: number
  unassigned_tickets: number
  by_priority: Record<string, number>
  by_type: Record<string, number>
}

export interface CreateTicketRequest {
  subject: string
  description: string
  type?: string
  priority?: string
  category_id?: string
}

export interface AddTicketCommentRequest {
  content: string
}
