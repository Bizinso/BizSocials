import type { AuditAction, SecurityEventType, SecuritySeverity } from './enums'

export interface AuditLogData {
  id: string
  action: AuditAction
  auditable_type: string
  auditable_id: string | null
  user_id: string | null
  user_name: string | null
  admin_id: string | null
  admin_name: string | null
  description: string | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  metadata: Record<string, unknown> | null
  ip_address: string | null
  user_agent: string | null
  request_id: string | null
  created_at: string
}

export interface SecurityEventData {
  id: string
  event_type: SecurityEventType
  severity: SecuritySeverity
  user_id: string | null
  user_name: string | null
  ip_address: string | null
  user_agent: string | null
  country_code: string | null
  city: string | null
  description: string | null
  metadata: Record<string, unknown> | null
  is_resolved: boolean
  resolved_by: string | null
  resolved_at: string | null
  resolution_notes: string | null
  created_at: string
}

export interface SecurityStatsData {
  total_events: number
  critical_events: number
  high_events: number
  medium_events: number
  failed_logins_24h: number
  suspicious_activities: number
  unresolved_events: number
  events_by_type: Record<string, number>
  events_by_severity: Record<string, number>
}
