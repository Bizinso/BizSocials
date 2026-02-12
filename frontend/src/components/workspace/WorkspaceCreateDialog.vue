<script setup lang="ts">
import { ref } from 'vue'
import { workspaceApi } from '@/api/workspace'
import { useWorkspaceStore } from '@/stores/workspace'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  created: []
}>()

const workspaceStore = useWorkspaceStore()
const toast = useToast()

const form = ref({
  name: '',
  description: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  try {
    const workspace = await workspaceApi.create({
      name: form.value.name,
      description: form.value.description || null,
    })
    workspaceStore.addWorkspace(workspace)
    toast.success('Workspace created!')
    form.value = { name: '', description: '' }
    emit('update:visible', false)
    emit('created')
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

function onHide() {
  emit('update:visible', false)
  errors.value = {}
}
</script>

<template>
  <Dialog
    :visible="props.visible"
    header="Create Workspace"
    :modal="true"
    :closable="true"
    :style="{ width: '480px' }"
    @update:visible="onHide"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label for="ws-name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
        <InputText
          id="ws-name"
          v-model="form.name"
          placeholder="e.g. Marketing Team"
          class="w-full"
          :invalid="!!errors.name"
          autofocus
        />
        <small v-if="errors.name" class="mt-1 text-red-500">{{ errors.name[0] }}</small>
      </div>

      <div>
        <label for="ws-desc" class="mb-1 block text-sm font-medium text-gray-700">Description (optional)</label>
        <Textarea
          id="ws-desc"
          v-model="form.description"
          placeholder="What is this workspace for?"
          rows="3"
          class="w-full"
        />
      </div>
    </form>

    <template #footer>
      <Button label="Cancel" severity="secondary" text @click="onHide" />
      <Button label="Create" :loading="loading" @click="handleSubmit" />
    </template>
  </Dialog>
</template>
