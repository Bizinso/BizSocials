import type { WorkspaceStatus, WorkspaceRole, UserStatus } from './enums'

export interface WorkspaceData {
  id: string
  tenant_id: string
  name: string
  slug: string
  description: string | null
  status: WorkspaceStatus
  icon: string | null
  color: string | null
  settings: Record<string, unknown>
  member_count: number
  current_user_role: string | null
  created_at: string
}

export interface CreateWorkspaceRequest {
  name: string
  description?: string | null
  icon?: string | null
  color?: string | null
}

export interface UpdateWorkspaceRequest {
  name?: string
  description?: string | null
  icon?: string | null
  color?: string | null
}

export interface WorkspaceMemberData {
  id: string
  user_id: string
  name: string
  email: string
  role: WorkspaceRole
  avatar_url: string | null
  joined_at: string
}

export interface AddWorkspaceMemberRequest {
  user_id: string
  role?: WorkspaceRole
}

export interface UpdateWorkspaceMemberRoleRequest {
  role: WorkspaceRole
}
