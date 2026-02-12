<script setup lang="ts">
import { InboxItemStatus, InboxItemType } from '@/types/enums'
import Select from 'primevue/select'
import AppSearchInput from '@/components/shared/AppSearchInput.vue'

defineProps<{
  search?: string
  status?: string
  type?: string
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:status': [value: string]
  'update:type': [value: string]
}>()

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Unread', value: InboxItemStatus.Unread },
  { label: 'Read', value: InboxItemStatus.Read },
  { label: 'Resolved', value: InboxItemStatus.Resolved },
  { label: 'Archived', value: InboxItemStatus.Archived },
]

const typeOptions = [
  { label: 'All types', value: '' },
  { label: 'Comments', value: InboxItemType.Comment },
  { label: 'Mentions', value: InboxItemType.Mention },
]
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <div class="w-64">
      <AppSearchInput
        :model-value="search || ''"
        placeholder="Search inbox..."
        @update:model-value="emit('update:search', $event)"
      />
    </div>
    <Select
      :model-value="status || ''"
      :options="statusOptions"
      option-label="label"
      option-value="value"
      placeholder="Status"
      class="w-36"
      @change="(e: any) => emit('update:status', e.value)"
    />
    <Select
      :model-value="type || ''"
      :options="typeOptions"
      option-label="label"
      option-value="value"
      placeholder="Type"
      class="w-36"
      @change="(e: any) => emit('update:type', e.value)"
    />
  </div>
</template>
