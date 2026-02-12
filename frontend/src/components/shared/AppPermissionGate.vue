<script setup lang="ts">
import { computed } from 'vue'
import { hasWorkspacePermission, hasTenantPermission } from '@/utils/role-permissions'
import type { WorkspaceRole, TenantRole } from '@/types/enums'

const props = defineProps<{
  permission: string
  workspaceRole?: WorkspaceRole
  tenantRole?: TenantRole
}>()

const isAllowed = computed(() => {
  if (props.workspaceRole) {
    return hasWorkspacePermission(props.permission, props.workspaceRole)
  }
  if (props.tenantRole) {
    return hasTenantPermission(props.permission, props.tenantRole)
  }
  return false
})
</script>

<template>
  <slot v-if="isAllowed" />
</template>
