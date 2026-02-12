import { get, getPaginated } from './client'
import type { AuditLogData, SecurityEventData, SecurityStatsData } from '@/types/audit'
import type { PaginationParams } from '@/types/api'

export const auditApi = {
  // Audit Logs
  listLogs(params?: PaginationParams & { action?: string; auditable_type?: string }) {
    return getPaginated<AuditLogData>('/audit/logs', params as Record<string, unknown>)
  },

  logsForAuditable(auditableType: string, auditableId: string) {
    return get<AuditLogData[]>(`/audit/logs/${auditableType}/${auditableId}`)
  },

  // Security Events
  listSecurityEvents(params?: PaginationParams & { event_type?: string; severity?: string }) {
    return getPaginated<SecurityEventData>('/security/events', params as Record<string, unknown>)
  },

  securityStats() {
    return get<SecurityStatsData>('/security/stats')
  },
}
