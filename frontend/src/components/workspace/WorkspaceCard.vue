<script setup lang="ts">
import type { WorkspaceData } from '@/types/workspace'
import Button from 'primevue/button'

defineProps<{
  workspace: WorkspaceData
}>()

const emit = defineEmits<{
  select: [workspace: WorkspaceData]
}>()
</script>

<template>
  <div
    class="cursor-pointer rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
    @click="emit('select', workspace)"
  >
    <div class="mb-3 flex items-center gap-3">
      <div
        class="flex h-10 w-10 items-center justify-center rounded-lg text-lg font-bold text-white"
        :style="{ backgroundColor: workspace.color || '#3b82f6' }"
      >
        {{ workspace.icon || workspace.name.charAt(0).toUpperCase() }}
      </div>
      <div class="min-w-0 flex-1">
        <h3 class="truncate text-sm font-semibold text-gray-900">{{ workspace.name }}</h3>
        <p class="text-xs text-gray-500">{{ workspace.member_count }} member{{ workspace.member_count !== 1 ? 's' : '' }}</p>
      </div>
    </div>
    <p v-if="workspace.description" class="mb-3 line-clamp-2 text-xs text-gray-500">
      {{ workspace.description }}
    </p>
    <div class="flex items-center justify-between">
      <span
        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
        :class="workspace.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
      >
        {{ workspace.status }}
      </span>
      <Button icon="pi pi-arrow-right" text rounded size="small" @click.stop="emit('select', workspace)" />
    </div>
  </div>
</template>
