<script setup lang="ts">
import type { FeatureFlagData } from '@/types/admin'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputSwitch from 'primevue/inputswitch'

defineProps<{
  flags: FeatureFlagData[]
}>()

const emit = defineEmits<{
  toggle: [flag: FeatureFlagData]
  edit: [flag: FeatureFlagData]
  remove: [flag: FeatureFlagData]
}>()
</script>

<template>
  <DataTable :value="flags" striped-rows>
    <Column field="name" header="Flag">
      <template #body="{ data }">
        <div>
          <span class="font-medium text-gray-900">{{ data.name }}</span>
          <p class="font-mono text-xs text-gray-400">{{ data.key }}</p>
        </div>
      </template>
    </Column>
    <Column field="description" header="Description">
      <template #body="{ data }">
        <span class="text-sm text-gray-600">{{ data.description || '—' }}</span>
      </template>
    </Column>
    <Column header="Enabled" style="width: 80px">
      <template #body="{ data }">
        <InputSwitch :model-value="data.is_enabled" @update:model-value="emit('toggle', data)" />
      </template>
    </Column>
    <Column header="Rollout" style="width: 80px">
      <template #body="{ data }">
        <Tag :value="`${data.rollout_percentage}%`" severity="info" class="!text-xs" />
      </template>
    </Column>
    <Column header="Plans" style="width: 120px">
      <template #body="{ data }">
        <span class="text-sm text-gray-500">{{ data.allowed_plans?.length || 'All' }}</span>
      </template>
    </Column>
    <Column header="Actions" style="width: 100px">
      <template #body="{ data }">
        <div class="flex gap-1">
          <Button icon="pi pi-pencil" severity="secondary" text size="small" @click="emit('edit', data)" />
          <Button icon="pi pi-trash" severity="danger" text size="small" @click="emit('remove', data)" />
        </div>
      </template>
    </Column>
  </DataTable>
</template>
