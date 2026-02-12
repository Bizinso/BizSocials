<script setup lang="ts">
import { ref } from 'vue'
import { formatDate } from '@/utils/formatters'
import { kbApi } from '@/api/kb'
import { useToast } from '@/composables/useToast'
import type { KBArticleData } from '@/types/kb'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'

const props = defineProps<{
  article: KBArticleData
}>()

const toast = useToast()
const feedbackSubmitted = ref(false)
const showFeedbackForm = ref(false)
const feedbackComment = ref('')

async function submitFeedback(isHelpful: boolean) {
  try {
    await kbApi.submitFeedback(props.article.id, {
      is_helpful: isHelpful,
      comment: feedbackComment.value || undefined,
    })
    feedbackSubmitted.value = true
    toast.success('Thank you for your feedback!')
  } catch {
    toast.error('Failed to submit feedback')
  }
}
</script>

<template>
  <article>
    <!-- Header -->
    <header class="mb-6">
      <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
        <router-link :to="{ name: 'kb-home' }" class="hover:text-primary-600">Knowledge Base</router-link>
        <i class="pi pi-angle-right text-xs" />
        <span>{{ article.category_name }}</span>
      </div>
      <h1 class="text-3xl font-bold text-gray-900">{{ article.title }}</h1>
      <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-500">
        <span v-if="article.published_at">Published {{ formatDate(article.published_at) }}</span>
        <span><i class="pi pi-eye mr-1" />{{ article.view_count }} views</span>
        <Tag
          v-for="tag in article.tags"
          :key="tag.id"
          :value="tag.name"
          severity="secondary"
          class="!text-xs"
        />
      </div>
    </header>

    <!-- Content -->
    <div class="prose prose-gray max-w-none" v-html="article.content" />

    <!-- Feedback -->
    <div class="mt-10 rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
      <template v-if="!feedbackSubmitted">
        <p class="mb-3 text-sm font-medium text-gray-700">Was this article helpful?</p>
        <div class="flex items-center justify-center gap-3">
          <Button
            label="Yes"
            icon="pi pi-thumbs-up"
            severity="success"
            outlined
            size="small"
            @click="submitFeedback(true)"
          />
          <Button
            label="No"
            icon="pi pi-thumbs-down"
            severity="danger"
            outlined
            size="small"
            @click="showFeedbackForm = true"
          />
        </div>
        <div v-if="showFeedbackForm" class="mx-auto mt-4 max-w-md">
          <Textarea
            v-model="feedbackComment"
            placeholder="How can we improve this article?"
            rows="3"
            class="w-full"
          />
          <Button
            label="Submit Feedback"
            size="small"
            class="mt-2"
            @click="submitFeedback(false)"
          />
        </div>
      </template>
      <p v-else class="text-sm text-gray-600">
        <i class="pi pi-check-circle mr-1 text-green-500" />
        Thank you for your feedback!
      </p>
    </div>
  </article>
</template>
