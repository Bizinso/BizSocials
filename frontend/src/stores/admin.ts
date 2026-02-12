import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminDashboardApi } from '@/api/admin'
import type { PlatformStatsData } from '@/types/admin'

export const useAdminStore = defineStore('admin', () => {
  const stats = ref<PlatformStatsData | null>(null)
  const loading = ref(false)

  async function fetchStats() {
    loading.value = true
    try {
      stats.value = await adminDashboardApi.getStats()
    } finally {
      loading.value = false
    }
  }

  return {
    stats,
    loading,
    fetchStats,
  }
})
