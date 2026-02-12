import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { inboxApi } from '@/api/inbox'
import type { InboxItemData, InboxStatsData } from '@/types/inbox'
import type { PaginationMeta } from '@/types/api'

export const useInboxStore = defineStore('inbox', () => {
  const items = ref<InboxItemData[]>([])
  const stats = ref<InboxStatsData | null>(null)
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)
  const selectedIds = ref<string[]>([])

  const hasSelection = computed(() => selectedIds.value.length > 0)

  async function fetchItems(
    workspaceId: string,
    params?: { page?: number; per_page?: number; status?: string; type?: string; platform?: string },
  ) {
    loading.value = true
    try {
      const response = await inboxApi.list(workspaceId, params)
      items.value = response.data
      pagination.value = response.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchStats(workspaceId: string) {
    stats.value = await inboxApi.stats(workspaceId)
  }

  function updateItem(item: InboxItemData) {
    const index = items.value.findIndex((i) => i.id === item.id)
    if (index !== -1) {
      items.value[index] = item
    }
  }

  function removeItem(id: string) {
    items.value = items.value.filter((i) => i.id !== id)
    selectedIds.value = selectedIds.value.filter((sid) => sid !== id)
  }

  function toggleSelection(id: string) {
    const index = selectedIds.value.indexOf(id)
    if (index >= 0) {
      selectedIds.value.splice(index, 1)
    } else {
      selectedIds.value.push(id)
    }
  }

  function clearSelection() {
    selectedIds.value = []
  }

  function selectAll() {
    selectedIds.value = items.value.map((i) => i.id)
  }

  function clear() {
    items.value = []
    stats.value = null
    pagination.value = null
    selectedIds.value = []
  }

  return {
    items,
    stats,
    pagination,
    loading,
    selectedIds,
    hasSelection,
    fetchItems,
    fetchStats,
    updateItem,
    removeItem,
    toggleSelection,
    clearSelection,
    selectAll,
    clear,
  }
})
