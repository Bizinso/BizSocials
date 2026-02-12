<script setup lang="ts">
defineProps<{
  title?: string
  padding?: boolean
}>()
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
    <div v-if="title || $slots.header" class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
      <h3 v-if="title" class="text-base font-semibold text-gray-900">{{ title }}</h3>
      <slot name="header" />
    </div>
    <div :class="padding !== false ? 'p-6' : ''">
      <slot />
    </div>
  </div>
</template>
