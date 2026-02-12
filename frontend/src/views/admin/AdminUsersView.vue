<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AdminUserTable from '@/components/admin/AdminUserTable.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppSearchInput from '@/components/shared/AppSearchInput.vue'
import { adminUsersApi } from '@/api/admin'
import type { AdminUserData } from '@/types/admin'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'

const users = ref<AdminUserData[]>([])
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

async function fetchUsers() {
  loading.value = true
  try {
    const result = await adminUsersApi.list({
      page: currentPage.value,
      search: search.value || undefined,
      status: statusFilter.value || undefined,
    })
    users.value = result.data
    totalRecords.value = result.meta.total
  } finally {
    loading.value = false
  }
}

onMounted(fetchUsers)

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchUsers()
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Manage Users</h1>

    <div class="mb-4 flex items-center gap-3">
      <AppSearchInput :model-value="search" placeholder="Search users..." class="flex-1" @update:model-value="search = $event; fetchUsers()" />
      <Select v-model="statusFilter" :options="statuses" option-label="label" option-value="value" class="w-36" @change="fetchUsers" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else>
      <AdminUserTable :users="users" />
      <Paginator v-if="totalRecords > 15" :rows="15" :total-records="totalRecords" :first="(currentPage - 1) * 15" class="mt-4" @page="onPage" />
    </template>
  </div>
</template>
