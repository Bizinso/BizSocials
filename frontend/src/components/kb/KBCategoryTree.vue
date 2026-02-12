<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { KBCategoryData } from '@/types/kb'

defineProps<{
  categories: KBCategoryData[]
  activeSlug?: string
}>()

const router = useRouter()

function navigate(category: KBCategoryData) {
  router.push({ name: 'kb-category', params: { slug: category.slug } })
}
</script>

<template>
  <nav class="space-y-1">
    <button
      v-for="cat in categories"
      :key="cat.id"
      class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors"
      :class="[
        activeSlug === cat.slug
          ? 'bg-primary-50 text-primary-700'
          : 'text-gray-700 hover:bg-gray-100',
      ]"
      @click="navigate(cat)"
    >
      <div class="flex items-center gap-2">
        <i v-if="cat.icon" :class="cat.icon" class="text-sm" />
        <i v-else class="pi pi-folder text-sm" />
        <span>{{ cat.name }}</span>
      </div>
      <span class="text-xs text-gray-400">{{ cat.article_count }}</span>
    </button>
  </nav>
</template>
