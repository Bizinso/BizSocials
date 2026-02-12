<script setup lang="ts">
import { computed } from 'vue'
import type { BestTimesData } from '@/types/analytics'

const props = defineProps<{
  data: BestTimesData[]
}>()

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const hours = Array.from({ length: 24 }, (_, i) => i)

const maxRate = computed(() => {
  if (props.data.length === 0) return 1
  return Math.max(...props.data.map((d) => d.engagement_rate))
})

function getCellData(day: string, hour: number) {
  return props.data.find((d) => d.day === day && d.hour === hour)
}

function cellColor(rate: number): string {
  if (rate === 0) return 'bg-gray-100'
  const intensity = Math.min(rate / maxRate.value, 1)
  if (intensity < 0.25) return 'bg-green-100'
  if (intensity < 0.5) return 'bg-green-200'
  if (intensity < 0.75) return 'bg-green-400'
  return 'bg-green-600'
}

function formatHour(h: number): string {
  if (h === 0) return '12a'
  if (h < 12) return `${h}a`
  if (h === 12) return '12p'
  return `${h - 12}p`
}
</script>

<template>
  <div v-if="data.length > 0" class="overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr>
          <th class="w-10" />
          <th
            v-for="h in hours"
            :key="h"
            class="px-0.5 py-1 text-center font-normal text-gray-400"
          >
            {{ h % 3 === 0 ? formatHour(h) : '' }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="day in days" :key="day">
          <td class="pr-2 text-right font-medium text-gray-500">{{ day }}</td>
          <td
            v-for="h in hours"
            :key="h"
            class="p-0.5"
          >
            <div
              class="h-5 w-full rounded-sm"
              :class="cellColor(getCellData(day, h)?.engagement_rate ?? 0)"
              :title="`${day} ${formatHour(h)}: ${(getCellData(day, h)?.engagement_rate ?? 0).toFixed(1)}% (${getCellData(day, h)?.post_count ?? 0} posts)`"
            />
          </td>
        </tr>
      </tbody>
    </table>
    <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
      <span>Less</span>
      <span class="h-3 w-3 rounded-sm bg-gray-100" />
      <span class="h-3 w-3 rounded-sm bg-green-100" />
      <span class="h-3 w-3 rounded-sm bg-green-200" />
      <span class="h-3 w-3 rounded-sm bg-green-400" />
      <span class="h-3 w-3 rounded-sm bg-green-600" />
      <span>More</span>
    </div>
  </div>
  <p v-else class="py-6 text-center text-sm text-gray-400">No best times data available yet</p>
</template>
