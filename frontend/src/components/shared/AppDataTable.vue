<script setup lang="ts">
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import type { PaginationMeta } from '@/types/api'

defineProps<{
  value: unknown[]
  loading?: boolean
  pagination?: PaginationMeta | null
  paginator?: boolean
  rows?: number
}>()

const emit = defineEmits<{
  page: [event: { page: number; rows: number }]
}>()

function onPage(event: { page: number; rows: number }) {
  emit('page', { page: event.page + 1, rows: event.rows })
}
</script>

<template>
  <DataTable
    :value="value"
    :loading="loading"
    :paginator="paginator !== false && !!pagination"
    :rows="rows || pagination?.per_page || 15"
    :total-records="pagination?.total"
    :lazy="!!pagination"
    striped-rows
    removable-sort
    class="text-sm"
    @page="onPage"
  >
    <slot />
    <template #empty>
      <div class="py-8 text-center text-gray-500">No records found.</div>
    </template>
  </DataTable>
</template>
