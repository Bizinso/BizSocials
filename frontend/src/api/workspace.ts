import { get, post, put, del, getPaginated } from './client'
import type {
  WorkspaceData,
  CreateWorkspaceRequest,
  UpdateWorkspaceRequest,
  WorkspaceMemberData,
  AddWorkspaceMemberRequest,
  UpdateWorkspaceMemberRoleRequest,
} from '@/types/workspace'
import type { WorkspaceDashboardData } from '@/types/dashboard'
import type { PaginationParams } from '@/types/api'

export const workspaceApi = {
  // Workspaces
  list(params?: PaginationParams) {
    return getPaginated<WorkspaceData>('/workspaces', params as Record<string, unknown>)
  },

  get(id: string) {
    return get<WorkspaceData>(`/workspaces/${id}`)
  },

  create(data: CreateWorkspaceRequest) {
    return post<WorkspaceData>('/workspaces', data)
  },

  update(id: string, data: UpdateWorkspaceRequest) {
    return put<WorkspaceData>(`/workspaces/${id}`, data)
  },

  delete(id: string) {
    return del(`/workspaces/${id}`)
  },

  archive(id: string) {
    return post<WorkspaceData>(`/workspaces/${id}/archive`)
  },

  restore(id: string) {
    return post<WorkspaceData>(`/workspaces/${id}/restore`)
  },

  updateSettings(id: string, data: Record<string, unknown>) {
    return put<void>(`/workspaces/${id}/settings`, data)
  },

  // Members
  getMembers(workspaceId: string, params?: PaginationParams) {
    return getPaginated<WorkspaceMemberData>(
      `/workspaces/${workspaceId}/members`,
      params as Record<string, unknown>,
    )
  },

  addMember(workspaceId: string, data: AddWorkspaceMemberRequest) {
    return post<WorkspaceMemberData>(`/workspaces/${workspaceId}/members`, data)
  },

  updateMemberRole(workspaceId: string, userId: string, data: UpdateWorkspaceMemberRoleRequest) {
    return put<WorkspaceMemberData>(`/workspaces/${workspaceId}/members/${userId}`, data)
  },

  removeMember(workspaceId: string, userId: string) {
    return del(`/workspaces/${workspaceId}/members/${userId}`)
  },

  // Dashboard
  getDashboard(workspaceId: string) {
    return get<WorkspaceDashboardData>(`/workspaces/${workspaceId}/dashboard`)
  },
}
