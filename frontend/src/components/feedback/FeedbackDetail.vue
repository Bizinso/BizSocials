<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { feedbackApi } from '@/api/feedback'
import { useToast } from '@/composables/useToast'
import { formatDate } from '@/utils/formatters'
import type { FeedbackData, FeedbackCommentData } from '@/types/feedback'
import FeedbackVoteButton from './FeedbackVoteButton.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'

const props = defineProps<{
  feedback: FeedbackData
}>()

const toast = useToast()
const comments = ref<FeedbackCommentData[]>([])
const newComment = ref('')
const submitting = ref(false)

onMounted(async () => {
  comments.value = await feedbackApi.getComments(props.feedback.id)
})

async function addComment() {
  if (!newComment.value.trim()) return
  submitting.value = true
  try {
    const comment = await feedbackApi.addComment(props.feedback.id, {
      content: newComment.value,
    })
    comments.value.push(comment)
    newComment.value = ''
  } catch {
    toast.error('Failed to post comment')
  } finally {
    submitting.value = false
  }
}

function statusSeverity(status: string) {
  switch (status) {
    case 'new': return 'info'
    case 'under_review': return 'warn'
    case 'planned': return 'info'
    case 'shipped': return 'success'
    case 'declined': return 'danger'
    default: return 'secondary'
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-start gap-4">
      <FeedbackVoteButton
        :feedback-id="feedback.id"
        :vote-count="feedback.vote_count"
        :user-vote="feedback.user_vote"
      />
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-bold text-gray-900">{{ feedback.title }}</h1>
          <Tag :value="feedback.status_label" :severity="statusSeverity(feedback.status)" />
        </div>
        <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
          <span>{{ feedback.type_label }}</span>
          <span v-if="feedback.category_label">{{ feedback.category_label }}</span>
          <span>{{ formatDate(feedback.created_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="mt-6 text-gray-700">
      <p class="whitespace-pre-wrap">{{ feedback.description }}</p>
    </div>

    <!-- Comments -->
    <div class="mt-8">
      <h2 class="mb-4 text-lg font-semibold text-gray-900">
        Comments ({{ comments.length }})
      </h2>

      <div class="space-y-4">
        <div
          v-for="comment in comments"
          :key="comment.id"
          class="rounded-lg border p-4"
          :class="comment.is_official_response ? 'border-primary-200 bg-primary-50' : 'border-gray-200'"
        >
          <div class="flex items-center gap-2 text-sm">
            <span class="font-medium text-gray-900">{{ comment.author_name }}</span>
            <Tag v-if="comment.is_official_response" value="Official" severity="info" class="!text-xs" />
            <span class="text-gray-500">{{ formatDate(comment.created_at) }}</span>
          </div>
          <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ comment.content }}</p>
        </div>
      </div>

      <!-- Add Comment -->
      <div class="mt-6">
        <Textarea
          v-model="newComment"
          placeholder="Add a comment..."
          rows="3"
          class="w-full"
        />
        <Button
          label="Post Comment"
          icon="pi pi-send"
          size="small"
          class="mt-2"
          :loading="submitting"
          :disabled="!newComment.trim()"
          @click="addComment"
        />
      </div>
    </div>
  </div>
</template>
