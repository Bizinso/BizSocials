import type {
  WhatsAppAccountStatus,
  WhatsAppQualityRating,
  WhatsAppMessagingTier,
  WhatsAppMessageType,
  WhatsAppMessageDirection,
  WhatsAppMessageStatus,
  WhatsAppConversationStatus,
  WhatsAppConversationPriority,
  WhatsAppOptInSource,
} from './enums'

// ─── Business Account ───────────────────────────────
export interface WhatsAppBusinessAccountData {
  id: string
  tenant_id: string
  waba_id: string
  name: string
  status: WhatsAppAccountStatus
  quality_rating: WhatsAppQualityRating
  messaging_limit_tier: WhatsAppMessagingTier
  is_marketing_enabled: boolean
  compliance_accepted_at: string | null
  suspended_reason: string | null
  phone_numbers: WhatsAppPhoneNumberData[] | null
  created_at: string
  updated_at: string
}

// ─── Phone Number ───────────────────────────────────
export interface WhatsAppPhoneNumberData {
  id: string
  phone_number_id: string
  phone_number: string
  display_name: string
  verified_name: string | null
  quality_rating: string
  status: string
  is_primary: boolean
  daily_send_count: number
  daily_send_limit: number
  created_at: string
}

// ─── Conversation ───────────────────────────────────
export interface WhatsAppConversationData {
  id: string
  workspace_id: string
  customer_phone: string
  customer_name: string | null
  customer_profile_name: string | null
  status: WhatsAppConversationStatus
  priority: WhatsAppConversationPriority
  assigned_to_user_id: string | null
  assigned_to_name: string | null
  assigned_to_team: string | null
  last_message_at: string | null
  last_customer_message_at: string | null
  conversation_expires_at: string | null
  is_within_service_window: boolean
  message_count: number
  first_response_at: string | null
  phone_number: string | null
  phone_display_name: string | null
  created_at: string
  updated_at: string
}

// ─── Message ────────────────────────────────────────
export interface WhatsAppMessageData {
  id: string
  conversation_id: string
  wamid: string | null
  direction: WhatsAppMessageDirection
  type: WhatsAppMessageType
  content_text: string | null
  content_payload: Record<string, unknown> | null
  media_url: string | null
  media_mime_type: string | null
  sent_by_user_id: string | null
  sent_by_name: string | null
  status: WhatsAppMessageStatus
  error_code: string | null
  error_message: string | null
  platform_timestamp: string
  created_at: string
}

// ─── Opt-In Contact ─────────────────────────────────
export interface WhatsAppOptInData {
  id: string
  workspace_id: string
  phone_number: string
  customer_name: string | null
  source: WhatsAppOptInSource
  opted_in_at: string
  opted_out_at: string | null
  opt_in_proof: string | null
  is_active: boolean
  tags: string[] | null
  created_at: string
}

// ─── Requests ───────────────────────────────────────
export interface OnboardWhatsAppRequest {
  meta_access_token: string
}

export interface UpdateBusinessProfileRequest {
  description?: string
  address?: string
  website?: string
  support_email?: string
}

export interface SendWhatsAppMessageRequest {
  type: 'text' | 'image' | 'video' | 'document' | 'audio' | 'template'
  content?: string
  media_url?: string
  caption?: string
  template_name?: string
  template_language?: string
  template_components?: unknown[]
}

export interface AssignConversationRequest {
  user_id?: string | null
  team?: string | null
}

export interface CreateOptInRequest {
  phone_number: string
  customer_name?: string
  source?: string
  tags?: string[]
}
