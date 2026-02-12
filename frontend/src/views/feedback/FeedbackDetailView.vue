<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import FeedbackDetail from '@/components/feedback/FeedbackDetail.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { feedbackApi } from '@/api/feedback'
import type { FeedbackData } from '@/types/feedback'

const route = useRoute()
const feedback = ref<FeedbackData | null>(null)
const loading = ref(true)

async function load(id: string) {
  loading.value = true
  try {
    feedback.value = await feedbackApi.get(id)
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.feedbackId as string,
  (id) => { if (id) load(id) },
  { immediate: true },
)
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <router-link :to="{ name: 'feedback-list' }" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600">
        <i class="pi pi-arrow-left text-xs" /> Back to Feedback
      </router-link>

      <AppLoadingSkeleton v-if="loading" :lines="10" />
      <FeedbackDetail v-else-if="feedback" :feedback="feedback" />
    </div>
  </PublicLayout>
</template>
