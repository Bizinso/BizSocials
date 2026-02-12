<script setup lang="ts">
import { useWorkspace } from '@/composables/useWorkspace'
import Select from 'primevue/select'

const { workspaces, currentWorkspaceId, switchWorkspace } = useWorkspace()

function onWorkspaceChange(event: { value: string }) {
  switchWorkspace(event.value)
}
</script>

<template>
  <Select
    :model-value="currentWorkspaceId"
    :options="workspaces"
    option-label="name"
    option-value="id"
    placeholder="Select workspace"
    class="w-full"
    @change="onWorkspaceChange"
  >
    <template #option="{ option }">
      <div class="flex items-center gap-2">
        <div
          class="flex h-6 w-6 items-center justify-center rounded text-xs font-bold text-white"
          :style="{ backgroundColor: option.color || '#3b82f6' }"
        >
          {{ option.icon || option.name.charAt(0).toUpperCase() }}
        </div>
        <span>{{ option.name }}</span>
      </div>
    </template>
    <template #value="{ value }">
      <div v-if="value" class="flex items-center gap-2">
        <div
          class="flex h-6 w-6 items-center justify-center rounded text-xs font-bold text-white"
          :style="{ backgroundColor: workspaces.find(w => w.id === value)?.color || '#3b82f6' }"
        >
          {{ workspaces.find(w => w.id === value)?.icon || workspaces.find(w => w.id === value)?.name.charAt(0).toUpperCase() }}
        </div>
        <span>{{ workspaces.find(w => w.id === value)?.name }}</span>
      </div>
      <span v-else class="text-gray-400">Select workspace</span>
    </template>
  </Select>
</template>
