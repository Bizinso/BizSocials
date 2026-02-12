<script setup lang="ts">
import { computed } from 'vue'
import { getPlatformConfig } from '@/utils/platform-config'
import type { SocialPlatform } from '@/types/enums'

const props = defineProps<{
  platform: SocialPlatform | string
  size?: 'sm' | 'md' | 'lg'
  showLabel?: boolean
}>()

const config = computed(() => getPlatformConfig(props.platform as SocialPlatform))

const sizeClasses: Record<string, string> = {
  sm: 'h-5 w-5 text-xs',
  md: 'h-7 w-7 text-sm',
  lg: 'h-9 w-9 text-base',
}
</script>

<template>
  <div class="inline-flex items-center gap-1.5">
    <div
      class="flex shrink-0 items-center justify-center rounded-full text-white"
      :class="sizeClasses[size || 'md']"
      :style="{ backgroundColor: config.color }"
    >
      <i :class="config.icon" />
    </div>
    <span v-if="showLabel" class="text-sm font-medium text-gray-700">{{ config.label }}</span>
  </div>
</template>
