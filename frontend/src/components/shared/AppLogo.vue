<script setup lang="ts">
defineProps<{
  iconOnly?: boolean
}>()
</script>

<template>
  <div class="flex items-center gap-2">
    <svg viewBox="0 0 32 32" class="h-8 w-8 shrink-0" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect width="32" height="32" rx="8" class="fill-primary-600" />
      <path d="M8 16c0-4.418 3.582-8 8-8s8 3.582 8 8-3.582 8-8 8" stroke="white" stroke-width="2.5" stroke-linecap="round" />
      <circle cx="16" cy="16" r="3" fill="white" />
    </svg>
    <span v-if="!iconOnly" class="text-lg font-bold text-gray-900">BizSocials</span>
  </div>
</template>
