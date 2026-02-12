<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { workspaceApi } from '@/api/workspace'
import { useWorkspaceStore } from '@/stores/workspace'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WorkspaceData } from '@/types/workspace'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const props = defineProps<{
  workspace: WorkspaceData
}>()

const workspaceStore = useWorkspaceStore()
const toast = useToast()

const form = ref({
  name: '',
  description: '',
  icon: '',
  color: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

onMounted(() => {
  form.value = {
    name: props.workspace.name,
    description: props.workspace.description || '',
    icon: props.workspace.icon || '',
    color: props.workspace.color || '',
  }
})

async function handleSave() {
  loading.value = true
  errors.value = {}
  try {
    const updated = await workspaceApi.update(props.workspace.id, {
      name: form.value.name,
      description: form.value.description || null,
      icon: form.value.icon || null,
      color: form.value.color || null,
    })
    workspaceStore.updateWorkspace(updated)
    toast.success('Workspace updated!')
  } catch (e) {
    const err = parseApiError(e)
    errors.value = err.errors
    if (!Object.keys(err.errors).length) {
      toast.error(err.message)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSave" class="max-w-lg space-y-4">
    <div>
      <label for="ws-name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
      <InputText id="ws-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
      <small v-if="errors.name" class="mt-1 text-red-500">{{ errors.name[0] }}</small>
    </div>

    <div>
      <label for="ws-desc" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
      <Textarea id="ws-desc" v-model="form.description" rows="3" class="w-full" />
    </div>

    <div class="flex gap-4">
      <div class="flex-1">
        <label for="ws-icon" class="mb-1 block text-sm font-medium text-gray-700">Icon (emoji)</label>
        <InputText id="ws-icon" v-model="form.icon" class="w-full" maxlength="10" />
      </div>
      <div class="flex-1">
        <label for="ws-color" class="mb-1 block text-sm font-medium text-gray-700">Color</label>
        <InputText id="ws-color" v-model="form.color" type="color" class="h-10 w-full" />
      </div>
    </div>

    <Button type="submit" label="Save changes" :loading="loading" />
  </form>
</template>
