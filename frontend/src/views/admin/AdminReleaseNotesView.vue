<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminReleaseNotesApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/formatters'
import type { ReleaseNoteData } from '@/types/feedback'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const toast = useToast()
const { confirmDelete } = useConfirm()
const releases = ref<ReleaseNoteData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const result = await adminReleaseNotesApi.list()
    releases.value = result.data
  } finally {
    loading.value = false
  }
})

async function publishRelease(release: ReleaseNoteData) {
  try {
    await adminReleaseNotesApi.publish(release.id)
    toast.success('Release published')
    const result = await adminReleaseNotesApi.list()
    releases.value = result.data
  } catch { toast.error('Failed to publish') }
}

async function removeRelease(release: ReleaseNoteData) {
  confirmDelete({
    message: `Delete release "${release.version}"?`,
    async onAccept() {
      try {
        await adminReleaseNotesApi.remove(release.id)
        releases.value = releases.value.filter((r) => r.id !== release.id)
        toast.success('Release deleted')
      } catch { toast.error('Failed to delete') }
    },
  })
}

function statusSeverity(status: string) {
  if (status === 'published') return 'success'
  if (status === 'scheduled') return 'info'
  return 'warn'
}

function typeSeverity(type: string) {
  if (type === 'major') return 'danger'
  if (type === 'minor') return 'info'
  if (type === 'hotfix') return 'warn'
  return 'secondary'
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Release Notes</h1>

    <AppLoadingSkeleton v-if="loading" :lines="6" />
    <DataTable v-else :value="releases" striped-rows>
      <Column field="version" header="Version" style="width: 100px">
        <template #body="{ data }">
          <Tag :value="data.version" :severity="typeSeverity(data.release_type)" />
        </template>
      </Column>
      <Column field="title" header="Title">
        <template #body="{ data }">
          <span class="font-medium text-gray-900">{{ data.title }}</span>
        </template>
      </Column>
      <Column field="status" header="Status" style="width: 100px">
        <template #body="{ data }">
          <Tag :value="data.status" :severity="statusSeverity(data.status)" class="!text-xs capitalize" />
        </template>
      </Column>
      <Column header="Items" style="width: 80px">
        <template #body="{ data }">
          {{ data.items?.length || 0 }}
        </template>
      </Column>
      <Column field="published_at" header="Published" style="width: 120px">
        <template #body="{ data }">
          <span class="text-sm text-gray-500">{{ data.published_at ? formatDate(data.published_at) : '—' }}</span>
        </template>
      </Column>
      <Column header="Actions" style="width: 120px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button v-if="data.status !== 'published'" icon="pi pi-check" severity="success" text size="small" title="Publish" @click="publishRelease(data)" />
            <Button icon="pi pi-trash" severity="danger" text size="small" @click="removeRelease(data)" />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>
