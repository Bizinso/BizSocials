<script setup lang="ts">
import type { TopPostData } from '@/types/analytics'
import { getPlatformColor } from '@/utils/platform-config'
import { formatNumber, formatDate, truncate } from '@/utils/formatters'

defineProps<{
  posts: TopPostData[]
}>()
</script>

<template>
  <div v-if="posts.length > 0" class="space-y-3">
    <div
      v-for="post in posts"
      :key="post.id"
      class="flex items-start gap-3 rounded-lg border border-gray-200 p-3"
    >
      <div
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
        :style="{ backgroundColor: getPlatformColor(post.platform) }"
      >
        {{ post.rank }}
      </div>

      <div v-if="post.thumbnail_url" class="h-16 w-16 shrink-0 overflow-hidden rounded">
        <img :src="post.thumbnail_url" :alt="post.title" class="h-full w-full object-cover" />
      </div>

      <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-gray-900">{{ post.title || truncate(post.content_excerpt || '', 80) }}</p>
        <p class="text-xs text-gray-400">{{ post.platform_label }} &middot; {{ formatDate(post.published_at) }}</p>
        <div class="mt-1 flex flex-wrap gap-3 text-xs text-gray-500">
          <span><i class="pi pi-eye mr-0.5" />{{ formatNumber(post.impressions) }}</span>
          <span><i class="pi pi-heart mr-0.5" />{{ formatNumber(post.likes) }}</span>
          <span><i class="pi pi-comment mr-0.5" />{{ formatNumber(post.comments) }}</span>
          <span><i class="pi pi-share-alt mr-0.5" />{{ formatNumber(post.shares) }}</span>
          <span class="font-medium text-primary-600">{{ post.engagement_rate.toFixed(1) }}% ER</span>
        </div>
      </div>
    </div>
  </div>
  <p v-else class="py-6 text-center text-sm text-gray-400">No top posts data available</p>
</template>
