<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminFeedbackApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { formatDate } from '@/utils/formatters'
import type { FeedbackData, FeedbackStatsData } from '@/types/feedback'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'

const toast = useToast()
const items = ref<FeedbackData[]>([])
const stats = ref<FeedbackStatsData | null>(null)
const loading = ref(true)
const totalRecords = ref(0)
const currentPage = ref(1)
const statusFilter = ref('')

const statuses = [
  { label: 'All', value: '' },
  { label: 'New', value: 'new' },
  { label: 'Under Review', value: 'under_review' },
  { label: 'Planned', value: 'planned' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Declined', value: 'declined' },
]

async function fetchData() {
  loading.value = true
  try {
    const [result, s] = await Promise.all([
      adminFeedbackApi.list({ page: currentPage.value, status: statusFilter.value || undefined }),
      adminFeedbackApi.getStats(),
    ])
    items.value = result.data
    totalRecords.value = result.meta.total
    stats.value = s
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

async function updateStatus(feedback: FeedbackData, status: string) {
  try {
    await adminFeedbackApi.updateStatus(feedback.id, status)
    toast.success('Status updated')
    fetchData()
  } catch { toast.error('Failed to update status') }
}

function statusSeverity(status: string) {
  switch (status) {
    case 'new': return 'info'
    case 'under_review': return 'warn'
    case 'planned': return 'info'
    case 'shipped': return 'success'
    case 'declined': return 'danger'
    default: return 'secondary'
  }
}

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchData()
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Feedback Management</h1>

    <!-- Stats -->
    <div v-if="stats" class="mb-6 grid grid-cols-3 gap-4 sm:grid-cols-6">
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold">{{ stats.total_feedback }}</p>
        <p class="text-xs text-gray-500">Total</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ stats.new_feedback }}</p>
        <p class="text-xs text-gray-500">New</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-yellow-600">{{ stats.under_review }}</p>
        <p class="text-xs text-gray-500">Under Review</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-indigo-600">{{ stats.planned }}</p>
        <p class="text-xs text-gray-500">Planned</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-green-600">{{ stats.shipped }}</p>
        <p class="text-xs text-gray-500">Shipped</p>
      </div>
      <div class="rounded-lg border bg-white p-3 text-center">
        <p class="text-2xl font-bold text-red-600">{{ stats.declined }}</p>
        <p class="text-xs text-gray-500">Declined</p>
      </div>
    </div>

    <div class="mb-4">
      <Select v-model="statusFilter" :options="statuses" option-label="label" option-value="value" placeholder="Filter by status" class="w-44" @change="fetchData" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="6" />
    <template v-else>
      <DataTable :value="items" striped-rows>
        <Column field="title" header="Title">
          <template #body="{ data }">
            <span class="font-medium text-gray-900">{{ data.title }}</span>
          </template>
        </Column>
        <Column field="type_label" header="Type" style="width: 120px" />
        <Column field="status" header="Status" style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.status_label" :severity="statusSeverity(data.status)" class="!text-xs" />
          </template>
        </Column>
        <Column field="vote_count" header="Votes" style="width: 80px" />
        <Column header="Actions" style="width: 200px">
          <template #body="{ data }">
            <Select
              :model-value="data.status"
              :options="statuses.filter(s => s.value)"
              option-label="label"
              option-value="value"
              placeholder="Change"
              class="w-40"
              @update:model-value="updateStatus(data, $event)"
            />
          </template>
        </Column>
      </DataTable>
      <Paginator v-if="totalRecords > 15" :rows="15" :total-records="totalRecords" :first="(currentPage - 1) * 15" class="mt-4" @page="onPage" />
    </template>
  </div>
</template>
