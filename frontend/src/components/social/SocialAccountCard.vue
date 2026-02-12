<script setup lang="ts">
import type { SocialAccountData } from '@/types/social'
import AppPlatformIcon from '@/components/shared/AppPlatformIcon.vue'
import AppStatusBadge from '@/components/shared/AppStatusBadge.vue'
import Button from 'primevue/button'
import { formatRelative } from '@/utils/formatters'

defineProps<{
  account: SocialAccountData
}>()

const emit = defineEmits<{
  refresh: [account: SocialAccountData]
  disconnect: [account: SocialAccountData]
}>()
</script>

<template>
  <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center gap-3">
      <img
        v-if="account.profile_image_url"
        :src="account.profile_image_url"
        :alt="account.account_name"
        class="h-10 w-10 rounded-full object-cover"
      />
      <AppPlatformIcon v-else :platform="account.platform" size="lg" />
      <div>
        <div class="flex items-center gap-2">
          <p class="font-medium text-gray-900">{{ account.account_name }}</p>
          <AppPlatformIcon :platform="account.platform" size="sm" />
        </div>
        <p v-if="account.account_username" class="text-xs text-gray-500">@{{ account.account_username }}</p>
        <p class="text-xs text-gray-400">Connected {{ formatRelative(account.connected_at) }}</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <AppStatusBadge :status="account.status" />
      <Button
        v-if="account.requires_reconnect"
        icon="pi pi-refresh"
        label="Reconnect"
        size="small"
        severity="warn"
        outlined
        @click="emit('refresh', account)"
      />
      <Button
        icon="pi pi-trash"
        severity="danger"
        text
        rounded
        size="small"
        v-tooltip="'Disconnect'"
        @click="emit('disconnect', account)"
      />
    </div>
  </div>
</template>
