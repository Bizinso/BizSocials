<script setup lang="ts">
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import type { ContentPerformanceData } from '@/types/analytics'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{
  data: ContentPerformanceData[]
}>()

const chartData = computed(() => ({
  labels: props.data.map((d) => d.content_type_label),
  datasets: [
    {
      label: 'Avg Engagements',
      data: props.data.map((d) => d.avg_engagements),
      backgroundColor: '#6366f1',
      borderRadius: 4,
    },
    {
      label: 'Avg Impressions',
      data: props.data.map((d) => d.avg_impressions),
      backgroundColor: '#a5b4fc',
      borderRadius: 4,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: { font: { size: 12 } },
    },
  },
  scales: {
    x: { grid: { display: false } },
    y: { beginAtZero: true },
  },
}
</script>

<template>
  <div>
    <div v-if="data.length > 0" class="h-64">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
    <p v-else class="py-8 text-center text-sm text-gray-400">No content type data available</p>
  </div>
</template>
