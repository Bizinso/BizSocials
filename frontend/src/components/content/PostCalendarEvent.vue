<script setup lang="ts">
import type { PostData } from '@/types/content'
import PostStatusBadge from './PostStatusBadge.vue'
import { truncate } from '@/utils/formatters'
import dayjs from 'dayjs'

const props = defineProps<{
  post: PostData
}>()

const emit = defineEmits<{
  click: [post: PostData]
}>()

function timeLabel(): string {
  const date = props.post.scheduled_at || props.post.published_at || props.post.created_at
  return dayjs(date).format('h:mm A')
}
</script>

<template>
  <div
    class="cursor-pointer rounded border-l-2 border-primary-500 bg-white p-1.5 text-xs shadow-sm transition-shadow hover:shadow-md"
    @click="emit('click', post)"
  >
    <div class="mb-0.5 flex items-center justify-between">
      <span class="font-medium text-gray-600">{{ timeLabel() }}</span>
      <PostStatusBadge :status="post.status" />
    </div>
    <p class="text-gray-800">{{ post.content_text ? truncate(post.content_text, 60) : '(No content)' }}</p>
  </div>
</template>
