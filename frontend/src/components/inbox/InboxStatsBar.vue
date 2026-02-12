<script setup lang="ts">
import type { InboxStatsData } from '@/types/inbox'

defineProps<{
  stats: InboxStatsData | null
}>()
</script>

<template>
  <div v-if="stats" class="flex flex-wrap gap-4 rounded-lg bg-gray-50 p-3 text-sm">
    <div class="flex items-center gap-1.5">
      <span class="font-semibold text-gray-900">{{ stats.total }}</span>
      <span class="text-gray-500">Total</span>
    </div>
    <div class="flex items-center gap-1.5">
      <span class="font-semibold text-blue-600">{{ stats.unread }}</span>
      <span class="text-gray-500">Unread</span>
    </div>
    <div class="flex items-center gap-1.5">
      <span class="font-semibold text-green-600">{{ stats.resolved }}</span>
      <span class="text-gray-500">Resolved</span>
    </div>
    <div class="flex items-center gap-1.5">
      <span class="font-semibold text-orange-600">{{ stats.assigned_to_me }}</span>
      <span class="text-gray-500">Assigned to me</span>
    </div>
  </div>
</template>
