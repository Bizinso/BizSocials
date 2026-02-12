<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import KBSearchBar from '@/components/kb/KBSearchBar.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import { kbApi } from '@/api/kb'
import type { KBSearchResultData } from '@/types/kb'

const route = useRoute()
const results = ref<KBSearchResultData[]>([])
const loading = ref(false)

async function performSearch(q: string) {
  if (!q) return
  loading.value = true
  try {
    results.value = await kbApi.search(q)
  } finally {
    loading.value = false
  }
}

watch(
  () => route.query.q as string,
  (q) => {
    if (q) performSearch(q)
  },
  { immediate: true },
)
</script>

<template>
  <PublicLayout>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 class="mb-6 text-2xl font-bold text-gray-900">Search Knowledge Base</h1>
      <KBSearchBar />

      <div class="mt-8">
        <AppLoadingSkeleton v-if="loading" :lines="6" />

        <template v-else-if="results.length > 0">
          <p class="mb-4 text-sm text-gray-500">{{ results.length }} result(s) found</p>
          <div class="space-y-4">
            <router-link
              v-for="result in results"
              :key="result.id"
              :to="{ name: 'kb-article', params: { slug: result.slug } }"
              class="block rounded-lg border border-gray-200 p-4 transition-colors hover:border-primary-300 hover:bg-gray-50"
            >
              <h3 class="font-semibold text-gray-900">{{ result.title }}</h3>
              <p v-if="result.excerpt" class="mt-1 text-sm text-gray-600 line-clamp-2">
                {{ result.excerpt }}
              </p>
              <span class="mt-2 inline-block text-xs text-gray-400">{{ result.category_name }}</span>
            </router-link>
          </div>
        </template>

        <AppEmptyState
          v-else-if="route.query.q"
          icon="pi pi-search"
          title="No results found"
          description="Try different keywords or browse categories."
        />
      </div>
    </div>
  </PublicLayout>
</template>
