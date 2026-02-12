import type { NotificationType, NotificationChannel } from './enums'

export interface NotificationData {
  id: string
  user_id: string
  type: NotificationType
  type_label: string
  category: string
  channel: NotificationChannel
  title: string
  message: string
  data: Record<string, unknown> | null
  action_url: string | null
  icon: string
  is_read: boolean
  is_urgent: boolean
  read_at: string | null
  sent_at: string | null
  created_at: string
}

export interface NotificationPreferenceData {
  id: string
  user_id: string
  notification_type: NotificationType
  notification_type_label: string
  category: string
  in_app_enabled: boolean
  email_enabled: boolean
  push_enabled: boolean
  sms_enabled: boolean
  created_at: string
  updated_at: string
}

export interface UpdatePreferencesRequest {
  preferences: {
    notification_type: NotificationType
    in_app_enabled: boolean
    email_enabled: boolean
    push_enabled?: boolean
    sms_enabled?: boolean
  }[]
}
