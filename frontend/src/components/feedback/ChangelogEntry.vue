<script setup lang="ts">
import { formatDate } from '@/utils/formatters'
import type { ReleaseNoteData } from '@/types/feedback'
import Tag from 'primevue/tag'

defineProps<{
  release: ReleaseNoteData
  showContent?: boolean
}>()

function typeSeverity(type: string) {
  switch (type) {
    case 'major': return 'danger'
    case 'minor': return 'info'
    case 'patch': return 'secondary'
    case 'hotfix': return 'warn'
    default: return 'secondary'
  }
}

function changeTypeIcon(type: string) {
  switch (type) {
    case 'feature': return 'pi pi-star'
    case 'improvement': return 'pi pi-arrow-up'
    case 'bug_fix': return 'pi pi-wrench'
    case 'breaking_change': return 'pi pi-exclamation-triangle'
    case 'deprecation': return 'pi pi-minus-circle'
    default: return 'pi pi-circle'
  }
}

function changeTypeColor(type: string) {
  switch (type) {
    case 'feature': return 'text-green-600'
    case 'improvement': return 'text-blue-600'
    case 'bug_fix': return 'text-orange-600'
    case 'breaking_change': return 'text-red-600'
    case 'deprecation': return 'text-gray-500'
    default: return 'text-gray-500'
  }
}
</script>

<template>
  <article class="rounded-lg border border-gray-200 bg-white p-6">
    <div class="flex items-center gap-3">
      <Tag :value="release.version" :severity="typeSeverity(release.release_type)" />
      <h2 class="text-xl font-bold text-gray-900">{{ release.title }}</h2>
    </div>

    <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
      <span v-if="release.published_at">{{ formatDate(release.published_at) }}</span>
      <span v-if="release.version_name" class="text-gray-400">{{ release.version_name }}</span>
    </div>

    <p v-if="release.summary" class="mt-3 text-gray-600">{{ release.summary }}</p>

    <!-- Items -->
    <div v-if="release.items && release.items.length > 0" class="mt-4 space-y-2">
      <div
        v-for="item in release.items"
        :key="item.id"
        class="flex items-start gap-2 text-sm"
      >
        <i :class="[changeTypeIcon(item.change_type), changeTypeColor(item.change_type)]" class="mt-0.5" />
        <div>
          <span class="font-medium text-gray-900">{{ item.title }}</span>
          <span v-if="item.description" class="ml-1 text-gray-500"> — {{ item.description }}</span>
        </div>
      </div>
    </div>

    <!-- Full content -->
    <div v-if="showContent && release.content" class="prose prose-sm mt-6 max-w-none" v-html="release.content" />
  </article>
</template>
