import { get, post, getPaginated } from './client'
import type {
  InboxItemData,
  InboxReplyData,
  InboxStatsData,
  CreateInboxReplyRequest,
  AssignInboxItemRequest,
  BulkInboxRequest,
} from '@/types/inbox'
import type { PaginationParams } from '@/types/api'

export const inboxApi = {
  list(workspaceId: string, params?: PaginationParams & { status?: string; type?: string; platform?: string }) {
    return getPaginated<InboxItemData>(`/workspaces/${workspaceId}/inbox`, params as Record<string, unknown>)
  },

  get(workspaceId: string, itemId: string) {
    return get<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}`)
  },

  stats(workspaceId: string) {
    return get<InboxStatsData>(`/workspaces/${workspaceId}/inbox/stats`)
  },

  markRead(workspaceId: string, itemId: string) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/read`)
  },

  markUnread(workspaceId: string, itemId: string) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/unread`)
  },

  resolve(workspaceId: string, itemId: string) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/resolve`)
  },

  unresolve(workspaceId: string, itemId: string) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/unresolve`)
  },

  archive(workspaceId: string, itemId: string) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/archive`)
  },

  assign(workspaceId: string, itemId: string, data: AssignInboxItemRequest) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/assign`, data)
  },

  unassign(workspaceId: string, itemId: string) {
    return post<InboxItemData>(`/workspaces/${workspaceId}/inbox/${itemId}/unassign`)
  },

  bulkRead(workspaceId: string, data: BulkInboxRequest) {
    return post<void>(`/workspaces/${workspaceId}/inbox/bulk-read`, data)
  },

  bulkResolve(workspaceId: string, data: BulkInboxRequest) {
    return post<void>(`/workspaces/${workspaceId}/inbox/bulk-resolve`, data)
  },

  // Replies
  listReplies(workspaceId: string, itemId: string) {
    return get<InboxReplyData[]>(`/workspaces/${workspaceId}/inbox/${itemId}/replies`)
  },

  createReply(workspaceId: string, itemId: string, data: CreateInboxReplyRequest) {
    return post<InboxReplyData>(`/workspaces/${workspaceId}/inbox/${itemId}/replies`, data)
  },
}
