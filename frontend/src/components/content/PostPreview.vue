<script setup lang="ts">
import { computed } from 'vue'
import type { PostMediaData } from '@/types/content'
import type { SocialAccountData } from '@/types/social'
import { getPlatformLabel, getPlatformColor } from '@/utils/platform-config'
import AppAvatar from '@/components/shared/AppAvatar.vue'

const props = defineProps<{
  contentText: string
  media?: PostMediaData[]
  account?: SocialAccountData | null
}>()

const displayText = computed(() => {
  if (!props.contentText) return 'Your post preview will appear here...'
  return props.contentText
})

const previewMedia = computed(() => props.media?.slice(0, 4) || [])
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
    <div class="border-b border-gray-100 bg-gray-50 px-4 py-2">
      <span class="text-xs font-medium text-gray-500">Preview</span>
      <span v-if="account" class="ml-2 text-xs" :style="{ color: getPlatformColor(account.platform) }">
        {{ getPlatformLabel(account.platform) }}
      </span>
    </div>

    <div class="p-4">
      <div v-if="account" class="mb-3 flex items-center gap-3">
        <AppAvatar :name="account.account_name" :src="account.profile_image_url" size="sm" />
        <div>
          <p class="text-sm font-medium text-gray-900">{{ account.account_name }}</p>
          <p class="text-xs text-gray-400">Just now</p>
        </div>
      </div>

      <p class="whitespace-pre-wrap text-sm text-gray-800">{{ displayText }}</p>

      <div v-if="previewMedia.length > 0" class="mt-3 grid gap-1" :class="previewMedia.length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
        <div
          v-for="item in previewMedia"
          :key="item.id"
          class="overflow-hidden rounded-lg bg-gray-100"
        >
          <img
            v-if="item.media_type === 'image'"
            :src="item.file_url || item.file_path"
            :alt="item.original_filename || ''"
            class="h-40 w-full object-cover"
          />
          <div v-else class="flex h-40 items-center justify-center">
            <i class="pi pi-play-circle text-4xl text-gray-400" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
