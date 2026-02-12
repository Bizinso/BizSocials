<script setup lang="ts">
import type { RoadmapItemData } from '@/types/feedback'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'

defineProps<{
  item: RoadmapItemData
}>()

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
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-start justify-between">
      <h3 class="font-semibold text-gray-900">{{ item.title }}</h3>
      <Tag :value="item.status_label" :severity="statusSeverity(item.status)" class="!text-xs" />
    </div>
    <p v-if="item.description" class="mt-2 text-sm text-gray-600 line-clamp-2">{{ item.description }}</p>

    <ProgressBar
      v-if="item.status === 'in_progress' || item.status === 'beta'"
      :value="item.progress_percentage"
      :show-value="false"
      class="mt-3"
      style="height: 6px"
    />

    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
      <span>{{ item.category_label }}</span>
      <span v-if="item.target_quarter">{{ item.target_quarter }}</span>
      <span><i class="pi pi-comment mr-1" />{{ item.feedback_count }}</span>
      <span><i class="pi pi-chevron-up mr-1" />{{ item.vote_count }}</span>
    </div>
  </div>
</template>
