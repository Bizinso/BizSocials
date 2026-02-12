import { get, post, put, del, getPaginated, upload } from './client'
import type {
  WhatsAppBusinessAccountData,
  WhatsAppPhoneNumberData,
  WhatsAppConversationData,
  WhatsAppMessageData,
  WhatsAppOptInData,
  OnboardWhatsAppRequest,
  UpdateBusinessProfileRequest,
  SendWhatsAppMessageRequest,
  AssignConversationRequest,
  CreateOptInRequest,
} from '@/types/whatsapp'
import type { PaginationParams } from '@/types/api'

export const whatsappApi = {
  // ─── Account Management ─────────────────────────────
  onboard(data: OnboardWhatsAppRequest) {
    return post<WhatsAppBusinessAccountData>('/whatsapp/onboard', data)
  },

  getAccounts() {
    return get<WhatsAppBusinessAccountData[]>('/whatsapp/accounts')
  },

  getAccount(accountId: string) {
    return get<WhatsAppBusinessAccountData>(`/whatsapp/accounts/${accountId}`)
  },

  updateProfile(accountId: string, data: UpdateBusinessProfileRequest) {
    return put<void>(`/whatsapp/accounts/${accountId}/profile`, data)
  },

  acceptCompliance(accountId: string) {
    return post<void>(`/whatsapp/accounts/${accountId}/accept-compliance`)
  },

  getPhoneNumbers(accountId: string) {
    return get<WhatsAppPhoneNumberData[]>(`/whatsapp/accounts/${accountId}/phone-numbers`)
  },

  // ─── Conversations ──────────────────────────────────
  getConversations(
    workspaceId: string,
    params?: PaginationParams & { status?: string; assigned_to_user_id?: string; unassigned?: boolean; priority?: string; search?: string },
  ) {
    return getPaginated<WhatsAppConversationData>(`/workspaces/${workspaceId}/conversations`, params as Record<string, unknown>)
  },

  getConversation(workspaceId: string, conversationId: string) {
    return get<WhatsAppConversationData>(`/workspaces/${workspaceId}/conversations/${conversationId}`)
  },

  assignConversation(workspaceId: string, conversationId: string, data: AssignConversationRequest) {
    return post<void>(`/workspaces/${workspaceId}/conversations/${conversationId}/assign`, data)
  },

  resolveConversation(workspaceId: string, conversationId: string) {
    return post<void>(`/workspaces/${workspaceId}/conversations/${conversationId}/resolve`)
  },

  reopenConversation(workspaceId: string, conversationId: string) {
    return post<void>(`/workspaces/${workspaceId}/conversations/${conversationId}/reopen`)
  },

  // ─── Messages ───────────────────────────────────────
  getMessages(workspaceId: string, conversationId: string, params?: PaginationParams) {
    return getPaginated<WhatsAppMessageData>(
      `/workspaces/${workspaceId}/conversations/${conversationId}/messages`,
      params as Record<string, unknown>,
    )
  },

  sendMessage(workspaceId: string, conversationId: string, data: SendWhatsAppMessageRequest) {
    return post<WhatsAppMessageData>(`/workspaces/${workspaceId}/conversations/${conversationId}/messages`, data)
  },

  sendMedia(workspaceId: string, conversationId: string, data: SendWhatsAppMessageRequest) {
    return post<WhatsAppMessageData>(`/workspaces/${workspaceId}/conversations/${conversationId}/messages/media`, data)
  },

  // ─── Contacts / Opt-Ins ─────────────────────────────
  getContacts(workspaceId: string, params?: PaginationParams & { active_only?: boolean; search?: string }) {
    return getPaginated<WhatsAppOptInData>(`/workspaces/${workspaceId}/whatsapp-contacts`, params as Record<string, unknown>)
  },

  createContact(workspaceId: string, data: CreateOptInRequest) {
    return post<WhatsAppOptInData>(`/workspaces/${workspaceId}/whatsapp-contacts`, data)
  },

  getContact(workspaceId: string, contactId: string) {
    return get<WhatsAppOptInData>(`/workspaces/${workspaceId}/whatsapp-contacts/${contactId}`)
  },

  updateContact(workspaceId: string, contactId: string, data: CreateOptInRequest) {
    return put<WhatsAppOptInData>(`/workspaces/${workspaceId}/whatsapp-contacts/${contactId}`, data)
  },

  deleteContact(workspaceId: string, contactId: string) {
    return del(`/workspaces/${workspaceId}/whatsapp-contacts/${contactId}`)
  },

  importContacts(workspaceId: string, file: File) {
    const formData = new FormData()
    formData.append('file', file)
    return upload<{ imported: number; skipped: number }>(`/workspaces/${workspaceId}/whatsapp-contacts/import`, formData)
  },
}
