<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue'
import FeedbackSubmitForm from '@/components/feedback/FeedbackSubmitForm.vue'
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 class="mb-2 text-3xl font-bold text-gray-900">Submit Feedback</h1>
      <p class="mb-8 text-gray-600">Share your ideas, report bugs, or request features.</p>

      <div class="rounded-xl border border-gray-200 bg-white p-8">
        <FeedbackSubmitForm />
      </div>
    </div>
  </PublicLayout>
</template>
