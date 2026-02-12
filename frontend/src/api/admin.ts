import { apiClient } from './client'
import type {
  AdminTenantData,
  UpdateTenantAdminRequest,
  AdminUserData,
  UpdateUserAdminRequest,
  AdminPlanData,
  CreatePlanRequest,
  UpdatePlanRequest,
  FeatureFlagData,
  CreateFeatureFlagRequest,
  UpdateFeatureFlagRequest,
  PlatformConfigData,
  PlatformStatsData,
  SuspendRequest,
  IntegrationListItem,
  IntegrationDetail,
  UpdateIntegrationRequest,
  IntegrationHealthSummary,
  IntegrationHealthAccount,
  ForceReauthResult,
} from '@/types/admin'
import type {
  KBArticleData,
  KBCategoryData,
  KBFeedbackData,
  CreateKBArticleRequest,
  UpdateKBArticleRequest,
  CreateKBCategoryRequest,
  UpdateKBCategoryRequest,
} from '@/types/kb'
import type { FeedbackData, FeedbackStatsData, RoadmapItemData, CreateRoadmapItemRequest, UpdateRoadmapItemRequest, ReleaseNoteData } from '@/types/feedback'
import type { SupportTicketData, SupportCommentData, SupportCategoryData, SupportStatsData, AddTicketCommentRequest } from '@/types/support'
import type { PaginatedResponse } from '@/types/api'

const A = '/admin'

// ─── Dashboard ────────────────────────────────────────
export const adminDashboardApi = {
  async getStats(): Promise<PlatformStatsData> {
    const { data } = await apiClient.get(`${A}/dashboard/stats`)
    return data
  },

  async getRevenue(params?: Record<string, unknown>): Promise<Record<string, unknown>> {
    const { data } = await apiClient.get(`${A}/dashboard/revenue`, { params })
    return data
  },

  async getGrowth(params?: Record<string, unknown>): Promise<Record<string, unknown>> {
    const { data } = await apiClient.get(`${A}/dashboard/growth`, { params })
    return data
  },

  async getActivity(params?: Record<string, unknown>): Promise<Record<string, unknown>> {
    const { data } = await apiClient.get(`${A}/dashboard/activity`, { params })
    return data
  },
}

// ─── Tenants ──────────────────────────────────────────
export const adminTenantsApi = {
  async list(params?: Record<string, unknown>): Promise<PaginatedResponse<AdminTenantData>> {
    const { data } = await apiClient.get(`${A}/tenants`, { params })
    return data
  },

  async get(tenantId: string): Promise<AdminTenantData> {
    const { data } = await apiClient.get(`${A}/tenants/${tenantId}`)
    return data
  },

  async update(tenantId: string, payload: UpdateTenantAdminRequest): Promise<AdminTenantData> {
    const { data } = await apiClient.put(`${A}/tenants/${tenantId}`, payload)
    return data
  },

  async suspend(tenantId: string, payload: SuspendRequest): Promise<void> {
    await apiClient.post(`${A}/tenants/${tenantId}/suspend`, payload)
  },

  async activate(tenantId: string): Promise<void> {
    await apiClient.post(`${A}/tenants/${tenantId}/activate`)
  },

  async impersonate(tenantId: string): Promise<{ token: string }> {
    const { data } = await apiClient.post(`${A}/tenants/${tenantId}/impersonate`)
    return data
  },
}

// ─── Users ────────────────────────────────────────────
export const adminUsersApi = {
  async list(params?: Record<string, unknown>): Promise<PaginatedResponse<AdminUserData>> {
    const { data } = await apiClient.get(`${A}/users`, { params })
    return data
  },

  async get(userId: string): Promise<AdminUserData> {
    const { data } = await apiClient.get(`${A}/users/${userId}`)
    return data
  },

  async update(userId: string, payload: UpdateUserAdminRequest): Promise<AdminUserData> {
    const { data } = await apiClient.put(`${A}/users/${userId}`, payload)
    return data
  },

  async suspend(userId: string, payload: SuspendRequest): Promise<void> {
    await apiClient.post(`${A}/users/${userId}/suspend`, payload)
  },

  async activate(userId: string): Promise<void> {
    await apiClient.post(`${A}/users/${userId}/activate`)
  },

  async resetPassword(userId: string): Promise<void> {
    await apiClient.post(`${A}/users/${userId}/reset-password`)
  },
}

// ─── Plans ────────────────────────────────────────────
export const adminPlansApi = {
  async list(): Promise<AdminPlanData[]> {
    const { data } = await apiClient.get(`${A}/plans`)
    return data
  },

  async get(planId: string): Promise<AdminPlanData> {
    const { data } = await apiClient.get(`${A}/plans/${planId}`)
    return data
  },

  async create(payload: CreatePlanRequest): Promise<AdminPlanData> {
    const { data } = await apiClient.post(`${A}/plans`, payload)
    return data
  },

  async update(planId: string, payload: UpdatePlanRequest): Promise<AdminPlanData> {
    const { data } = await apiClient.put(`${A}/plans/${planId}`, payload)
    return data
  },

  async remove(planId: string): Promise<void> {
    await apiClient.delete(`${A}/plans/${planId}`)
  },

  async updateLimits(planId: string, limits: Record<string, number>): Promise<void> {
    await apiClient.put(`${A}/plans/${planId}/limits`, { limits })
  },
}

// ─── Feature Flags ────────────────────────────────────
export const adminFeatureFlagsApi = {
  async list(): Promise<FeatureFlagData[]> {
    const { data } = await apiClient.get(`${A}/feature-flags`)
    return data
  },

  async get(flagId: string): Promise<FeatureFlagData> {
    const { data } = await apiClient.get(`${A}/feature-flags/${flagId}`)
    return data
  },

  async create(payload: CreateFeatureFlagRequest): Promise<FeatureFlagData> {
    const { data } = await apiClient.post(`${A}/feature-flags`, payload)
    return data
  },

  async update(flagId: string, payload: UpdateFeatureFlagRequest): Promise<FeatureFlagData> {
    const { data } = await apiClient.put(`${A}/feature-flags/${flagId}`, payload)
    return data
  },

  async remove(flagId: string): Promise<void> {
    await apiClient.delete(`${A}/feature-flags/${flagId}`)
  },

  async toggle(flagId: string): Promise<FeatureFlagData> {
    const { data } = await apiClient.post(`${A}/feature-flags/${flagId}/toggle`)
    return data
  },

  async check(key: string): Promise<{ enabled: boolean }> {
    const { data } = await apiClient.get(`${A}/feature-flags/check/${key}`)
    return data
  },
}

// ─── Platform Config ──────────────────────────────────
export const adminConfigApi = {
  async list(): Promise<PlatformConfigData[]> {
    const { data } = await apiClient.get(`${A}/config`)
    return data
  },

  async getGrouped(): Promise<Record<string, PlatformConfigData[]>> {
    const { data } = await apiClient.get(`${A}/config/grouped`)
    return data
  },

  async getCategories(): Promise<string[]> {
    const { data } = await apiClient.get(`${A}/config/categories`)
    return data
  },

  async get(key: string): Promise<PlatformConfigData> {
    const { data } = await apiClient.get(`${A}/config/${key}`)
    return data
  },

  async update(key: string, value: unknown): Promise<PlatformConfigData> {
    const { data } = await apiClient.put(`${A}/config/${key}`, { value })
    return data
  },

  async remove(key: string): Promise<void> {
    await apiClient.delete(`${A}/config/${key}`)
  },

  async bulkSet(configs: Record<string, unknown>): Promise<void> {
    await apiClient.post(`${A}/config/bulk`, { configs })
  },
}

// ─── Admin KB ─────────────────────────────────────────
export const adminKBApi = {
  async listArticles(params?: Record<string, unknown>): Promise<PaginatedResponse<KBArticleData>> {
    const { data } = await apiClient.get(`${A}/kb/articles`, { params })
    return data
  },

  async getArticle(articleId: string): Promise<KBArticleData> {
    const { data } = await apiClient.get(`${A}/kb/articles/${articleId}`)
    return data
  },

  async createArticle(payload: CreateKBArticleRequest): Promise<KBArticleData> {
    const { data } = await apiClient.post(`${A}/kb/articles`, payload)
    return data
  },

  async updateArticle(articleId: string, payload: UpdateKBArticleRequest): Promise<KBArticleData> {
    const { data } = await apiClient.put(`${A}/kb/articles/${articleId}`, payload)
    return data
  },

  async deleteArticle(articleId: string): Promise<void> {
    await apiClient.delete(`${A}/kb/articles/${articleId}`)
  },

  async publishArticle(articleId: string): Promise<void> {
    await apiClient.post(`${A}/kb/articles/${articleId}/publish`)
  },

  async unpublishArticle(articleId: string): Promise<void> {
    await apiClient.post(`${A}/kb/articles/${articleId}/unpublish`)
  },

  async archiveArticle(articleId: string): Promise<void> {
    await apiClient.post(`${A}/kb/articles/${articleId}/archive`)
  },

  async listCategories(): Promise<KBCategoryData[]> {
    const { data } = await apiClient.get(`${A}/kb/categories`)
    return data
  },

  async createCategory(payload: CreateKBCategoryRequest): Promise<KBCategoryData> {
    const { data } = await apiClient.post(`${A}/kb/categories`, payload)
    return data
  },

  async updateCategory(categoryId: string, payload: UpdateKBCategoryRequest): Promise<KBCategoryData> {
    const { data } = await apiClient.put(`${A}/kb/categories/${categoryId}`, payload)
    return data
  },

  async deleteCategory(categoryId: string): Promise<void> {
    await apiClient.delete(`${A}/kb/categories/${categoryId}`)
  },

  async updateCategoryOrder(order: { id: string; sort_order: number }[]): Promise<void> {
    await apiClient.put(`${A}/kb/categories/order`, { order })
  },

  async listFeedback(params?: Record<string, unknown>): Promise<PaginatedResponse<KBFeedbackData>> {
    const { data } = await apiClient.get(`${A}/kb/feedback`, { params })
    return data
  },

  async getPendingFeedback(): Promise<KBFeedbackData[]> {
    const { data } = await apiClient.get(`${A}/kb/feedback/pending`)
    return data
  },

  async resolveFeedback(feedbackId: string, notes?: string): Promise<void> {
    await apiClient.post(`${A}/kb/feedback/${feedbackId}/resolve`, { admin_notes: notes })
  },

  async actionFeedback(feedbackId: string, notes?: string): Promise<void> {
    await apiClient.post(`${A}/kb/feedback/${feedbackId}/action`, { admin_notes: notes })
  },

  async dismissFeedback(feedbackId: string, notes?: string): Promise<void> {
    await apiClient.post(`${A}/kb/feedback/${feedbackId}/dismiss`, { admin_notes: notes })
  },
}

// ─── Admin Feedback ───────────────────────────────────
export const adminFeedbackApi = {
  async list(params?: Record<string, unknown>): Promise<PaginatedResponse<FeedbackData>> {
    const { data } = await apiClient.get(`${A}/feedback`, { params })
    return data
  },

  async getStats(): Promise<FeedbackStatsData> {
    const { data } = await apiClient.get(`${A}/feedback/stats`)
    return data
  },

  async get(feedbackId: string): Promise<FeedbackData> {
    const { data } = await apiClient.get(`${A}/feedback/${feedbackId}`)
    return data
  },

  async updateStatus(feedbackId: string, status: string, reason?: string): Promise<void> {
    await apiClient.put(`${A}/feedback/${feedbackId}/status`, { status, status_reason: reason })
  },

  async linkRoadmap(feedbackId: string, roadmapItemId: string): Promise<void> {
    await apiClient.post(`${A}/feedback/${feedbackId}/link-roadmap`, { roadmap_item_id: roadmapItemId })
  },
}

// ─── Admin Roadmap ────────────────────────────────────
export const adminRoadmapApi = {
  async list(params?: Record<string, unknown>): Promise<RoadmapItemData[]> {
    const { data } = await apiClient.get(`${A}/roadmap`, { params })
    return data
  },

  async get(itemId: string): Promise<RoadmapItemData> {
    const { data } = await apiClient.get(`${A}/roadmap/${itemId}`)
    return data
  },

  async create(payload: CreateRoadmapItemRequest): Promise<RoadmapItemData> {
    const { data } = await apiClient.post(`${A}/roadmap`, payload)
    return data
  },

  async update(itemId: string, payload: UpdateRoadmapItemRequest): Promise<RoadmapItemData> {
    const { data } = await apiClient.put(`${A}/roadmap/${itemId}`, payload)
    return data
  },

  async updateStatus(itemId: string, status: string): Promise<void> {
    await apiClient.put(`${A}/roadmap/${itemId}/status`, { status })
  },

  async remove(itemId: string): Promise<void> {
    await apiClient.delete(`${A}/roadmap/${itemId}`)
  },
}

// ─── Admin Release Notes ──────────────────────────────
export const adminReleaseNotesApi = {
  async list(params?: Record<string, unknown>): Promise<PaginatedResponse<ReleaseNoteData>> {
    const { data } = await apiClient.get(`${A}/release-notes`, { params })
    return data
  },

  async get(noteId: string): Promise<ReleaseNoteData> {
    const { data } = await apiClient.get(`${A}/release-notes/${noteId}`)
    return data
  },

  async create(payload: Record<string, unknown>): Promise<ReleaseNoteData> {
    const { data } = await apiClient.post(`${A}/release-notes`, payload)
    return data
  },

  async update(noteId: string, payload: Record<string, unknown>): Promise<ReleaseNoteData> {
    const { data } = await apiClient.put(`${A}/release-notes/${noteId}`, payload)
    return data
  },

  async publish(noteId: string): Promise<void> {
    await apiClient.post(`${A}/release-notes/${noteId}/publish`)
  },

  async unpublish(noteId: string): Promise<void> {
    await apiClient.post(`${A}/release-notes/${noteId}/unpublish`)
  },

  async remove(noteId: string): Promise<void> {
    await apiClient.delete(`${A}/release-notes/${noteId}`)
  },
}

// ─── Admin Support ────────────────────────────────────
export const adminSupportApi = {
  async getStats(): Promise<SupportStatsData> {
    const { data } = await apiClient.get(`${A}/support/stats`)
    return data
  },

  async listTickets(params?: Record<string, unknown>): Promise<PaginatedResponse<SupportTicketData>> {
    const { data } = await apiClient.get(`${A}/support/tickets`, { params })
    return data
  },

  async getTicket(ticketId: string): Promise<SupportTicketData> {
    const { data } = await apiClient.get(`${A}/support/tickets/${ticketId}`)
    return data
  },

  async assignTicket(ticketId: string, adminId: string): Promise<void> {
    await apiClient.post(`${A}/support/tickets/${ticketId}/assign`, { admin_id: adminId })
  },

  async unassignTicket(ticketId: string): Promise<void> {
    await apiClient.post(`${A}/support/tickets/${ticketId}/unassign`)
  },

  async updateStatus(ticketId: string, status: string): Promise<void> {
    await apiClient.put(`${A}/support/tickets/${ticketId}/status`, { status })
  },

  async updatePriority(ticketId: string, priority: string): Promise<void> {
    await apiClient.put(`${A}/support/tickets/${ticketId}/priority`, { priority })
  },

  async listComments(ticketId: string): Promise<SupportCommentData[]> {
    const { data } = await apiClient.get(`${A}/support/tickets/${ticketId}/comments`)
    return data
  },

  async addComment(ticketId: string, payload: AddTicketCommentRequest): Promise<SupportCommentData> {
    const { data } = await apiClient.post(`${A}/support/tickets/${ticketId}/comments`, payload)
    return data
  },

  async addNote(ticketId: string, content: string): Promise<SupportCommentData> {
    const { data } = await apiClient.post(`${A}/support/tickets/${ticketId}/notes`, { content })
    return data
  },

  async listCategories(): Promise<SupportCategoryData[]> {
    const { data } = await apiClient.get(`${A}/support/categories`)
    return data
  },

  async createCategory(payload: Partial<SupportCategoryData>): Promise<SupportCategoryData> {
    const { data } = await apiClient.post(`${A}/support/categories`, payload)
    return data
  },

  async updateCategory(categoryId: string, payload: Partial<SupportCategoryData>): Promise<SupportCategoryData> {
    const { data } = await apiClient.put(`${A}/support/categories/${categoryId}`, payload)
    return data
  },

  async deleteCategory(categoryId: string): Promise<void> {
    await apiClient.delete(`${A}/support/categories/${categoryId}`)
  },
}

// ─── Admin Privacy ────────────────────────────────────
export const adminPrivacyApi = {
  async listExportRequests(params?: Record<string, unknown>): Promise<PaginatedResponse<Record<string, unknown>>> {
    const { data } = await apiClient.get(`${A}/privacy/export-requests`, { params })
    return data
  },

  async listDeletionRequests(params?: Record<string, unknown>): Promise<PaginatedResponse<Record<string, unknown>>> {
    const { data } = await apiClient.get(`${A}/privacy/deletion-requests`, { params })
    return data
  },

  async approveDeletion(requestId: string): Promise<void> {
    await apiClient.post(`${A}/privacy/deletion-requests/${requestId}/approve`)
  },

  async rejectDeletion(requestId: string, reason?: string): Promise<void> {
    await apiClient.post(`${A}/privacy/deletion-requests/${requestId}/reject`, { reason })
  },
}

// ─── Admin Integrations ──────────────────────────────
export const adminIntegrationsApi = {
  async list(): Promise<IntegrationListItem[]> {
    const { data } = await apiClient.get(`${A}/integrations`)
    return data
  },

  async get(provider: string): Promise<IntegrationDetail> {
    const { data } = await apiClient.get(`${A}/integrations/${provider}`)
    return data
  },

  async update(provider: string, payload: UpdateIntegrationRequest): Promise<IntegrationDetail & { meta: Record<string, unknown> }> {
    const { data } = await apiClient.put(`${A}/integrations/${provider}`, payload)
    return data
  },

  async verify(provider: string): Promise<{ valid: boolean; app_name: string | null; error: string | null; verified_at: string }> {
    const { data } = await apiClient.post(`${A}/integrations/${provider}/verify`)
    return data
  },

  async toggle(provider: string, enabled: boolean, reason?: string): Promise<{ provider: string; is_enabled: boolean; status: string }> {
    const { data } = await apiClient.post(`${A}/integrations/${provider}/toggle`, { enabled, reason })
    return data
  },

  async forceReauth(provider: string, platforms: string[], reason: string, notifyTenants = true): Promise<ForceReauthResult> {
    const { data } = await apiClient.post(`${A}/integrations/${provider}/force-reauth`, {
      platforms,
      reason,
      notify_tenants: notifyTenants,
    })
    return data
  },

  async getHealth(provider: string, params?: Record<string, unknown>): Promise<{ summary: Record<string, IntegrationHealthSummary>; accounts: { data: IntegrationHealthAccount[] } }> {
    const { data } = await apiClient.get(`${A}/integrations/${provider}/health`, { params })
    return data
  },

  async getAuditLog(provider: string, params?: Record<string, unknown>): Promise<Record<string, unknown>> {
    const { data } = await apiClient.get(`${A}/integrations/${provider}/audit-log`, { params })
    return data
  },
}
