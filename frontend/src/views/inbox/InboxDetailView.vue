<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { inboxApi } from '@/api/inbox'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxItemData } from '@/types/inbox'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import InboxThread from '@/components/inbox/InboxThread.vue'
import InboxAssignDialog from '@/components/inbox/InboxAssignDialog.vue'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const itemId = computed(() => route.params.itemId as string)

const item = ref<InboxItemData | null>(null)
const loading = ref(true)
const showAssignDialog = ref(false)

onMounted(async () => {
  try {
    item.value = await inboxApi.get(workspaceId.value, itemId.value)
    // Auto-mark as read
    if (item.value.status === 'unread') {
      item.value = await inboxApi.markRead(workspaceId.value, itemId.value)
    }
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
})

function onUpdated(updated: InboxItemData) {
  item.value = updated
}

function onAssigned(updated: InboxItemData) {
  item.value = updated
  showAssignDialog.value = false
}

function goBack() {
  router.push(`/app/w/${workspaceId.value}/inbox`)
}
</script>

<template>
  <div>
    <div class="mb-4">
      <Button label="Back to Inbox" icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="6" />

    <template v-else-if="item">
      <AppPageHeader :title="item.author_name" :description="`${item.item_type} via ${item.account_name || 'Unknown'}`" />

      <AppCard>
        <InboxThread
          :workspace-id="workspaceId"
          :item="item"
          @updated="onUpdated"
          @assign="showAssignDialog = true"
        />
      </AppCard>

      <InboxAssignDialog
        v-model:visible="showAssignDialog"
        :workspace-id="workspaceId"
        :item="item"
        @assigned="onAssigned"
      />
    </template>
  </div>
</template>
