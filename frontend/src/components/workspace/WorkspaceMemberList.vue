<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { workspaceApi } from '@/api/workspace'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { usePermissions } from '@/composables/usePermissions'
import { parseApiError } from '@/utils/error-handler'
import { WorkspaceRole } from '@/types/enums'
import type { WorkspaceMemberData } from '@/types/workspace'
import type { PaginationMeta } from '@/types/api'
import AppAvatar from '@/components/shared/AppAvatar.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const props = defineProps<{
  workspaceId: string
}>()

const toast = useToast()
const { confirmDelete } = useConfirm()
const { canManageMembers } = usePermissions()

const members = ref<WorkspaceMemberData[]>([])
const loading = ref(false)
const pagination = ref<PaginationMeta | null>(null)

const roleOptions = [
  { label: 'Owner', value: WorkspaceRole.Owner },
  { label: 'Admin', value: WorkspaceRole.Admin },
  { label: 'Editor', value: WorkspaceRole.Editor },
  { label: 'Viewer', value: WorkspaceRole.Viewer },
]

onMounted(() => fetchMembers())

async function fetchMembers(page = 1) {
  loading.value = true
  try {
    const response = await workspaceApi.getMembers(props.workspaceId, { page })
    members.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

async function changeRole(member: WorkspaceMemberData, role: WorkspaceRole) {
  try {
    await workspaceApi.updateMemberRole(props.workspaceId, member.user_id, { role })
    member.role = role
    toast.success('Role updated')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function removeMember(member: WorkspaceMemberData) {
  confirmDelete({
    message: `Remove ${member.name} from this workspace?`,
    async onAccept() {
      try {
        await workspaceApi.removeMember(props.workspaceId, member.user_id)
        members.value = members.value.filter((m) => m.id !== member.id)
        toast.success('Member removed')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}

function roleSeverity(role: WorkspaceRole) {
  const map: Record<string, string> = {
    owner: 'danger',
    admin: 'warn',
    editor: 'info',
    viewer: 'secondary',
  }
  return (map[role] || 'secondary') as 'danger' | 'warn' | 'info' | 'secondary'
}
</script>

<template>
  <DataTable :value="members" :loading="loading" striped-rows class="text-sm">
    <Column header="Member" class="min-w-[200px]">
      <template #body="{ data }">
        <div class="flex items-center gap-3">
          <AppAvatar :name="data.name" :src="data.avatar_url" size="sm" />
          <div>
            <p class="font-medium text-gray-900">{{ data.name }}</p>
            <p class="text-xs text-gray-500">{{ data.email }}</p>
          </div>
        </div>
      </template>
    </Column>
    <Column header="Role" class="w-[160px]">
      <template #body="{ data }">
        <Select
          v-if="canManageMembers"
          :model-value="data.role"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          class="w-full"
          @change="(e: any) => changeRole(data, e.value)"
        />
        <Tag
          v-else
          :value="data.role"
          :severity="roleSeverity(data.role)"
        />
      </template>
    </Column>
    <Column v-if="canManageMembers" header="" class="w-[60px]">
      <template #body="{ data }">
        <Button
          icon="pi pi-trash"
          severity="danger"
          text
          rounded
          size="small"
          @click="removeMember(data)"
        />
      </template>
    </Column>
  </DataTable>
</template>
