<script setup lang="ts">
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import type { PlatformMetricsData } from '@/types/analytics'
import { getPlatformColor } from '@/utils/platform-config'
import { formatNumber } from '@/utils/formatters'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps<{
  platforms: PlatformMetricsData[]
}>()

const chartData = computed(() => ({
  labels: props.platforms.map((p) => p.platform_label),
  datasets: [
    {
      data: props.platforms.map((p) => p.engagements),
      backgroundColor: props.platforms.map((p) => getPlatformColor(p.platform)),
      borderWidth: 2,
      borderColor: '#fff',
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right' as const,
      labels: { font: { size: 12 }, padding: 16 },
    },
  },
  cutout: '65%',
}
</script>

<template>
  <div>
    <div v-if="platforms.length > 0" class="h-64">
      <Doughnut :data="chartData" :options="chartOptions" />
    </div>
    <p v-else class="py-8 text-center text-sm text-gray-400">No platform data available</p>

    <div class="mt-4 space-y-2">
      <div
        v-for="platform in platforms"
        :key="platform.platform"
        class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"
      >
        <div class="flex items-center gap-2">
          <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: getPlatformColor(platform.platform) }" />
          <span class="font-medium text-gray-700">{{ platform.platform_label }}</span>
        </div>
        <div class="flex items-center gap-4 text-gray-500">
          <span>{{ formatNumber(platform.engagements) }} engagements</span>
          <span>{{ platform.engagement_rate.toFixed(1) }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>
