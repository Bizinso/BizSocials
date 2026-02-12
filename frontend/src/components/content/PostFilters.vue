<script setup lang="ts">
import { PostStatus, PostType } from '@/types/enums'
import Select from 'primevue/select'
import AppSearchInput from '@/components/shared/AppSearchInput.vue'

const props = defineProps<{
  search?: string
  status?: string
  postType?: string
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:status': [value: string]
  'update:postType': [value: string]
}>()

const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Draft', value: PostStatus.Draft },
  { label: 'Submitted', value: PostStatus.Submitted },
  { label: 'Approved', value: PostStatus.Approved },
  { label: 'Scheduled', value: PostStatus.Scheduled },
  { label: 'Published', value: PostStatus.Published },
  { label: 'Failed', value: PostStatus.Failed },
]

const typeOptions = [
  { label: 'All types', value: '' },
  { label: 'Standard', value: PostType.Standard },
  { label: 'Reel', value: PostType.Reel },
  { label: 'Story', value: PostType.Story },
  { label: 'Thread', value: PostType.Thread },
  { label: 'Article', value: PostType.Article },
]
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <div class="w-64">
      <AppSearchInput
        :model-value="search || ''"
        placeholder="Search posts..."
        @update:model-value="emit('update:search', $event)"
      />
    </div>
    <Select
      :model-value="status"
      :options="statusOptions"
      option-label="label"
      option-value="value"
      placeholder="Status"
      class="w-40"
      @change="(e: any) => emit('update:status', e.value)"
    />
    <Select
      :model-value="postType"
      :options="typeOptions"
      option-label="label"
      option-value="value"
      placeholder="Type"
      class="w-36"
      @change="(e: any) => emit('update:postType', e.value)"
    />
  </div>
</template>
