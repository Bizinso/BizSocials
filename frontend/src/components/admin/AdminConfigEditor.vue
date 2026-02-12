<script setup lang="ts">
import { ref } from 'vue'
import { adminConfigApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import type { PlatformConfigData } from '@/types/admin'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

defineProps<{
  configs: Record<string, PlatformConfigData[]>
}>()

const toast = useToast()
const editingKey = ref('')
const editValue = ref('')

function startEdit(config: PlatformConfigData) {
  editingKey.value = config.key
  editValue.value = typeof config.value === 'string' ? config.value : JSON.stringify(config.value)
}

async function saveConfig(config: PlatformConfigData) {
  try {
    let value: unknown = editValue.value
    try { value = JSON.parse(editValue.value) } catch { /* use as string */ }
    await adminConfigApi.update(config.key, value)
    config.value = value
    editingKey.value = ''
    toast.success('Config updated')
  } catch {
    toast.error('Failed to update config')
  }
}

function cancelEdit() {
  editingKey.value = ''
}
</script>

<template>
  <div class="space-y-8">
    <div v-for="(items, category) in configs" :key="category">
      <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">{{ category }}</h3>
      <div class="space-y-2">
        <div
          v-for="config in items"
          :key="config.key"
          class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3"
        >
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="font-mono text-sm font-medium text-gray-900">{{ config.key }}</span>
              <Tag v-if="config.is_sensitive" value="sensitive" severity="warn" class="!text-xs" />
            </div>
            <p v-if="config.description" class="text-xs text-gray-500">{{ config.description }}</p>
          </div>

          <div class="ml-4 flex items-center gap-2">
            <template v-if="editingKey === config.key">
              <InputText v-model="editValue" size="small" class="w-48" />
              <Button icon="pi pi-check" severity="success" text size="small" @click="saveConfig(config)" />
              <Button icon="pi pi-times" severity="secondary" text size="small" @click="cancelEdit" />
            </template>
            <template v-else>
              <span class="max-w-48 truncate text-sm text-gray-600">
                {{ config.is_sensitive ? '••••••' : String(config.value) }}
              </span>
              <Button icon="pi pi-pencil" severity="secondary" text size="small" @click="startEdit(config)" />
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
