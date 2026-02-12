<script setup lang="ts">
import { computed } from 'vue'
import type { HealthStatusData } from '@/types/social'

const props = defineProps<{
  health: HealthStatusData | null
}>()

const healthPercent = computed(() => {
  if (!props.health || props.health.total_accounts === 0) return 0
  return Math.round((props.health.connected_count / props.health.total_accounts) * 100)
})

const healthColor = computed(() => {
  if (healthPercent.value === 100) return 'text-green-600'
  if (healthPercent.value >= 75) return 'text-yellow-600'
  return 'text-red-600'
})
</script>

<template>
  <div v-if="health" class="flex items-center gap-2">
    <span :class="healthColor" class="text-sm font-semibold">{{ healthPercent }}%</span>
    <span class="text-xs text-gray-500">
      {{ health.connected_count }}/{{ health.total_accounts }} healthy
    </span>
    <span v-if="health.expired_count > 0" class="text-xs text-yellow-600">
      ({{ health.expired_count }} expired)
    </span>
  </div>
</template>
