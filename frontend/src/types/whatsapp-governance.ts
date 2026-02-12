export type AlertSeverity = 'info' | 'warning' | 'critical'
export type AlertType = 'quality_drop' | 'rate_limit_hit' | 'template_rejection_spike' | 'suspension_risk' | 'account_banned'

export interface AccountRiskAlertData {
  id: string
  whatsapp_business_account_id: string
  alert_type: AlertType
  severity: AlertSeverity
  title: string
  description: string
  recommended_action: string | null
  auto_action_taken: string | null
  acknowledged_at: string | null
  acknowledged_by_user_id: string | null
  resolved_at: string | null
  created_at: string
  updated_at: string
  business_account?: WhatsAppAdminAccountData
}

export interface WhatsAppAdminAccountData {
  id: string
  tenant_id: string
  waba_id: string
  name: string
  status: string
  quality_rating: string
  messaging_limit_tier: string
  is_marketing_enabled: boolean
  suspended_reason: string | null
  compliance_accepted_at: string | null
  created_at: string
  updated_at: string
  phone_numbers?: WhatsAppAdminPhoneData[]
  tenant?: { id: string; name: string }
  unresolved_alerts_count?: number
}

export interface WhatsAppAdminPhoneData {
  id: string
  phone_number: string
  display_name: string
  quality_rating: string
  status: string
  daily_send_count: number
  daily_send_limit: number
}

export interface WhatsAppAdminAccountDetailData {
  account: WhatsAppAdminAccountData
  alerts: AccountRiskAlertData[]
  stats: {
    total_conversations: number
    active_conversations: number
    total_templates: number
    approved_templates: number
  }
}

export interface ConsentLogsData {
  compliance_accepted_at: string | null
  compliance_accepted_by: string | null
  account_created_at: string
  marketing_enabled: boolean
  status: string
}
