<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useSocialStore } from '@/stores/social'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import SocialAccountList from '@/components/social/SocialAccountList.vue'
import ConnectAccountDialog from '@/components/social/ConnectAccountDialog.vue'
import AccountHealthBadge from '@/components/social/AccountHealthBadge.vue'
import Button from 'primevue/button'

const route = useRoute()
const socialStore = useSocialStore()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const showConnectDialog = ref(false)

onMounted(async () => {
  try {
    await Promise.all([
      socialStore.fetchAccounts(workspaceId.value),
      socialStore.fetchHealth(workspaceId.value),
    ])
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
})

function onAccountConnected() {
  socialStore.fetchAccounts(workspaceId.value)
  socialStore.fetchHealth(workspaceId.value)
}
</script>

<template>
  <AppPageHeader title="Social Accounts" description="Connect and manage your social media accounts">
    <template #actions>
      <AccountHealthBadge v-if="socialStore.health" :health="socialStore.health" class="mr-3" />
      <Button label="Connect Account" icon="pi pi-plus" @click="showConnectDialog = true" />
    </template>
  </AppPageHeader>

  <AppCard>
    <SocialAccountList :workspace-id="workspaceId" />
  </AppCard>

  <ConnectAccountDialog
    v-model:visible="showConnectDialog"
    :workspace-id="workspaceId"
    @connected="onAccountConnected"
  />
</template>
