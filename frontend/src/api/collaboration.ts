import { get, post, put, del, getPaginated } from './client'
import type {
  ApprovalWorkflowData,
  CreateWorkflowRequest,
  WorkspaceTaskData,
  CreateTaskRequest,
  PostNoteData,
  CreatePostNoteRequest,
  PostRevisionData,
} from '@/types/collaboration'
import type { PaginationParams } from '@/types/api'

const base = (wsId: string) => `/workspaces/${wsId}`

// Approval Workflows
export const approvalWorkflowApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<ApprovalWorkflowData>(`${base(workspaceId)}/approval-workflows`, params as Record<string, unknown>)
  },
  create(workspaceId: string, data: CreateWorkflowRequest) {
    return post<ApprovalWorkflowData>(`${base(workspaceId)}/approval-workflows`, data)
  },
  show(workspaceId: string, id: string) {
    return get<ApprovalWorkflowData>(`${base(workspaceId)}/approval-workflows/${id}`)
  },
  update(workspaceId: string, id: string, data: Partial<CreateWorkflowRequest>) {
    return put<ApprovalWorkflowData>(`${base(workspaceId)}/approval-workflows/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/approval-workflows/${id}`)
  },
  setDefault(workspaceId: string, id: string) {
    return post<ApprovalWorkflowData>(`${base(workspaceId)}/approval-workflows/${id}/set-default`)
  },
}

// Workspace Tasks
export const workspaceTaskApi = {
  list(workspaceId: string, params?: PaginationParams & { status?: string; assigned_to_user_id?: string; priority?: string }) {
    return getPaginated<WorkspaceTaskData>(`${base(workspaceId)}/tasks`, params as Record<string, unknown>)
  },
  create(workspaceId: string, data: CreateTaskRequest) {
    return post<WorkspaceTaskData>(`${base(workspaceId)}/tasks`, data)
  },
  show(workspaceId: string, id: string) {
    return get<WorkspaceTaskData>(`${base(workspaceId)}/tasks/${id}`)
  },
  update(workspaceId: string, id: string, data: Partial<CreateTaskRequest & { status?: string }>) {
    return put<WorkspaceTaskData>(`${base(workspaceId)}/tasks/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/tasks/${id}`)
  },
  complete(workspaceId: string, id: string) {
    return post<WorkspaceTaskData>(`${base(workspaceId)}/tasks/${id}/complete`)
  },
}

// Post Notes
export const postNoteApi = {
  list(workspaceId: string, postId: string) {
    return get<PostNoteData[]>(`${base(workspaceId)}/posts/${postId}/notes`)
  },
  create(workspaceId: string, postId: string, data: CreatePostNoteRequest) {
    return post<PostNoteData>(`${base(workspaceId)}/posts/${postId}/notes`, data)
  },
  delete(workspaceId: string, noteId: string) {
    return del(`${base(workspaceId)}/post-notes/${noteId}`)
  },
}

// Post Revisions
export const postRevisionApi = {
  list(workspaceId: string, postId: string) {
    return get<PostRevisionData[]>(`${base(workspaceId)}/posts/${postId}/revisions`)
  },
  show(workspaceId: string, revisionId: string) {
    return get<PostRevisionData>(`${base(workspaceId)}/post-revisions/${revisionId}`)
  },
  restore(workspaceId: string, revisionId: string) {
    return post<void>(`${base(workspaceId)}/post-revisions/${revisionId}/restore`)
  },
}
