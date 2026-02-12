<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { adminIntegrationsApi } from '@/api/admin'
import type { IntegrationListItem } from '@/types/admin'

const router = useRouter()
const integrations = ref<IntegrationListItem[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    integrations.value = await adminIntegrationsApi.list()
  } finally {
    loading.value = false
  }
})

function statusSeverity(status: string): 'success' | 'warn' | 'danger' | 'secondary' {
  switch (status) {
    case 'active':
      return 'success'
    case 'maintenance':
      return 'warn'
    case 'disabled':
      return 'danger'
    default:
      return 'secondary'
  }
}

function totalAccounts(stats: Record<string, { connected: number; expiring: number; expired: number; revoked: number }>): number {
  return Object.values(stats).reduce((sum, s) => sum + s.connected + s.expiring + s.expired + s.revoked, 0)
}

function viewDetail(integration: IntegrationListItem) {
  router.push({ name: 'admin-integration-detail', params: { provider: integration.provider } })
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Integrations</h1>

    <AppLoadingSkeleton v-if="loading" :lines="6" />

    <DataTable v-else :value="integrations" responsive-layout="scroll" class="rounded-lg border border-gray-200">
      <Column field="display_name" header="Provider" sortable>
        <template #body="{ data }">
          <div class="flex items-center gap-3">
            <i class="pi pi-link text-lg text-gray-500" />
            <div>
              <div class="font-medium text-gray-900">{{ data.display_name }}</div>
              <div class="text-xs text-gray-500">
                {{ data.platforms.map((p: string) => p.charAt(0).toUpperCase() + p.slice(1)).join(', ') }}
              </div>
            </div>
          </div>
        </template>
      </Column>

      <Column field="status" header="Status" sortable>
        <template #body="{ data }">
          <Tag :value="data.status" :severity="statusSeverity(data.status)" />
        </template>
      </Column>

      <Column field="api_version" header="API Version" sortable />

      <Column header="Credentials">
        <template #body="{ data }">
          <Tag :value="data.has_credentials ? 'Configured' : 'Missing'" :severity="data.has_credentials ? 'success' : 'danger'" />
        </template>
      </Column>

      <Column header="Accounts">
        <template #body="{ data }">
          <span class="font-medium">{{ totalAccounts(data.account_stats) }}</span>
          <span class="ml-1 text-xs text-gray-500">total</span>
        </template>
      </Column>

      <Column header="Last Verified">
        <template #body="{ data }">
          <span v-if="data.last_verified_at" class="text-sm text-gray-600">
            {{ new Date(data.last_verified_at).toLocaleDateString() }}
          </span>
          <span v-else class="text-sm text-gray-400">Never</span>
        </template>
      </Column>

      <Column header="">
        <template #body="{ data }">
          <Button label="Manage" icon="pi pi-cog" severity="secondary" size="small" @click="viewDetail(data)" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>
