<script setup lang="ts">
import { computed } from 'vue'
import { initials } from '@/utils/formatters'

const props = defineProps<{
  name: string
  src?: string | null
  size?: 'xs' | 'sm' | 'md' | 'lg'
}>()

const sizeClasses: Record<string, string> = {
  xs: 'h-6 w-6 text-[10px]',
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-12 w-12 text-base',
}

const classes = computed(() => sizeClasses[props.size || 'md'])
const avatarInitials = computed(() => initials(props.name || '?'))
</script>

<template>
  <img
    v-if="src"
    :src="src"
    :alt="name"
    class="shrink-0 rounded-full object-cover"
    :class="classes"
  />
  <div
    v-else
    class="flex shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700"
    :class="classes"
  >
    {{ avatarInitials }}
  </div>
</template>
