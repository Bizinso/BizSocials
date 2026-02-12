import { get, post, put, del } from './client'
import type {
  TeamData,
  TeamDetailData,
  TeamMemberData,
  CreateTeamRequest,
  UpdateTeamRequest,
  AddTeamMemberRequest,
} from '@/types/team'

export const teamApi = {
  list(workspaceId: string, params?: { search?: string; per_page?: number }) {
    return get<{ data: TeamData[]; meta: Record<string, unknown> }>(
      `/workspaces/${workspaceId}/teams`,
      params as Record<string, unknown>,
    )
  },

  get(workspaceId: string, teamId: string) {
    return get<TeamDetailData>(`/workspaces/${workspaceId}/teams/${teamId}`)
  },

  create(workspaceId: string, data: CreateTeamRequest) {
    return post<TeamData>(`/workspaces/${workspaceId}/teams`, data)
  },

  update(workspaceId: string, teamId: string, data: UpdateTeamRequest) {
    return put<TeamData>(`/workspaces/${workspaceId}/teams/${teamId}`, data)
  },

  delete(workspaceId: string, teamId: string) {
    return del(`/workspaces/${workspaceId}/teams/${teamId}`)
  },

  addMember(workspaceId: string, teamId: string, data: AddTeamMemberRequest) {
    return post<TeamMemberData>(`/workspaces/${workspaceId}/teams/${teamId}/members`, data)
  },

  removeMember(workspaceId: string, teamId: string, userId: string) {
    return del(`/workspaces/${workspaceId}/teams/${teamId}/members/${userId}`)
  },
}
