<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInboxStore } from '@/stores/inbox'
import { inboxApi } from '@/api/inbox'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxItemData } from '@/types/inbox'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import InboxStatsBar from '@/components/inbox/InboxStatsBar.vue'
import InboxFilters from '@/components/inbox/InboxFilters.vue'
import InboxList from '@/components/inbox/InboxList.vue'
import InboxBulkActions from '@/components/inbox/InboxBulkActions.vue'

const route = useRoute()
const router = useRouter()
const inboxStore = useInboxStore()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const filterStatus = ref('')
const filterType = ref('')
const filterSearch = ref('')

onMounted(() => {
  fetchItems()
  inboxStore.fetchStats(workspaceId.value)
})

watch([filterStatus, filterType, filterSearch], () => {
  inboxStore.clearSelection()
  fetchItems(1)
})

function fetchItems(page = 1) {
  inboxStore.fetchItems(workspaceId.value, {
    page,
    status: filterStatus.value || undefined,
    type: filterType.value || undefined,
  })
}

function onItemClick(item: InboxItemData) {
  router.push(`/app/w/${workspaceId.value}/inbox/${item.id}`)
}

function onItemSelect(item: InboxItemData) {
  inboxStore.toggleSelection(item.id)
}

async function bulkMarkRead() {
  try {
    await inboxApi.bulkRead(workspaceId.value, { ids: inboxStore.selectedIds })
    toast.success('Marked as read')
    inboxStore.clearSelection()
    fetchItems()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function bulkResolve() {
  try {
    await inboxApi.bulkResolve(workspaceId.value, { ids: inboxStore.selectedIds })
    toast.success('Resolved')
    inboxStore.clearSelection()
    fetchItems()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}
</script>

<template>
  <AppPageHeader title="Inbox" description="Manage comments and mentions from your social accounts" />

  <div class="space-y-4">
    <InboxStatsBar :stats="inboxStore.stats" />

    <AppCard :padding="false">
      <div class="border-b border-gray-200 px-4 py-3">
        <InboxFilters
          v-model:search="filterSearch"
          v-model:status="filterStatus"
          v-model:type="filterType"
        />
      </div>

      <InboxBulkActions
        :count="inboxStore.selectedIds.length"
        @mark-read="bulkMarkRead"
        @resolve="bulkResolve"
        @clear-selection="inboxStore.clearSelection()"
      />

      <InboxList
        :items="inboxStore.items"
        :loading="inboxStore.loading"
        :pagination="inboxStore.pagination"
        :selected-ids="inboxStore.selectedIds"
        @click="onItemClick"
        @select="onItemSelect"
        @page="fetchItems"
      />
    </AppCard>
  </div>
</template>
