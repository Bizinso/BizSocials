<script setup lang="ts">
import type { AdminPlanData } from '@/types/admin'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

defineProps<{
  plans: AdminPlanData[]
}>()

const emit = defineEmits<{
  edit: [plan: AdminPlanData]
  remove: [plan: AdminPlanData]
}>()
</script>

<template>
  <DataTable :value="plans" striped-rows>
    <Column field="name" header="Plan">
      <template #body="{ data }">
        <div>
          <span class="font-medium text-gray-900">{{ data.name }}</span>
          <span class="ml-2 font-mono text-xs text-gray-400">{{ data.code }}</span>
        </div>
      </template>
    </Column>
    <Column header="Monthly Price" style="width: 140px">
      <template #body="{ data }">
        <div class="text-sm">
          <div>&#8377;{{ data.price_inr_monthly }}</div>
          <div class="text-gray-400">${{ data.price_usd_monthly }}</div>
        </div>
      </template>
    </Column>
    <Column header="Yearly Price" style="width: 140px">
      <template #body="{ data }">
        <div class="text-sm">
          <div>&#8377;{{ data.price_inr_yearly }}</div>
          <div class="text-gray-400">${{ data.price_usd_yearly }}</div>
        </div>
      </template>
    </Column>
    <Column field="subscription_count" header="Subs" style="width: 80px" />
    <Column field="is_active" header="Status" style="width: 80px">
      <template #body="{ data }">
        <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" class="!text-xs" />
      </template>
    </Column>
    <Column header="Actions" style="width: 120px">
      <template #body="{ data }">
        <div class="flex gap-1">
          <Button icon="pi pi-pencil" severity="secondary" text size="small" @click="emit('edit', data)" />
          <Button icon="pi pi-trash" severity="danger" text size="small" @click="emit('remove', data)" />
        </div>
      </template>
    </Column>
  </DataTable>
</template>
