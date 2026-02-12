<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminRoadmapApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import type { RoadmapItemData } from '@/types/feedback'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import ProgressBar from 'primevue/progressbar'

const toast = useToast()
const { confirmDelete } = useConfirm()
const items = ref<RoadmapItemData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    items.value = await adminRoadmapApi.list()
  } finally {
    loading.value = false
  }
})

async function removeItem(item: RoadmapItemData) {
  confirmDelete({
    message: `Delete roadmap item "${item.title}"?`,
    async onAccept() {
      try {
        await adminRoadmapApi.remove(item.id)
        items.value = items.value.filter((i) => i.id !== item.id)
        toast.success('Item deleted')
      } catch { toast.error('Failed to delete') }
    },
  })
}

function statusSeverity(status: string) {
  switch (status) {
    case 'planned': return 'info'
    case 'in_progress': return 'warn'
    case 'beta': return 'info'
    case 'shipped': return 'success'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Roadmap Management</h1>

    <AppLoadingSkeleton v-if="loading" :lines="6" />
    <DataTable v-else :value="items" striped-rows>
      <Column field="title" header="Title">
        <template #body="{ data }">
          <span class="font-medium text-gray-900">{{ data.title }}</span>
        </template>
      </Column>
      <Column field="category_label" header="Category" style="width: 120px" />
      <Column field="status" header="Status" style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.status_label" :severity="statusSeverity(data.status)" class="!text-xs" />
        </template>
      </Column>
      <Column header="Progress" style="width: 120px">
        <template #body="{ data }">
          <ProgressBar :value="data.progress_percentage" :show-value="true" style="height: 16px" />
        </template>
      </Column>
      <Column field="vote_count" header="Votes" style="width: 80px" />
      <Column header="Actions" style="width: 80px">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text size="small" @click="removeItem(data)" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>
