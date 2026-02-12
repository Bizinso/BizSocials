import { WorkspaceRole, TenantRole } from '@/types/enums'

// Workspace-level permissions
const workspacePermissions: Record<string, WorkspaceRole[]> = {
  canViewContent: [WorkspaceRole.Owner, WorkspaceRole.Admin, WorkspaceRole.Editor, WorkspaceRole.Viewer],
  canCreateContent: [WorkspaceRole.Owner, WorkspaceRole.Admin, WorkspaceRole.Editor],
  canEditContent: [WorkspaceRole.Owner, WorkspaceRole.Admin, WorkspaceRole.Editor],
  canDeleteContent: [WorkspaceRole.Owner, WorkspaceRole.Admin],
  canApproveContent: [WorkspaceRole.Owner, WorkspaceRole.Admin],
  canManageSocialAccounts: [WorkspaceRole.Owner, WorkspaceRole.Admin],
  canManageMembers: [WorkspaceRole.Owner, WorkspaceRole.Admin],
  canManageWorkspace: [WorkspaceRole.Owner, WorkspaceRole.Admin],
  canViewAnalytics: [WorkspaceRole.Owner, WorkspaceRole.Admin, WorkspaceRole.Editor, WorkspaceRole.Viewer],
  canExportReports: [WorkspaceRole.Owner, WorkspaceRole.Admin],
  canManageInbox: [WorkspaceRole.Owner, WorkspaceRole.Admin, WorkspaceRole.Editor],
  canReplyInbox: [WorkspaceRole.Owner, WorkspaceRole.Admin, WorkspaceRole.Editor],
}

// Tenant-level permissions
const tenantPermissions: Record<string, TenantRole[]> = {
  canManageBilling: [TenantRole.Owner],
  canManageTenant: [TenantRole.Owner, TenantRole.Admin],
  canInviteMembers: [TenantRole.Owner, TenantRole.Admin],
  canRemoveMembers: [TenantRole.Owner, TenantRole.Admin],
  canCreateWorkspace: [TenantRole.Owner, TenantRole.Admin],
  canDeleteWorkspace: [TenantRole.Owner],
  canViewAuditLogs: [TenantRole.Owner, TenantRole.Admin],
}

export function hasWorkspacePermission(permission: string, role: WorkspaceRole): boolean {
  const allowed = workspacePermissions[permission]
  return allowed ? allowed.includes(role) : false
}

export function hasTenantPermission(permission: string, role: TenantRole): boolean {
  const allowed = tenantPermissions[permission]
  return allowed ? allowed.includes(role) : false
}
