<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AdminStatsGrid from '@/components/admin/AdminStatsGrid.vue'
import AdminSignupChart from '@/components/admin/AdminSignupChart.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminDashboardApi } from '@/api/admin'
import type { PlatformStatsData } from '@/types/admin'

const stats = ref<PlatformStatsData | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    stats.value = await adminDashboardApi.getStats()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Platform Dashboard</h1>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else-if="stats">
      <AdminStatsGrid :stats="stats" class="mb-8" />

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <AdminSignupChart :signups-by-month="stats.signups_by_month" />

        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <h3 class="mb-4 text-sm font-semibold text-gray-700">Tenants by Plan</h3>
          <div class="space-y-2">
            <div v-for="(count, plan) in stats.tenants_by_plan" :key="plan" class="flex items-center justify-between">
              <span class="text-sm text-gray-700 capitalize">{{ plan }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
