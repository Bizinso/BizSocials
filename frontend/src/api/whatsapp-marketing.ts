import { get, post, put, del, getPaginated } from './client'
import type {
  WhatsAppTemplateData,
  CreateTemplateRequest,
  WhatsAppCampaignData,
  CreateCampaignRequest,
  CampaignStatsData,
  CampaignValidationData,
} from '@/types/whatsapp-marketing'
import type { PaginationParams } from '@/types/api'

export const whatsappTemplateApi = {
  list(workspaceId: string, params?: PaginationParams & { status?: string; category?: string; search?: string }) {
    return getPaginated<WhatsAppTemplateData>(`/workspaces/${workspaceId}/whatsapp-templates`, params as Record<string, unknown>)
  },

  get(workspaceId: string, templateId: string) {
    return get<WhatsAppTemplateData>(`/workspaces/${workspaceId}/whatsapp-templates/${templateId}`)
  },

  create(workspaceId: string, data: CreateTemplateRequest) {
    return post<WhatsAppTemplateData>(`/workspaces/${workspaceId}/whatsapp-templates`, data)
  },

  update(workspaceId: string, templateId: string, data: CreateTemplateRequest) {
    return put<WhatsAppTemplateData>(`/workspaces/${workspaceId}/whatsapp-templates/${templateId}`, data)
  },

  delete(workspaceId: string, templateId: string) {
    return del(`/workspaces/${workspaceId}/whatsapp-templates/${templateId}`)
  },

  submit(workspaceId: string, templateId: string) {
    return post<WhatsAppTemplateData>(`/workspaces/${workspaceId}/whatsapp-templates/${templateId}/submit`)
  },

  sync(workspaceId: string, templateId: string) {
    return post<WhatsAppTemplateData>(`/workspaces/${workspaceId}/whatsapp-templates/${templateId}/sync`)
  },
}

export const whatsappCampaignApi = {
  list(workspaceId: string, params?: PaginationParams & { status?: string; search?: string }) {
    return getPaginated<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns`, params as Record<string, unknown>)
  },

  get(workspaceId: string, campaignId: string) {
    return get<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}`)
  },

  create(workspaceId: string, data: CreateCampaignRequest) {
    return post<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns`, data)
  },

  update(workspaceId: string, campaignId: string, data: CreateCampaignRequest) {
    return put<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}`, data)
  },

  delete(workspaceId: string, campaignId: string) {
    return del(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}`)
  },

  buildAudience(workspaceId: string, campaignId: string) {
    return post<{ recipients_count: number }>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}/build-audience`)
  },

  schedule(workspaceId: string, campaignId: string, scheduledAt: string) {
    return post<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}/schedule`, { scheduled_at: scheduledAt })
  },

  send(workspaceId: string, campaignId: string) {
    return post<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}/send`)
  },

  cancel(workspaceId: string, campaignId: string) {
    return post<WhatsAppCampaignData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}/cancel`)
  },

  stats(workspaceId: string, campaignId: string) {
    return get<CampaignStatsData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}/stats`)
  },

  validate(workspaceId: string, campaignId: string) {
    return get<CampaignValidationData>(`/workspaces/${workspaceId}/whatsapp-campaigns/${campaignId}/validate`)
  },
}
