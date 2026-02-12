<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { auditApi } from '@/api/audit'
import type { AuditLogData } from '@/types/audit'
import type { PaginationMeta } from '@/types/api'
import { formatDateTime } from '@/utils/formatters'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Paginator from 'primevue/paginator'

const logs = ref<AuditLogData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)

onMounted(() => fetchLogs())

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const response = await auditApi.listLogs({ page, per_page: 25 })
    logs.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

function actionSeverity(action: string) {
  switch (action) {
    case 'delete': return 'danger'
    case 'create': return 'success'
    case 'update': case 'settings_change': return 'info'
    case 'login': case 'logout': return 'secondary'
    default: return 'secondary'
  }
}

function onPageChange(event: any) {
  fetchLogs(event.page + 1)
}
</script>

<template>
  <AppPageHeader title="Audit Log" description="Track all actions and changes in your organization" />

  <AppCard>
    <AppLoadingSkeleton v-if="loading" :lines="4" :count="5" />

    <template v-else-if="logs.length > 0">
      <DataTable :value="logs" striped-rows class="text-sm">
        <Column header="Action" class="min-w-[180px]">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <Tag :value="data.action.replace(/_/g, ' ')" :severity="actionSeverity(data.action)" />
              <span class="text-gray-500">{{ data.auditable_type }}</span>
            </div>
          </template>
        </Column>
        <Column header="Description" class="min-w-[200px]">
          <template #body="{ data }">
            <span class="text-gray-700">{{ data.description || '—' }}</span>
          </template>
        </Column>
        <Column header="User" class="w-[140px]">
          <template #body="{ data }">
            <span class="text-gray-700">{{ data.user_name || data.admin_name || '—' }}</span>
          </template>
        </Column>
        <Column header="IP" class="w-[120px]">
          <template #body="{ data }">
            <span class="text-xs text-gray-500">{{ data.ip_address || '—' }}</span>
          </template>
        </Column>
        <Column header="Date" class="w-[160px]">
          <template #body="{ data }">
            <span class="text-xs text-gray-500">{{ formatDateTime(data.created_at) }}</span>
          </template>
        </Column>
      </DataTable>

      <Paginator
        v-if="pagination && pagination.last_page > 1"
        :rows="pagination.per_page"
        :total-records="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        class="mt-4"
        @page="onPageChange"
      />
    </template>

    <AppEmptyState
      v-else
      title="No audit logs"
      description="Activity will be recorded here as your team uses the platform."
      icon="pi pi-list"
    />
  </AppCard>
</template>
