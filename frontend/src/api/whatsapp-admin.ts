import { apiClient } from './client'
import type {
  WhatsAppAdminAccountData,
  WhatsAppAdminAccountDetailData,
  AccountRiskAlertData,
  ConsentLogsData,
} from '@/types/whatsapp-governance'
import type { PaginatedResponse } from '@/types/api'

const A = '/admin/whatsapp'

export const whatsappAdminApi = {
  async listAccounts(params?: Record<string, unknown>): Promise<PaginatedResponse<WhatsAppAdminAccountData>> {
    const { data } = await apiClient.get(`${A}/accounts`, { params })
    return data
  },

  async getAccountDetail(accountId: string): Promise<WhatsAppAdminAccountDetailData> {
    const { data } = await apiClient.get(`${A}/accounts/${accountId}`)
    return data.data
  },

  async suspendAccount(accountId: string, reason: string): Promise<void> {
    await apiClient.post(`${A}/accounts/${accountId}/suspend`, { reason })
  },

  async reactivateAccount(accountId: string): Promise<void> {
    await apiClient.post(`${A}/accounts/${accountId}/reactivate`)
  },

  async disableMarketing(accountId: string): Promise<void> {
    await apiClient.post(`${A}/accounts/${accountId}/disable-marketing`)
  },

  async enableMarketing(accountId: string): Promise<void> {
    await apiClient.post(`${A}/accounts/${accountId}/enable-marketing`)
  },

  async overrideRateLimit(phoneId: string, dailySendLimit: number): Promise<void> {
    await apiClient.post(`${A}/phone-numbers/${phoneId}/override-rate-limit`, { daily_send_limit: dailySendLimit })
  },

  async getConsentLogs(accountId: string): Promise<ConsentLogsData> {
    const { data } = await apiClient.get(`${A}/accounts/${accountId}/consent-logs`)
    return data.data
  },

  async listAlerts(params?: Record<string, unknown>): Promise<PaginatedResponse<AccountRiskAlertData>> {
    const { data } = await apiClient.get(`${A}/alerts`, { params })
    return data
  },

  async acknowledgeAlert(alertId: string): Promise<void> {
    await apiClient.post(`${A}/alerts/${alertId}/acknowledge`)
  },
}
