<script setup lang="ts">
import type { InboxItemData } from '@/types/inbox'
import type { PaginationMeta } from '@/types/api'
import InboxListItem from './InboxListItem.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Paginator from 'primevue/paginator'

defineProps<{
  items: InboxItemData[]
  loading: boolean
  pagination: PaginationMeta | null
  selectedIds: string[]
}>()

const emit = defineEmits<{
  click: [item: InboxItemData]
  select: [item: InboxItemData]
  page: [page: number]
}>()

function isSelected(id: string): boolean {
  return false // will be bound from parent
}

function onPageChange(event: any) {
  emit('page', event.page + 1)
}
</script>

<template>
  <div>
    <AppLoadingSkeleton v-if="loading" :lines="3" :count="5" />

    <template v-else-if="items.length > 0">
      <div class="divide-y divide-gray-100">
        <InboxListItem
          v-for="item in items"
          :key="item.id"
          :item="item"
          :selected="selectedIds.includes(item.id)"
          @click="emit('click', $event)"
          @select="emit('select', $event)"
        />
      </div>

      <Paginator
        v-if="pagination && pagination.last_page > 1"
        :rows="pagination.per_page"
        :total-records="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        class="mt-2"
        @page="onPageChange"
      />
    </template>

    <AppEmptyState
      v-else
      title="Inbox is empty"
      description="Comments and mentions from your social accounts will appear here."
      icon="pi pi-inbox"
    />
  </div>
</template>
