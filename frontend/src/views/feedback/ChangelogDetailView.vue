<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import ChangelogEntry from '@/components/feedback/ChangelogEntry.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { feedbackApi } from '@/api/feedback'
import type { ReleaseNoteData } from '@/types/feedback'

const route = useRoute()
const release = ref<ReleaseNoteData | null>(null)
const loading = ref(true)

async function load(slug: string) {
  loading.value = true
  try {
    release.value = await feedbackApi.getChangelogEntry(slug)
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.slug as string,
  (slug) => { if (slug) load(slug) },
  { immediate: true },
)
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <router-link :to="{ name: 'changelog-list' }" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600">
        <i class="pi pi-arrow-left text-xs" /> Back to Changelog
      </router-link>

      <AppLoadingSkeleton v-if="loading" :lines="10" />
      <ChangelogEntry v-else-if="release" :release="release" show-content />
    </div>
  </PublicLayout>
</template>
