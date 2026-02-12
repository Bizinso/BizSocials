<script setup lang="ts">
import { ref, onMounted } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import KBSearchBar from '@/components/kb/KBSearchBar.vue'
import KBCategoryTree from '@/components/kb/KBCategoryTree.vue'
import KBArticleList from '@/components/kb/KBArticleList.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { kbApi } from '@/api/kb'
import type { KBCategoryData, KBArticleSummaryData } from '@/types/kb'

const categories = ref<KBCategoryData[]>([])
const featured = ref<KBArticleSummaryData[]>([])
const popular = ref<KBArticleSummaryData[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const [cats, feat, pop] = await Promise.all([
      kbApi.listCategories(),
      kbApi.getFeaturedArticles(),
      kbApi.getPopularArticles(),
    ])
    categories.value = cats
    featured.value = feat
    popular.value = pop
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <!-- Hero -->
      <div class="mb-12 text-center">
        <h1 class="text-4xl font-bold text-gray-900">Knowledge Base</h1>
        <p class="mt-3 text-lg text-gray-600">Find answers, guides, and tutorials</p>
        <div class="mx-auto mt-6 max-w-xl">
          <KBSearchBar />
        </div>
      </div>

      <AppLoadingSkeleton v-if="loading" :lines="8" />

      <template v-else>
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
          <!-- Categories Sidebar -->
          <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Categories</h2>
            <KBCategoryTree :categories="categories" />
          </div>

          <!-- Articles -->
          <div class="lg:col-span-3">
            <!-- Featured -->
            <section v-if="featured.length > 0" class="mb-10">
              <h2 class="mb-4 text-lg font-semibold text-gray-900">Featured Articles</h2>
              <KBArticleList :articles="featured" />
            </section>

            <!-- Popular -->
            <section v-if="popular.length > 0">
              <h2 class="mb-4 text-lg font-semibold text-gray-900">Popular Articles</h2>
              <KBArticleList :articles="popular" />
            </section>
          </div>
        </div>
      </template>
    </div>
  </PublicLayout>
</template>
