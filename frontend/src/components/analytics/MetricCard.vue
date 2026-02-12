<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/utils/formatters'

const props = defineProps<{
  label: string
  value: number
  change?: number | null
  icon?: string
  format?: 'number' | 'percent' | 'rate'
}>()

const displayValue = computed(() => {
  if (props.format === 'percent' || props.format === 'rate') {
    return `${props.value.toFixed(1)}%`
  }
  return formatNumber(props.value)
})

const changeText = computed(() => {
  if (props.change == null) return null
  const sign = props.change >= 0 ? '+' : ''
  return `${sign}${props.change.toFixed(1)}%`
})

const changeColor = computed(() => {
  if (props.change == null) return ''
  return props.change >= 0 ? 'text-green-600' : 'text-red-600'
})
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="mb-2 flex items-center justify-between">
      <span class="text-sm text-gray-500">{{ label }}</span>
      <i v-if="icon" :class="icon" class="text-gray-400" />
    </div>
    <div class="flex items-end gap-2">
      <span class="text-2xl font-bold text-gray-900">{{ displayValue }}</span>
      <span v-if="changeText" :class="changeColor" class="mb-0.5 text-sm font-medium">
        {{ changeText }}
      </span>
    </div>
  </div>
</template>
