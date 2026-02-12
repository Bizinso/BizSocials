<script setup lang="ts">
import { ref, onMounted } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import RoadmapBoard from '@/components/feedback/RoadmapBoard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { feedbackApi } from '@/api/feedback'
import type { RoadmapItemData } from '@/types/feedback'

const items = ref<RoadmapItemData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    items.value = await feedbackApi.listRoadmap()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">Product Roadmap</h1>
        <p class="mt-2 text-gray-600">See what we're building and what's coming next</p>
      </div>

      <AppLoadingSkeleton v-if="loading" :lines="8" />
      <RoadmapBoard v-else :items="items" />
    </div>
  </PublicLayout>
</template>
