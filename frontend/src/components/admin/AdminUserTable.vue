<script setup lang="ts">
import { useRouter } from 'vue-router'
import { formatDate } from '@/utils/formatters'
import type { AdminUserData } from '@/types/admin'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

defineProps<{
  users: AdminUserData[]
}>()

const router = useRouter()

function statusSeverity(status: string) {
  switch (status) {
    case 'active': return 'success'
    case 'pending': return 'warn'
    case 'suspended': return 'danger'
    case 'deactivated': return 'secondary'
    default: return 'secondary'
  }
}

function openUser(event: { data: AdminUserData }) {
  router.push({ name: 'admin-user-detail', params: { userId: event.data.id } })
}
</script>

<template>
  <DataTable :value="users" striped-rows class="cursor-pointer" @row-click="openUser">
    <Column field="name" header="Name">
      <template #body="{ data }">
        <div>
          <span class="font-medium text-gray-900">{{ data.name }}</span>
          <p class="text-xs text-gray-500">{{ data.email }}</p>
        </div>
      </template>
    </Column>
    <Column field="status" header="Status" style="width: 100px">
      <template #body="{ data }">
        <Tag :value="data.status_label" :severity="statusSeverity(data.status)" class="!text-xs" />
      </template>
    </Column>
    <Column field="role_label" header="Role" style="width: 100px" />
    <Column field="tenant_name" header="Tenant" style="width: 160px">
      <template #body="{ data }">
        <span class="text-sm">{{ data.tenant_name || '—' }}</span>
      </template>
    </Column>
    <Column header="MFA" style="width: 60px">
      <template #body="{ data }">
        <i :class="data.mfa_enabled ? 'pi pi-check text-green-500' : 'pi pi-times text-gray-300'" />
      </template>
    </Column>
    <Column field="last_login_at" header="Last Login" style="width: 120px">
      <template #body="{ data }">
        <span class="text-sm text-gray-500">{{ data.last_login_at ? formatDate(data.last_login_at) : '—' }}</span>
      </template>
    </Column>
  </DataTable>
</template>
