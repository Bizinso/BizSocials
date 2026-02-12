import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tenantApi } from '@/api/tenant'
import type { TenantData, TenantStatsData } from '@/types/tenant'

export const useTenantStore = defineStore('tenant', () => {
  const tenant = ref<TenantData | null>(null)
  const stats = ref<TenantStatsData | null>(null)
  const loading = ref(false)

  async function fetchTenant() {
    loading.value = true
    try {
      tenant.value = await tenantApi.getCurrent()
    } finally {
      loading.value = false
    }
  }

  async function fetchStats() {
    stats.value = await tenantApi.getStats()
  }

  function clear() {
    tenant.value = null
    stats.value = null
  }

  return {
    tenant,
    stats,
    loading,
    fetchTenant,
    fetchStats,
    clear,
  }
})
