// ─── Template ───────────────────────────────────────
export type WhatsAppTemplateCategory = 'marketing' | 'utility' | 'authentication'
export type WhatsAppTemplateStatus = 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'disabled' | 'paused'
export type WhatsAppHeaderType = 'none' | 'text' | 'image' | 'video' | 'document'
export type WhatsAppButtonType = 'QUICK_REPLY' | 'URL' | 'PHONE_NUMBER'

export interface WhatsAppTemplateButton {
  type: WhatsAppButtonType
  text: string
  url?: string
  phone_number?: string
}

export interface WhatsAppTemplateData {
  id: string
  workspace_id: string
  whatsapp_phone_number_id: string
  meta_template_id: string | null
  name: string
  language: string
  category: WhatsAppTemplateCategory
  status: WhatsAppTemplateStatus
  rejection_reason: string | null
  header_type: WhatsAppHeaderType
  header_content: string | null
  body_text: string
  footer_text: string | null
  buttons: WhatsAppTemplateButton[] | null
  sample_values: string[] | null
  usage_count: number
  last_used_at: string | null
  submitted_at: string | null
  approved_at: string | null
  phone_number: string | null
  phone_display_name: string | null
  created_at: string
  updated_at: string
}

export interface CreateTemplateRequest {
  whatsapp_phone_number_id: string
  name: string
  language?: string
  category: WhatsAppTemplateCategory
  header_type?: WhatsAppHeaderType
  header_content?: string
  body_text: string
  footer_text?: string
  buttons?: WhatsAppTemplateButton[]
  sample_values?: string[]
}

// ─── Campaign ──────────────────────────────────────
export type WhatsAppCampaignStatus = 'draft' | 'scheduled' | 'sending' | 'completed' | 'failed' | 'cancelled'

export interface WhatsAppCampaignAudienceFilter {
  tags?: string[]
  opt_in_after?: string
  exclude_tags?: string[]
}

export interface WhatsAppCampaignData {
  id: string
  workspace_id: string
  whatsapp_phone_number_id: string
  template_id: string
  name: string
  status: WhatsAppCampaignStatus
  scheduled_at: string | null
  started_at: string | null
  completed_at: string | null
  total_recipients: number
  sent_count: number
  delivered_count: number
  read_count: number
  failed_count: number
  delivery_rate: number
  read_rate: number
  template_params_mapping: Record<string, string> | null
  audience_filter: WhatsAppCampaignAudienceFilter | null
  template_name: string | null
  created_by_name: string | null
  created_at: string
  updated_at: string
}

export interface CreateCampaignRequest {
  whatsapp_phone_number_id: string
  template_id: string
  name: string
  template_params_mapping?: Record<string, string>
  audience_filter?: WhatsAppCampaignAudienceFilter
}

export interface CampaignStatsData {
  total_recipients: number
  sent: number
  delivered: number
  read: number
  failed: number
  delivery_rate: number
  read_rate: number
}

export interface CampaignValidationData {
  valid_count: number
  invalid_count: number
  reasons: string[]
}
