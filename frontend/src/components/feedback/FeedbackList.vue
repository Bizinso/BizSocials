<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { FeedbackData } from '@/types/feedback'
import FeedbackVoteButton from './FeedbackVoteButton.vue'
import Tag from 'primevue/tag'

defineProps<{
  items: FeedbackData[]
}>()

const router = useRouter()

function statusSeverity(status: string) {
  switch (status) {
    case 'new': return 'info'
    case 'under_review': return 'warn'
    case 'planned': return 'info'
    case 'shipped': return 'success'
    case 'declined': return 'danger'
    case 'duplicate': return 'secondary'
    default: return 'secondary'
  }
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="item in items"
      :key="item.id"
      class="flex gap-4 rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50"
    >
      <div class="flex-shrink-0 pt-1">
        <FeedbackVoteButton
          :feedback-id="item.id"
          :vote-count="item.vote_count"
          :user-vote="item.user_vote"
        />
      </div>
      <div
        class="min-w-0 flex-1 cursor-pointer"
        @click="router.push({ name: 'feedback-detail', params: { feedbackId: item.id } })"
      >
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-gray-900">{{ item.title }}</h3>
          <Tag :value="item.status_label" :severity="statusSeverity(item.status)" class="!text-xs" />
        </div>
        <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ item.description }}</p>
        <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
          <span>{{ item.type_label }}</span>
          <span v-if="item.category_label">{{ item.category_label }}</span>
          <span><i class="pi pi-comment mr-1" />{{ item.comment_count }}</span>
          <span v-if="item.submitter_name">by {{ item.submitter_name }}</span>
        </div>
      </div>
    </div>

    <p v-if="items.length === 0" class="py-8 text-center text-sm text-gray-500">
      No feedback yet. Be the first to submit!
    </p>
  </div>
</template>
