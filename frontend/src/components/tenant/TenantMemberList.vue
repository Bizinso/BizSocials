<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { tenantApi } from '@/api/tenant'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import { TenantRole } from '@/types/enums'
import type { TenantMemberData } from '@/types/tenant'
import type { PaginationMeta } from '@/types/api'
import AppAvatar from '@/components/shared/AppAvatar.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const toast = useToast()
const { confirmDelete } = useConfirm()

const members = ref<TenantMemberData[]>([])
const loading = ref(false)
const pagination = ref<PaginationMeta | null>(null)

const roleOptions = [
  { label: 'Owner', value: TenantRole.Owner },
  { label: 'Admin', value: TenantRole.Admin },
  { label: 'Member', value: TenantRole.Member },
]

onMounted(() => fetchMembers())

async function fetchMembers(page = 1) {
  loading.value = true
  try {
    const response = await tenantApi.getMembers({ page })
    members.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

async function changeRole(member: TenantMemberData, role: TenantRole) {
  try {
    await tenantApi.updateMemberRole(member.id, { role })
    member.role = role
    toast.success('Role updated')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function removeMember(member: TenantMemberData) {
  confirmDelete({
    message: `Remove ${member.name} from the organization?`,
    async onAccept() {
      try {
        await tenantApi.removeMember(member.id)
        members.value = members.value.filter((m) => m.id !== member.id)
        toast.success('Member removed')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}

function statusSeverity(status: string) {
  return status === 'active' ? 'success' : 'warn'
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
    <Column header="Status" class="w-[100px]">
      <template #body="{ data }">
        <Tag :value="data.status" :severity="statusSeverity(data.status)" />
      </template>
    </Column>
    <Column header="Role" class="w-[160px]">
      <template #body="{ data }">
        <Select
          :model-value="data.role"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          class="w-full"
          @change="(e: any) => changeRole(data, e.value)"
        />
      </template>
    </Column>
    <Column header="" class="w-[60px]">
      <template #body="{ data }">
        <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="removeMember(data)" />
      </template>
    </Column>
  </DataTable>
</template>
