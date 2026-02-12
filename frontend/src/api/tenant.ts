import { get, put, post, del, getPaginated } from './client'
import type {
  TenantData,
  UpdateTenantRequest,
  TenantMemberData,
  InvitationData,
  InviteUserRequest,
  UpdateMemberRoleRequest,
  TenantStatsData,
} from '@/types/tenant'
import type { PaginatedResponse, PaginationParams } from '@/types/api'

export const tenantApi = {
  // Tenant
  getCurrent() {
    return get<TenantData>('/tenants/current')
  },

  update(data: UpdateTenantRequest) {
    return put<TenantData>('/tenants/current', data)
  },

  updateSettings(data: Record<string, unknown>) {
    return put<void>('/tenants/current/settings', data)
  },

  getStats() {
    return get<TenantStatsData>('/tenants/current/stats')
  },

  // Members
  getMembers(params?: PaginationParams) {
    return getPaginated<TenantMemberData>('/tenants/current/members', params as Record<string, unknown>)
  },

  updateMemberRole(userId: string, data: UpdateMemberRoleRequest) {
    return put<TenantMemberData>(`/tenants/current/members/${userId}`, data)
  },

  removeMember(userId: string) {
    return del(`/tenants/current/members/${userId}`)
  },

  // Invitations
  getInvitations(params?: PaginationParams) {
    return getPaginated<InvitationData>('/tenants/current/invitations', params as Record<string, unknown>)
  },

  sendInvitation(data: InviteUserRequest) {
    return post<InvitationData>('/tenants/current/invitations', data)
  },

  resendInvitation(id: string) {
    return post<void>(`/tenants/current/invitations/${id}/resend`)
  },

  cancelInvitation(id: string) {
    return del(`/tenants/current/invitations/${id}`)
  },
}
