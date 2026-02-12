<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSocialStore } from '@/stores/social'
import { socialApi } from '@/api/social'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import type { SocialAccountData } from '@/types/social'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import SocialAccountCard from './SocialAccountCard.vue'

const props = defineProps<{
  workspaceId: string
}>()

const socialStore = useSocialStore()
const toast = useToast()
const { confirmDelete } = useConfirm()

onMounted(() => socialStore.fetchAccounts(props.workspaceId))

async function handleRefresh(account: SocialAccountData) {
  try {
    await socialApi.refresh(props.workspaceId, account.id)
    await socialStore.fetchAccounts(props.workspaceId)
    toast.success('Account refreshed!')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function handleDisconnect(account: SocialAccountData) {
  confirmDelete({
    message: `Disconnect ${account.account_name}? You can reconnect later.`,
    async onAccept() {
      try {
        await socialApi.disconnect(props.workspaceId, account.id)
        socialStore.removeAccount(account.id)
        toast.success('Account disconnected')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}
</script>

<template>
  <div v-if="socialStore.loading" class="space-y-3">
    <AppLoadingSkeleton v-for="i in 3" :key="i" :lines="2" has-avatar />
  </div>

  <AppEmptyState
    v-else-if="socialStore.accounts.length === 0"
    icon="pi pi-share-alt"
    title="No social accounts connected"
    description="Connect your social media accounts to start publishing."
  >
    <slot name="empty-action" />
  </AppEmptyState>

  <div v-else class="space-y-3">
    <SocialAccountCard
      v-for="account in socialStore.accounts"
      :key="account.id"
      :account="account"
      @refresh="handleRefresh"
      @disconnect="handleDisconnect"
    />
  </div>
</template>
