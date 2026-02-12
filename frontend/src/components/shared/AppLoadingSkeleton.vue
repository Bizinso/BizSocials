<script setup lang="ts">
import Skeleton from 'primevue/skeleton'

defineProps<{
  lines?: number
  hasAvatar?: boolean
}>()
</script>

<template>
  <div class="space-y-4">
    <div v-if="hasAvatar" class="flex items-center gap-3">
      <Skeleton shape="circle" size="2.5rem" />
      <div class="flex-1 space-y-2">
        <Skeleton width="60%" height="0.75rem" />
        <Skeleton width="40%" height="0.75rem" />
      </div>
    </div>
    <template v-else>
      <Skeleton v-for="i in (lines || 3)" :key="i" :width="i === (lines || 3) ? '60%' : '100%'" height="0.75rem" />
    </template>
  </div>
</template>
