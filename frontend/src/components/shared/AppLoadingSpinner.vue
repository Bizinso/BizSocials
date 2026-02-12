<script setup lang="ts">
defineProps<{
  size?: 'sm' | 'md' | 'lg'
  label?: string
}>()

const sizeClasses: Record<string, string> = {
  sm: 'h-5 w-5',
  md: 'h-8 w-8',
  lg: 'h-12 w-12',
}
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3">
    <ProgressSpinner
      :style="{ width: size === 'sm' ? '20px' : size === 'lg' ? '48px' : '32px', height: size === 'sm' ? '20px' : size === 'lg' ? '48px' : '32px' }"
      strokeWidth="4"
    />
    <span v-if="label" class="text-sm text-gray-500">{{ label }}</span>
  </div>
</template>

<script lang="ts">
import ProgressSpinner from 'primevue/progressspinner'
</script>
