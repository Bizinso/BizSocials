<script setup lang="ts">
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
} from 'chart.js'

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps<{
  signupsByMonth: Record<string, number>
}>()

const chartData = computed(() => ({
  labels: Object.keys(props.signupsByMonth),
  datasets: [
    {
      label: 'Signups',
      data: Object.values(props.signupsByMonth),
      backgroundColor: 'rgba(99, 102, 241, 0.6)',
      borderRadius: 4,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1 } },
  },
}
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <h3 class="mb-4 text-sm font-semibold text-gray-700">Signups by Month</h3>
    <div style="height: 240px">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
