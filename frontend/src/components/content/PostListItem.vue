<script setup lang="ts">
import type { PostData } from '@/types/content'
import PostStatusBadge from './PostStatusBadge.vue'
import AppPlatformIcon from '@/components/shared/AppPlatformIcon.vue'
import Button from 'primevue/button'
import { formatRelative, truncate } from '@/utils/formatters'

defineProps<{
  post: PostData
}>()

const emit = defineEmits<{
  edit: [post: PostData]
  delete: [post: PostData]
  duplicate: [post: PostData]
}>()
</script>

<template>
  <div class="flex items-start gap-4 rounded-lg border border-gray-200 bg-white p-4 transition-shadow hover:shadow-sm">
    <div class="min-w-0 flex-1">
      <div class="mb-1 flex items-center gap-2">
        <PostStatusBadge :status="post.status" />
        <span class="text-xs capitalize text-gray-400">{{ post.post_type }}</span>
        <span class="text-xs text-gray-400">{{ formatRelative(post.created_at) }}</span>
      </div>
      <p class="mb-2 text-sm text-gray-900">
        {{ post.content_text ? truncate(post.content_text, 160) : '(No content)' }}
      </p>
      <div class="flex items-center gap-3 text-xs text-gray-500">
        <span v-if="post.target_count > 0">
          <i class="pi pi-share-alt mr-1" />{{ post.target_count }} target{{ post.target_count !== 1 ? 's' : '' }}
        </span>
        <span v-if="post.media_count > 0">
          <i class="pi pi-image mr-1" />{{ post.media_count }} media
        </span>
        <span v-if="post.scheduled_at">
          <i class="pi pi-clock mr-1" />{{ formatRelative(post.scheduled_at) }}
        </span>
        <span v-if="post.author_name">
          by {{ post.author_name }}
        </span>
      </div>
    </div>
    <div class="flex shrink-0 gap-1">
      <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Edit'" @click="emit('edit', post)" />
      <Button icon="pi pi-copy" text rounded size="small" v-tooltip="'Duplicate'" @click="emit('duplicate', post)" />
      <Button icon="pi pi-trash" severity="danger" text rounded size="small" v-tooltip="'Delete'" @click="emit('delete', post)" />
    </div>
  </div>
</template>
