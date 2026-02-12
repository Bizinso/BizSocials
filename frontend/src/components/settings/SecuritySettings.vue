<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { auditApi } from '@/api/audit'
import type { SecurityEventData, SecurityStatsData } from '@/types/audit'
import type { PaginationMeta } from '@/types/api'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Paginator from 'primevue/paginator'
import { formatDateTime } from '@/utils/formatters'

const events = ref<SecurityEventData[]>([])
const stats = ref<SecurityStatsData | null>(null)
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const [eventsRes, statsRes] = await Promise.all([
      auditApi.listSecurityEvents({ per_page: 20 }),
      auditApi.securityStats(),
    ])
    events.value = eventsRes.data
    pagination.value = eventsRes.meta
    stats.value = statsRes
  } finally {
    loading.value = false
  }
})

async function fetchEvents(page = 1) {
  loading.value = true
  try {
    const response = await auditApi.listSecurityEvents({ page, per_page: 20 })
    events.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

function severityColor(severity: string) {
  switch (severity) {
    case 'critical': return 'danger'
    case 'high': return 'danger'
    case 'medium': return 'warn'
    case 'low': return 'info'
    default: return 'secondary'
  }
}

function onPageChange(event: any) {
  fetchEvents(event.page + 1)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Stats -->
    <div v-if="stats" class="grid grid-cols-2 gap-4 md:grid-cols-4">
      <div class="rounded-lg border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ stats.total_events }}</p>
        <p class="text-sm text-gray-500">Total Events</p>
      </div>
      <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-center">
        <p class="text-2xl font-bold text-red-600">{{ stats.critical_events }}</p>
        <p class="text-sm text-gray-500">Critical</p>
      </div>
      <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-center">
        <p class="text-2xl font-bold text-orange-600">{{ stats.failed_logins_24h }}</p>
        <p class="text-sm text-gray-500">Failed Logins (24h)</p>
      </div>
      <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-center">
        <p class="text-2xl font-bold text-yellow-600">{{ stats.unresolved_events }}</p>
        <p class="text-sm text-gray-500">Unresolved</p>
      </div>
    </div>

    <!-- Events table -->
    <AppLoadingSkeleton v-if="loading" :lines="4" :count="3" />

    <template v-else-if="events.length > 0">
      <DataTable :value="events" striped-rows class="text-sm">
        <Column header="Event" class="min-w-[200px]">
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-900">{{ data.event_type.replace(/_/g, ' ') }}</p>
              <p v-if="data.description" class="text-xs text-gray-500">{{ data.description }}</p>
            </div>
          </template>
        </Column>
        <Column header="Severity" class="w-[100px]">
          <template #body="{ data }">
            <Tag :value="data.severity" :severity="severityColor(data.severity)" />
          </template>
        </Column>
        <Column header="User" class="w-[140px]">
          <template #body="{ data }">
            <span class="text-gray-700">{{ data.user_name || '—' }}</span>
          </template>
        </Column>
        <Column header="IP" class="w-[130px]">
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
      title="No security events"
      description="Security activity will be tracked here."
      icon="pi pi-shield"
    />
  </div>
</template>
