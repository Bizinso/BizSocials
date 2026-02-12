import type { TenantStatus, TenantType, TenantRole, InvitationStatus, UserStatus } from './enums'

export interface TenantData {
  id: string
  name: string
  slug: string
  type: TenantType
  status: TenantStatus
  logo_url: string | null
  website: string | null
  timezone: string | null
  settings: Record<string, unknown>
  created_at: string
}

export interface UpdateTenantRequest {
  name?: string
  website?: string | null
  timezone?: string | null
  industry?: string | null
  company_size?: string | null
}

export interface TenantMemberData {
  id: string
  name: string
  email: string
  role: TenantRole
  status: UserStatus
  avatar_url: string | null
  joined_at: string
}

export interface InvitationData {
  id: string
  email: string
  role: TenantRole
  status: InvitationStatus
  workspace_ids: string[] | null
  invited_by_name: string
  expires_at: string
  created_at: string
}

export interface InviteUserRequest {
  email: string
  role?: TenantRole
  workspace_ids?: string[] | null
}

export interface UpdateMemberRoleRequest {
  role: TenantRole
}

export interface TenantStatsData {
  total_members: number
  total_workspaces: number
  total_social_accounts: number
  total_posts: number
}
