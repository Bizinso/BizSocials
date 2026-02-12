<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminPrivacyApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/formatters'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const toast = useToast()
const { confirmDelete } = useConfirm()
const exportRequests = ref<Record<string, unknown>[]>([])
const deletionRequests = ref<Record<string, unknown>[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const [exp, del] = await Promise.all([
      adminPrivacyApi.listExportRequests(),
      adminPrivacyApi.listDeletionRequests(),
    ])
    exportRequests.value = exp.data
    deletionRequests.value = del.data
  } finally {
    loading.value = false
  }
})

async function approveDeletion(request: Record<string, unknown>) {
  confirmDelete({
    message: 'Approve this data deletion? This action is irreversible.',
    async onAccept() {
      try {
        await adminPrivacyApi.approveDeletion(request.id as string)
        toast.success('Deletion approved')
        const result = await adminPrivacyApi.listDeletionRequests()
        deletionRequests.value = result.data
      } catch { toast.error('Failed to approve') }
    },
  })
}

async function rejectDeletion(request: Record<string, unknown>) {
  try {
    await adminPrivacyApi.rejectDeletion(request.id as string, 'Admin rejected')
    toast.success('Deletion rejected')
    const result = await adminPrivacyApi.listDeletionRequests()
    deletionRequests.value = result.data
  } catch { toast.error('Failed to reject') }
}

function statusSeverity(status: unknown) {
  if (status === 'completed') return 'success'
  if (status === 'processing') return 'info'
  if (status === 'pending') return 'warn'
  if (status === 'failed') return 'danger'
  return 'secondary'
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Data Privacy</h1>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else>
      <!-- Export Requests -->
      <section class="mb-10">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Export Requests</h2>
        <DataTable :value="exportRequests" striped-rows>
          <Column field="id" header="ID" style="width: 280px">
            <template #body="{ data }">
              <span class="font-mono text-xs">{{ data.id }}</span>
            </template>
          </Column>
          <Column field="status" header="Status" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="String(data.status)" :severity="statusSeverity(data.status)" class="!text-xs capitalize" />
            </template>
          </Column>
          <Column field="created_at" header="Requested" style="width: 140px">
            <template #body="{ data }">
              <span class="text-sm text-gray-500">{{ formatDate(data.created_at as string) }}</span>
            </template>
          </Column>
        </DataTable>
      </section>

      <!-- Deletion Requests -->
      <section>
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Deletion Requests</h2>
        <DataTable :value="deletionRequests" striped-rows>
          <Column field="id" header="ID" style="width: 280px">
            <template #body="{ data }">
              <span class="font-mono text-xs">{{ data.id }}</span>
            </template>
          </Column>
          <Column field="status" header="Status" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="String(data.status)" :severity="statusSeverity(data.status)" class="!text-xs capitalize" />
            </template>
          </Column>
          <Column field="created_at" header="Requested" style="width: 140px">
            <template #body="{ data }">
              <span class="text-sm text-gray-500">{{ formatDate(data.created_at as string) }}</span>
            </template>
          </Column>
          <Column header="Actions" style="width: 160px">
            <template #body="{ data }">
              <div v-if="data.status === 'pending'" class="flex gap-1">
                <Button icon="pi pi-check" severity="success" text size="small" title="Approve" @click="approveDeletion(data)" />
                <Button icon="pi pi-times" severity="danger" text size="small" title="Reject" @click="rejectDeletion(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </section>
    </template>
  </div>
</template>
