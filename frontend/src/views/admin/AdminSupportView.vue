<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminSupportApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { formatDate } from '@/utils/formatters'
import type { SupportTicketData, SupportStatsData } from '@/types/support'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'

const toast = useToast()
const tickets = ref<SupportTicketData[]>([])
const stats = ref<SupportStatsData | null>(null)
const loading = ref(true)
const totalRecords = ref(0)
const currentPage = ref(1)
const statusFilter = ref('')

const statuses = [
  { label: 'All', value: '' },
  { label: 'New', value: 'new' },
  { label: 'Open', value: 'open' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Resolved', value: 'resolved' },
  { label: 'Closed', value: 'closed' },
]

async function fetchData() {
  loading.value = true
  try {
    const [result, s] = await Promise.all([
      adminSupportApi.listTickets({ page: currentPage.value, status: statusFilter.value || undefined }),
      adminSupportApi.getStats(),
    ])
    tickets.value = result.data
    totalRecords.value = result.meta.total
    stats.value = s
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

async function updateStatus(ticket: SupportTicketData, status: string) {
  try {
    await adminSupportApi.updateStatus(ticket.id, status)
    toast.success('Status updated')
    fetchData()
  } catch { toast.error('Failed to update status') }
}

function statusSeverity(status: string) {
  switch (status) {
    case 'new': case 'open': return 'info'
    case 'in_progress': case 'waiting_customer': return 'warn'
    case 'resolved': return 'success'
    case 'closed': return 'secondary'
    default: return 'secondary'
  }
}

function prioritySeverity(p: string) {
  if (p === 'urgent') return 'danger'
  if (p === 'high') return 'warn'
  if (p === 'medium') return 'info'
  return 'secondary'
}

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchData()
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Support Management</h1>

    <!-- Stats -->
    <div v-if="stats" class="mb-6 grid grid-cols-3 gap-4 sm:grid-cols-6">
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold">{{ stats.total_tickets }}</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ stats.open_tickets }}</p>
        <p class="text-xs text-gray-500">Open</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_tickets }}</p>
        <p class="text-xs text-gray-500">Pending</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-green-600">{{ stats.resolved_tickets }}</p>
        <p class="text-xs text-gray-500">Resolved</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold">{{ stats.closed_tickets }}</p>
        <p class="text-xs text-gray-500">Closed</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-red-600">{{ stats.unassigned_tickets }}</p>
        <p class="text-xs text-gray-500">Unassigned</p>
      </div>
    </div>

    <div class="mb-4">
      <Select v-model="statusFilter" :options="statuses" option-label="label" option-value="value" class="w-44" @change="fetchData" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else>
      <DataTable :value="tickets" striped-rows>
        <Column field="ticket_number" header="Ticket" style="width: 120px">
          <template #body="{ data }">
            <span class="font-mono text-sm text-primary-600">{{ data.ticket_number }}</span>
          </template>
        </Column>
        <Column field="subject" header="Subject">
          <template #body="{ data }">
            <span class="font-medium text-gray-900">{{ data.subject }}</span>
          </template>
        </Column>
        <Column field="user_name" header="User" style="width: 140px" />
        <Column field="status" header="Status" style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.status.replace(/_/g, ' ')" :severity="statusSeverity(data.status)" class="!text-xs capitalize" />
          </template>
        </Column>
        <Column field="priority" header="Priority" style="width: 90px">
          <template #body="{ data }">
            <Tag :value="data.priority" :severity="prioritySeverity(data.priority)" class="!text-xs capitalize" />
          </template>
        </Column>
        <Column field="assigned_to_name" header="Assigned" style="width: 120px">
          <template #body="{ data }">
            <span class="text-sm">{{ data.assigned_to_name || '—' }}</span>
          </template>
        </Column>
        <Column field="created_at" header="Created" style="width: 110px">
          <template #body="{ data }">
            <span class="text-sm text-gray-500">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
      </DataTable>
      <Paginator v-if="totalRecords > 15" :rows="15" :total-records="totalRecords" :first="(currentPage - 1) * 15" class="mt-4" @page="onPage" />
    </template>
  </div>
</template>
