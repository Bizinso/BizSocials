import { apiClient } from './client'
import type {
  SupportTicketData,
  SupportTicketSummaryData,
  SupportCommentData,
  SupportCategoryData,
  CreateTicketRequest,
  AddTicketCommentRequest,
} from '@/types/support'
import type { PaginatedResponse } from '@/types/api'

export const supportApi = {
  // ─── Categories ───────────────────────────────────
  async listCategories(): Promise<SupportCategoryData[]> {
    const { data } = await apiClient.get('/support/categories')
    return data
  },

  // ─── Tickets ──────────────────────────────────────
  async listTickets(params?: Record<string, unknown>): Promise<PaginatedResponse<SupportTicketSummaryData>> {
    const { data } = await apiClient.get('/support/tickets', { params })
    return data
  },

  async createTicket(payload: CreateTicketRequest): Promise<SupportTicketData> {
    const { data } = await apiClient.post('/support/tickets', payload)
    return data
  },

  async getTicket(ticketId: string): Promise<SupportTicketData> {
    const { data } = await apiClient.get(`/support/tickets/${ticketId}`)
    return data
  },

  async updateTicket(ticketId: string, payload: Partial<CreateTicketRequest>): Promise<SupportTicketData> {
    const { data } = await apiClient.put(`/support/tickets/${ticketId}`, payload)
    return data
  },

  async closeTicket(ticketId: string): Promise<void> {
    await apiClient.post(`/support/tickets/${ticketId}/close`)
  },

  async reopenTicket(ticketId: string): Promise<void> {
    await apiClient.post(`/support/tickets/${ticketId}/reopen`)
  },

  // ─── Comments ─────────────────────────────────────
  async listComments(ticketId: string): Promise<SupportCommentData[]> {
    const { data } = await apiClient.get(`/support/tickets/${ticketId}/comments`)
    return data
  },

  async addComment(ticketId: string, payload: AddTicketCommentRequest): Promise<SupportCommentData> {
    const { data } = await apiClient.post(`/support/tickets/${ticketId}/comments`, payload)
    return data
  },
}
