import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { socialApi } from '@/api/social'
import type { SocialAccountData, HealthStatusData } from '@/types/social'
import type { SocialPlatform } from '@/types/enums'

export const useSocialStore = defineStore('social', () => {
  const accounts = ref<SocialAccountData[]>([])
  const health = ref<HealthStatusData | null>(null)
  const loading = ref(false)

  const connectedAccounts = computed(() =>
    accounts.value.filter((a) => a.status === 'connected'),
  )

  const accountsByPlatform = computed(() => {
    const map: Record<string, SocialAccountData[]> = {}
    for (const account of accounts.value) {
      if (!map[account.platform]) map[account.platform] = []
      map[account.platform].push(account)
    }
    return map
  })

  async function fetchAccounts(workspaceId: string) {
    loading.value = true
    try {
      accounts.value = await socialApi.list(workspaceId)
    } finally {
      loading.value = false
    }
  }

  async function fetchHealth(workspaceId: string) {
    health.value = await socialApi.health(workspaceId)
  }

  function addAccount(account: SocialAccountData) {
    accounts.value.push(account)
  }

  function removeAccount(id: string) {
    accounts.value = accounts.value.filter((a) => a.id !== id)
  }

  function clear() {
    accounts.value = []
    health.value = null
  }

  return {
    accounts,
    health,
    loading,
    connectedAccounts,
    accountsByPlatform,
    fetchAccounts,
    fetchHealth,
    addAccount,
    removeAccount,
    clear,
  }
})
