<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { KBArticleSummaryData } from '@/types/kb'
import Tag from 'primevue/tag'

defineProps<{
  articles: KBArticleSummaryData[]
}>()

const router = useRouter()

function openArticle(article: KBArticleSummaryData) {
  router.push({ name: 'kb-article', params: { slug: article.slug } })
}

function typeSeverity(type: string) {
  switch (type) {
    case 'how_to': return 'info'
    case 'troubleshooting': return 'warn'
    case 'tutorial': return 'success'
    case 'faq': return 'secondary'
    default: return 'info'
  }
}

function typeLabel(type: string) {
  return type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="article in articles"
      :key="article.id"
      class="cursor-pointer rounded-lg border border-gray-200 p-4 transition-colors hover:border-primary-300 hover:bg-gray-50"
      @click="openArticle(article)"
    >
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <h3 class="text-base font-semibold text-gray-900">{{ article.title }}</h3>
          <p v-if="article.excerpt" class="mt-1 text-sm text-gray-600 line-clamp-2">
            {{ article.excerpt }}
          </p>
          <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
            <span>{{ article.category_name }}</span>
            <Tag :value="typeLabel(article.article_type)" :severity="typeSeverity(article.article_type)" class="!text-xs" />
            <span v-if="article.view_count > 0">
              <i class="pi pi-eye mr-1" />{{ article.view_count }} views
            </span>
          </div>
        </div>
        <i v-if="article.is_featured" class="pi pi-star-fill text-yellow-400" />
      </div>
    </div>

    <p v-if="articles.length === 0" class="py-8 text-center text-sm text-gray-500">
      No articles found.
    </p>
  </div>
</template>
