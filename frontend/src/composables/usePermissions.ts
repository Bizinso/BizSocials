import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useWorkspaceStore } from '@/stores/workspace'
import { useTenantStore } from '@/stores/tenant'
import { hasWorkspacePermission, hasTenantPermission } from '@/utils/role-permissions'
import type { WorkspaceRole, TenantRole } from '@/types/enums'

export function usePermissions() {
  const authStore = useAuthStore()
  const workspaceStore = useWorkspaceStore()
  const tenantStore = useTenantStore()

  const workspaceRole = computed<WorkspaceRole | null>(
    () => (workspaceStore.currentWorkspace?.current_user_role as WorkspaceRole) ?? null,
  )
  const tenantRole = computed<TenantRole | null>(
    () => (authStore.user?.role_in_tenant as TenantRole) ?? null,
  )

  function canWorkspace(permission: string): boolean {
    if (!workspaceRole.value) return false
    return hasWorkspacePermission(permission, workspaceRole.value)
  }

  function canTenant(permission: string): boolean {
    if (!tenantRole.value) return false
    return hasTenantPermission(permission, tenantRole.value)
  }

  // Convenience computed for common checks
  const canCreateContent = computed(() => canWorkspace('canCreateContent'))
  const canApproveContent = computed(() => canWorkspace('canApproveContent'))
  const canManageSocialAccounts = computed(() => canWorkspace('canManageSocialAccounts'))
  const canManageMembers = computed(() => canWorkspace('canManageMembers'))
  const canManageBilling = computed(() => canTenant('canManageBilling'))
  const canManageTenant = computed(() => canTenant('canManageTenant'))
  const canInviteMembers = computed(() => canTenant('canInviteMembers'))
  const canViewAuditLogs = computed(() => canTenant('canViewAuditLogs'))

  return {
    workspaceRole,
    tenantRole,
    canWorkspace,
    canTenant,
    canCreateContent,
    canApproveContent,
    canManageSocialAccounts,
    canManageMembers,
    canManageBilling,
    canManageTenant,
    canInviteMembers,
    canViewAuditLogs,
  }
}
