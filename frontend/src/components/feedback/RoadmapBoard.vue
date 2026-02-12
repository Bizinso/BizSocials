<script setup lang="ts">
import { computed } from 'vue'
import type { RoadmapItemData } from '@/types/feedback'
import RoadmapItemCard from './RoadmapItemCard.vue'

const props = defineProps<{
  items: RoadmapItemData[]
}>()

const columns = computed(() => [
  { key: 'planned', label: 'Planned', items: props.items.filter((i) => i.status === 'planned') },
  { key: 'in_progress', label: 'In Progress', items: props.items.filter((i) => i.status === 'in_progress') },
  { key: 'beta', label: 'Beta', items: props.items.filter((i) => i.status === 'beta') },
  { key: 'shipped', label: 'Shipped', items: props.items.filter((i) => i.status === 'shipped') },
])
</script>

<template>
  <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
    <div v-for="col in columns" :key="col.key">
      <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">
        {{ col.label }}
        <span class="ml-1 text-gray-400">({{ col.items.length }})</span>
      </h3>
      <div class="space-y-3">
        <RoadmapItemCard v-for="item in col.items" :key="item.id" :item="item" />
        <p v-if="col.items.length === 0" class="text-center text-sm text-gray-400 py-4">
          No items
        </p>
      </div>
    </div>
  </div>
</template>
