<script setup lang="ts">
defineProps<{
  icon?: string
  title: string
  description?: string
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="mb-4 rounded-full bg-gray-100 p-4">
      <i :class="icon || 'pi pi-inbox'" class="text-3xl text-gray-400" />
    </div>
    <h3 class="mb-1 text-base font-semibold text-gray-900">{{ title }}</h3>
    <p v-if="description" class="mb-6 max-w-sm text-sm text-gray-500">{{ description }}</p>
    <slot />
  </div>
</template>
