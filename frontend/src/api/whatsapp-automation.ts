import { get, post, put, del, getPaginated } from './client'
import type {
  WhatsAppAutomationRuleData,
  CreateAutomationRuleRequest,
  WhatsAppQuickReplyData,
  CreateQuickReplyRequest,
  InboxHealthData,
  MarketingPerformanceData,
  ComplianceHealthData,
  AgentProductivityData,
} from '@/types/whatsapp-automation'
import type { PaginationParams } from '@/types/api'

export const whatsappAutomationApi = {
  listRules(workspaceId: string, params?: PaginationParams) {
    return getPaginated<WhatsAppAutomationRuleData>(`/workspaces/${workspaceId}/whatsapp-automation-rules`, params as Record<string, unknown>)
  },

  createRule(workspaceId: string, data: CreateAutomationRuleRequest) {
    return post<WhatsAppAutomationRuleData>(`/workspaces/${workspaceId}/whatsapp-automation-rules`, data)
  },

  updateRule(workspaceId: string, ruleId: string, data: Partial<CreateAutomationRuleRequest> & { is_active?: boolean }) {
    return put<WhatsAppAutomationRuleData>(`/workspaces/${workspaceId}/whatsapp-automation-rules/${ruleId}`, data)
  },

  deleteRule(workspaceId: string, ruleId: string) {
    return del(`/workspaces/${workspaceId}/whatsapp-automation-rules/${ruleId}`)
  },
}

export const whatsappQuickReplyApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<WhatsAppQuickReplyData>(`/workspaces/${workspaceId}/whatsapp-quick-replies`, params as Record<string, unknown>)
  },

  create(workspaceId: string, data: CreateQuickReplyRequest) {
    return post<WhatsAppQuickReplyData>(`/workspaces/${workspaceId}/whatsapp-quick-replies`, data)
  },

  update(workspaceId: string, replyId: string, data: Partial<CreateQuickReplyRequest>) {
    return put<WhatsAppQuickReplyData>(`/workspaces/${workspaceId}/whatsapp-quick-replies/${replyId}`, data)
  },

  delete(workspaceId: string, replyId: string) {
    return del(`/workspaces/${workspaceId}/whatsapp-quick-replies/${replyId}`)
  },
}

export const whatsappAnalyticsApi = {
  inboxHealth(workspaceId: string) {
    return get<InboxHealthData>(`/workspaces/${workspaceId}/whatsapp-analytics/inbox-health`)
  },

  marketingPerformance(workspaceId: string, from?: string, to?: string) {
    const params: Record<string, string> = {}
    if (from) params.from = from
    if (to) params.to = to
    return get<MarketingPerformanceData>(`/workspaces/${workspaceId}/whatsapp-analytics/marketing-performance`, params)
  },

  complianceHealth(workspaceId: string) {
    return get<ComplianceHealthData>(`/workspaces/${workspaceId}/whatsapp-analytics/compliance-health`)
  },

  agentProductivity(workspaceId: string) {
    return get<AgentProductivityData[]>(`/workspaces/${workspaceId}/whatsapp-analytics/agent-productivity`)
  },
}
