<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import KBArticleContent from '@/components/kb/KBArticleContent.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { kbApi } from '@/api/kb'
import type { KBArticleData } from '@/types/kb'

const route = useRoute()
const article = ref<KBArticleData | null>(null)
const loading = ref(true)

async function loadArticle(slug: string) {
  loading.value = true
  try {
    article.value = await kbApi.getArticle(slug)
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.slug as string,
  (slug) => {
    if (slug) loadArticle(slug)
  },
  { immediate: true },
)
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <AppLoadingSkeleton v-if="loading" :lines="12" />
      <KBArticleContent v-else-if="article" :article="article" />
    </div>
  </PublicLayout>
</template>
