<script setup lang="ts">
import { ref } from 'vue'
import { socialApi } from '@/api/social'
import { useSocialStore } from '@/stores/social'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { SocialPlatform } from '@/types/enums'
import { platformConfigs } from '@/utils/platform-config'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const props = defineProps<{
  visible: boolean
  workspaceId: string
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  connected: []
}>()

const toast = useToast()
const connecting = ref<string | null>(null)

const platforms = Object.entries(platformConfigs).map(([key, config]) => ({
  value: key as SocialPlatform,
  ...config,
}))

async function connectPlatform(platform: SocialPlatform) {
  connecting.value = platform
  try {
    // Store workspace context for the callback page
    localStorage.setItem('oauth_workspace_id', props.workspaceId)
    const data = await socialApi.getAuthorizationUrl(platform)
    window.location.href = data.url
  } catch (e) {
    toast.error(parseApiError(e).message)
    connecting.value = null
  }
}

function onHide() {
  emit('update:visible', false)
}
</script>

<template>
  <Dialog
    :visible="props.visible"
    header="Connect Social Account"
    :modal="true"
    :closable="true"
    :style="{ width: '480px' }"
    @update:visible="onHide"
  >
    <p class="mb-4 text-sm text-gray-500">
      Select a platform to connect. You'll be redirected to authorize access.
    </p>
    <div class="space-y-2">
      <button
        v-for="platform in platforms"
        :key="platform.value"
        class="flex w-full items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50"
        :disabled="connecting !== null"
        @click="connectPlatform(platform.value)"
      >
        <div
          class="flex h-10 w-10 items-center justify-center rounded-full text-white"
          :style="{ backgroundColor: platform.color }"
        >
          <i :class="platform.icon" class="text-lg" />
        </div>
        <div class="flex-1 text-left">
          <p class="font-medium text-gray-900">{{ platform.label }}</p>
          <p class="text-xs text-gray-500">Connect your {{ platform.label }} account</p>
        </div>
        <i
          v-if="connecting === platform.value"
          class="pi pi-spin pi-spinner text-gray-400"
        />
        <i v-else class="pi pi-arrow-right text-gray-400" />
      </button>
    </div>
  </Dialog>
</template>
