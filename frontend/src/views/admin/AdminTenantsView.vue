<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AdminTenantTable from '@/components/admin/AdminTenantTable.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppSearchInput from '@/components/shared/AppSearchInput.vue'
import { adminTenantsApi } from '@/api/admin'
import type { AdminTenantData } from '@/types/admin'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'

const tenants = ref<AdminTenantData[]>([])
const loading = ref(true)
const totalRecords = ref(0)
const currentPage = ref(1)
const search = ref('')
const statusFilter = ref('')

const statuses = [
  { label: 'All', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Suspended', value: 'suspended' },
]

async function fetchTenants() {
  loading.value = true
  try {
    const result = await adminTenantsApi.list({
      page: currentPage.value,
      search: search.value || undefined,
      status: statusFilter.value || undefined,
    })
    tenants.value = result.data
    totalRecords.value = result.meta.total
  } finally {
    loading.value = false
  }
}

onMounted(fetchTenants)

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchTenants()
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Manage Tenants</h1>

    <div class="mb-4 flex items-center gap-3">
      <AppSearchInput :model-value="search" placeholder="Search tenants..." class="flex-1" @update:model-value="search = $event; fetchTenants()" />
      <Select v-model="statusFilter" :options="statuses" option-label="label" option-value="value" class="w-36" @change="fetchTenants" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else>
      <AdminTenantTable :tenants="tenants" />
      <Paginator v-if="totalRecords > 15" :rows="15" :total-records="totalRecords" :first="(currentPage - 1) * 15" class="mt-4" @page="onPage" />
    </template>
  </div>
</template>
