<script setup lang="ts">
import Button from 'primevue/button'

defineProps<{
  count: number
}>()

const emit = defineEmits<{
  markRead: []
  resolve: []
  clearSelection: []
}>()
</script>

<template>
  <div v-if="count > 0" class="flex items-center gap-3 rounded-lg bg-primary-50 px-4 py-2">
    <span class="text-sm font-medium text-primary-700">{{ count }} selected</span>
    <Button label="Mark Read" icon="pi pi-eye" severity="info" size="small" text @click="emit('markRead')" />
    <Button label="Resolve" icon="pi pi-check" severity="success" size="small" text @click="emit('resolve')" />
    <Button label="Clear" severity="secondary" size="small" text @click="emit('clearSelection')" />
  </div>
</template>
