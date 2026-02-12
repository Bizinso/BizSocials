<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { privacyApi } from '@/api/privacy'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import type { DataExportRequestData, DataDeletionRequestData } from '@/types/privacy'
import { formatDate } from '@/utils/formatters'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const toast = useToast()
const { confirmDelete } = useConfirm()

const exportRequests = ref<DataExportRequestData[]>([])
const deletionRequests = ref<DataDeletionRequestData[]>([])
const loading = ref(false)
const requestingExport = ref(false)
const requestingDeletion = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const [exports, deletions] = await Promise.all([
      privacyApi.listExportRequests(),
      privacyApi.listDeletionRequests(),
    ])
    exportRequests.value = exports
    deletionRequests.value = deletions
  } finally {
    loading.value = false
  }
})

async function requestExport() {
  requestingExport.value = true
  try {
    const request = await privacyApi.requestExport({ format: 'json' })
    exportRequests.value.unshift(request)
    toast.success('Export request submitted. You will be notified when ready.')
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    requestingExport.value = false
  }
}

async function requestDeletion() {
  confirmDelete({
    message: 'This will permanently delete all your data. This action cannot be undone. Are you sure?',
    async onAccept() {
      requestingDeletion.value = true
      try {
        const request = await privacyApi.requestDeletion({
          reason: 'User-initiated data deletion request',
        })
        deletionRequests.value.unshift(request)
        toast.success('Deletion request submitted.')
      } catch (e) {
        toast.error(parseApiError(e).message)
      } finally {
        requestingDeletion.value = false
      }
    },
  })
}

async function cancelDeletionRequest(request: DataDeletionRequestData) {
  try {
    await privacyApi.cancelDeletion(request.id)
    deletionRequests.value = deletionRequests.value.filter((r) => r.id !== request.id)
    toast.success('Deletion request cancelled')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function openDownload(url: string) {
  globalThis.window.open(url, '_blank')
}

function statusSeverity(status: string) {
  switch (status) {
    case 'completed': return 'success'
    case 'processing': return 'info'
    case 'pending': return 'warn'
    case 'failed': return 'danger'
    case 'cancelled': return 'secondary'
    default: return 'secondary'
  }
}
</script>

<template>
  <AppLoadingSkeleton v-if="loading" :lines="5" />

  <div v-else class="space-y-8">
    <!-- Data Export -->
    <section>
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900">Export Your Data</h3>
          <p class="text-sm text-gray-500">Download a copy of all your data in JSON format.</p>
        </div>
        <Button label="Request Export" icon="pi pi-download" :loading="requestingExport" @click="requestExport" />
      </div>

      <div v-if="exportRequests.length > 0" class="space-y-2">
        <div
          v-for="req in exportRequests"
          :key="req.id"
          class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3"
        >
          <div>
            <p class="text-sm font-medium text-gray-900">
              Export Request
              <Tag :value="req.status" :severity="statusSeverity(req.status)" class="ml-2" />
            </p>
            <p class="text-xs text-gray-500">{{ formatDate(req.created_at) }}</p>
          </div>
          <Button
            v-if="req.download_url && req.status === 'completed'"
            label="Download"
            icon="pi pi-download"
            severity="info"
            size="small"
            @click="openDownload(req.download_url!)"
          />
        </div>
      </div>
    </section>

    <!-- Data Deletion -->
    <section>
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900">Delete Your Data</h3>
          <p class="text-sm text-gray-500">Permanently delete all your data. This cannot be undone.</p>
        </div>
        <Button label="Request Deletion" icon="pi pi-trash" severity="danger" :loading="requestingDeletion" @click="requestDeletion" />
      </div>

      <div v-if="deletionRequests.length > 0" class="space-y-2">
        <div
          v-for="req in deletionRequests"
          :key="req.id"
          class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3"
        >
          <div>
            <p class="text-sm font-medium text-gray-900">
              Deletion Request
              <Tag :value="req.status" :severity="statusSeverity(req.status)" class="ml-2" />
            </p>
            <p class="text-xs text-gray-500">
              {{ formatDate(req.created_at) }}
              <span v-if="req.scheduled_for"> &middot; Scheduled for {{ formatDate(req.scheduled_for) }}</span>
            </p>
          </div>
          <Button
            v-if="req.status === 'pending'"
            label="Cancel"
            severity="secondary"
            size="small"
            @click="cancelDeletionRequest(req)"
          />
        </div>
      </div>
    </section>
  </div>
</template>
