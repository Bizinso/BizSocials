<script setup lang="ts">
import { formatDate } from '@/utils/formatters'
import type { SupportCommentData } from '@/types/support'
import Tag from 'primevue/tag'

defineProps<{
  comments: SupportCommentData[]
}>()
</script>

<template>
  <div class="space-y-4">
    <div
      v-for="comment in comments"
      :key="comment.id"
      class="rounded-lg border p-4"
      :class="[
        comment.is_internal ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200',
        comment.comment_type === 'system' ? 'border-gray-100 bg-gray-50' : '',
      ]"
    >
      <div class="flex items-center gap-2 text-sm">
        <span class="font-medium text-gray-900">{{ comment.author_name }}</span>
        <Tag
          v-if="comment.author_type === 'admin'"
          value="Staff"
          severity="info"
          class="!text-xs"
        />
        <Tag
          v-if="comment.is_internal"
          value="Internal"
          severity="warn"
          class="!text-xs"
        />
        <span class="text-gray-500">{{ formatDate(comment.created_at) }}</span>
      </div>
      <p
        class="mt-2 whitespace-pre-wrap text-sm"
        :class="comment.comment_type === 'system' ? 'italic text-gray-500' : 'text-gray-700'"
      >
        {{ comment.content }}
      </p>
    </div>

    <p v-if="comments.length === 0" class="py-4 text-center text-sm text-gray-500">
      No replies yet.
    </p>
  </div>
</template>
