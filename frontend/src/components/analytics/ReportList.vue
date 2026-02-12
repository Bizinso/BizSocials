<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { reportsApi } from '@/api/reports'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import type { ReportData } from '@/types/report'
import type { PaginationMeta } from '@/types/api'
import ReportDownloadButton from './ReportDownloadButton.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { formatDate } from '@/utils/formatters'

const props = defineProps<{
  workspaceId: string
}>()

const toast = useToast()
const { confirmDelete } = useConfirm()
const reports = ref<ReportData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)

onMounted(() => fetchReports())

async function fetchReports(page = 1) {
  loading.value = true
  try {
    const response = await reportsApi.list(props.workspaceId, { page })
    reports.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

function statusSeverity(status: string) {
  switch (status) {
    case 'completed': return 'success'
    case 'processing': return 'info'
    case 'pending': return 'secondary'
    case 'failed': return 'danger'
    case 'expired': return 'warn'
    default: return 'secondary'
  }
}

function deleteReport(report: ReportData) {
  confirmDelete({
    message: `Delete report "${report.name}"?`,
    async onAccept() {
      try {
        await reportsApi.delete(props.workspaceId, report.id)
        reports.value = reports.value.filter((r) => r.id !== report.id)
        toast.success('Report deleted')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}
</script>

<template>
  <div>
    <AppLoadingSkeleton v-if="loading" :lines="4" :count="3" />

    <template v-else-if="reports.length > 0">
      <DataTable :value="reports" striped-rows class="text-sm">
        <Column header="Report" class="min-w-[200px]">
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-900">{{ data.name }}</p>
              <p class="text-xs text-gray-500">{{ data.report_type_label }} &middot; {{ formatDate(data.date_from) }} - {{ formatDate(data.date_to) }}</p>
            </div>
          </template>
        </Column>
        <Column header="Status" class="w-[100px]">
          <template #body="{ data }">
            <Tag :value="data.status_label" :severity="statusSeverity(data.status)" />
          </template>
        </Column>
        <Column header="Size" class="w-[100px]">
          <template #body="{ data }">
            <span class="text-sm text-gray-500">{{ data.file_size_human || '—' }}</span>
          </template>
        </Column>
        <Column header="Created" class="w-[120px]">
          <template #body="{ data }">
            <span class="text-sm text-gray-500">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column header="" class="w-[100px]">
          <template #body="{ data }">
            <div class="flex gap-1">
              <ReportDownloadButton :workspace-id="workspaceId" :report="data" />
              <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="deleteReport(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>

    <AppEmptyState
      v-else
      title="No reports yet"
      description="Generate your first analytics report."
      icon="pi pi-file-pdf"
    />
  </div>
</template>
