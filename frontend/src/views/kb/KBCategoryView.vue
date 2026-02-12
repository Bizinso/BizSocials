<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import KBSearchBar from '@/components/kb/KBSearchBar.vue'
import KBCategoryTree from '@/components/kb/KBCategoryTree.vue'
import KBArticleList from '@/components/kb/KBArticleList.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { kbApi } from '@/api/kb'
import type { KBCategoryData, KBArticleSummaryData } from '@/types/kb'

const route = useRoute()
const category = ref<KBCategoryData | null>(null)
const categories = ref<KBCategoryData[]>([])
const articles = ref<KBArticleSummaryData[]>([])
const loading = ref(true)

async function loadCategory(slug: string) {
  loading.value = true
  try {
    const [cat, cats, articlePage] = await Promise.all([
      kbApi.getCategory(slug),
      kbApi.listCategories(),
      kbApi.listArticles({ category: slug }),
    ])
    category.value = cat
    categories.value = cats
    articles.value = articlePage.data
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.slug as string,
  (slug) => {
    if (slug) loadCategory(slug)
  },
  { immediate: true },
)
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-8">
        <KBSearchBar />
      </div>

      <AppLoadingSkeleton v-if="loading" :lines="8" />

      <div v-else class="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <div>
          <h2 class="mb-4 text-lg font-semibold text-gray-900">Categories</h2>
          <KBCategoryTree :categories="categories" :active-slug="route.params.slug as string" />
        </div>

        <div class="lg:col-span-3">
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ category?.name }}</h1>
            <p v-if="category?.description" class="mt-1 text-gray-600">{{ category.description }}</p>
          </div>
          <KBArticleList :articles="articles" />
        </div>
      </div>
    </div>
  </PublicLayout>
</template>
