<script setup lang="ts">
import { useRouter } from 'vue-router'
import { formatDate } from '@/utils/formatters'
import type { AdminTenantData } from '@/types/admin'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

defineProps<{
  tenants: AdminTenantData[]
}>()

const router = useRouter()

function statusSeverity(status: string) {
  switch (status) {
    case 'active': return 'success'
    case 'pending': return 'warn'
    case 'suspended': return 'danger'
    case 'terminated': return 'secondary'
    default: return 'secondary'
  }
}

function openTenant(event: { data: AdminTenantData }) {
  router.push({ name: 'admin-tenant-detail', params: { tenantId: event.data.id } })
}
</script>

<template>
  <DataTable :value="tenants" striped-rows class="cursor-pointer" @row-click="openTenant">
    <Column field="name" header="Name">
      <template #body="{ data }">
        <div>
          <span class="font-medium text-gray-900">{{ data.name }}</span>
          <span class="ml-2 text-xs text-gray-400">{{ data.slug }}</span>
        </div>
      </template>
    </Column>
    <Column field="type_label" header="Type" style="width: 140px" />
    <Column field="status" header="Status" style="width: 120px">
      <template #body="{ data }">
        <Tag :value="data.status_label" :severity="statusSeverity(data.status)" class="!text-xs" />
      </template>
    </Column>
    <Column field="plan_name" header="Plan" style="width: 120px">
      <template #body="{ data }">
        <span class="text-sm">{{ data.plan_name || '—' }}</span>
      </template>
    </Column>
    <Column field="user_count" header="Users" style="width: 80px" />
    <Column field="workspace_count" header="Workspaces" style="width: 100px" />
    <Column field="created_at" header="Created" style="width: 120px">
      <template #body="{ data }">
        <span class="text-sm text-gray-500">{{ formatDate(data.created_at) }}</span>
      </template>
    </Column>
  </DataTable>
</template>
