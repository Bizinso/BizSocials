import { get, post, put, del, getPaginated } from './client'
import type {
  InboxItemTagData,
  InboxInternalNoteData,
  SavedReplyData,
  InboxContactData,
  InboxAutomationRuleData,
  CreateInboxTagRequest,
  CreateNoteRequest,
  CreateSavedReplyRequest,
  CreateInboxContactRequest,
  CreateAutomationRuleRequest,
} from '@/types/inbox-extended'
import type { PaginationParams } from '@/types/api'

// Inbox Tags
export const inboxTagApi = {
  list(workspaceId: string, params?: PaginationParams & { search?: string }) {
    return getPaginated<InboxItemTagData>(`/workspaces/${workspaceId}/inbox-tags`, params as Record<string, unknown>)
  },

  create(workspaceId: string, data: CreateInboxTagRequest) {
    return post<InboxItemTagData>(`/workspaces/${workspaceId}/inbox-tags`, data)
  },

  update(workspaceId: string, tagId: string, data: Partial<CreateInboxTagRequest>) {
    return put<InboxItemTagData>(`/workspaces/${workspaceId}/inbox-tags/${tagId}`, data)
  },

  delete(workspaceId: string, tagId: string) {
    return del(`/workspaces/${workspaceId}/inbox-tags/${tagId}`)
  },

  attach(workspaceId: string, itemId: string, tagId: string) {
    return post<void>(`/workspaces/${workspaceId}/inbox/${itemId}/tags/${tagId}`)
  },

  detach(workspaceId: string, itemId: string, tagId: string) {
    return del(`/workspaces/${workspaceId}/inbox/${itemId}/tags/${tagId}`)
  },
}

// Inbox Internal Notes
export const inboxNoteApi = {
  list(workspaceId: string, itemId: string) {
    return get<InboxInternalNoteData[]>(`/workspaces/${workspaceId}/inbox/${itemId}/notes`)
  },

  create(workspaceId: string, itemId: string, data: CreateNoteRequest) {
    return post<InboxInternalNoteData>(`/workspaces/${workspaceId}/inbox/${itemId}/notes`, data)
  },

  delete(workspaceId: string, noteId: string) {
    return del(`/workspaces/${workspaceId}/inbox/notes/${noteId}`)
  },
}

// Saved Replies
export const savedReplyApi = {
  list(workspaceId: string, params?: PaginationParams & { category?: string; search?: string }) {
    return getPaginated<SavedReplyData>(`/workspaces/${workspaceId}/saved-replies`, params as Record<string, unknown>)
  },

  get(workspaceId: string, replyId: string) {
    return get<SavedReplyData>(`/workspaces/${workspaceId}/saved-replies/${replyId}`)
  },

  create(workspaceId: string, data: CreateSavedReplyRequest) {
    return post<SavedReplyData>(`/workspaces/${workspaceId}/saved-replies`, data)
  },

  update(workspaceId: string, replyId: string, data: Partial<CreateSavedReplyRequest>) {
    return put<SavedReplyData>(`/workspaces/${workspaceId}/saved-replies/${replyId}`, data)
  },

  delete(workspaceId: string, replyId: string) {
    return del(`/workspaces/${workspaceId}/saved-replies/${replyId}`)
  },
}

// Inbox Contacts (Social CRM)
export const inboxContactApi = {
  list(workspaceId: string, params?: PaginationParams & { platform?: string; search?: string; sort_by?: string; sort_dir?: string }) {
    return getPaginated<InboxContactData>(`/workspaces/${workspaceId}/inbox-contacts`, params as Record<string, unknown>)
  },

  get(workspaceId: string, contactId: string) {
    return get<InboxContactData>(`/workspaces/${workspaceId}/inbox-contacts/${contactId}`)
  },

  create(workspaceId: string, data: CreateInboxContactRequest) {
    return post<InboxContactData>(`/workspaces/${workspaceId}/inbox-contacts`, data)
  },

  update(workspaceId: string, contactId: string, data: Partial<CreateInboxContactRequest>) {
    return put<InboxContactData>(`/workspaces/${workspaceId}/inbox-contacts/${contactId}`, data)
  },

  delete(workspaceId: string, contactId: string) {
    return del(`/workspaces/${workspaceId}/inbox-contacts/${contactId}`)
  },
}

// Inbox Automation Rules
export const inboxAutomationApi = {
  list(workspaceId: string, params?: PaginationParams & { is_active?: boolean }) {
    return getPaginated<InboxAutomationRuleData>(`/workspaces/${workspaceId}/inbox-automation-rules`, params as Record<string, unknown>)
  },

  get(workspaceId: string, ruleId: string) {
    return get<InboxAutomationRuleData>(`/workspaces/${workspaceId}/inbox-automation-rules/${ruleId}`)
  },

  create(workspaceId: string, data: CreateAutomationRuleRequest) {
    return post<InboxAutomationRuleData>(`/workspaces/${workspaceId}/inbox-automation-rules`, data)
  },

  update(workspaceId: string, ruleId: string, data: Partial<CreateAutomationRuleRequest> & { is_active?: boolean }) {
    return put<InboxAutomationRuleData>(`/workspaces/${workspaceId}/inbox-automation-rules/${ruleId}`, data)
  },

  delete(workspaceId: string, ruleId: string) {
    return del(`/workspaces/${workspaceId}/inbox-automation-rules/${ruleId}`)
  },
}
