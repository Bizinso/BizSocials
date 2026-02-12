<script setup lang="ts">
import { ref } from 'vue'
import { workspaceApi } from '@/api/workspace'
import { useToast } from '@/composables/useToast'
import { usePermissions } from '@/composables/usePermissions'
import { parseApiError } from '@/utils/error-handler'
import { WorkspaceRole } from '@/types/enums'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'

const props = defineProps<{
  workspaceId: string
}>()

const emit = defineEmits<{
  added: []
}>()

const toast = useToast()
const { canManageMembers } = usePermissions()

const userId = ref('')
const role = ref<WorkspaceRole>(WorkspaceRole.Viewer)
const loading = ref(false)

const roleOptions = [
  { label: 'Admin', value: WorkspaceRole.Admin },
  { label: 'Editor', value: WorkspaceRole.Editor },
  { label: 'Viewer', value: WorkspaceRole.Viewer },
]

async function handleAdd() {
  if (!userId.value.trim()) return
  loading.value = true
  try {
    await workspaceApi.addMember(props.workspaceId, {
      user_id: userId.value.trim(),
      role: role.value,
    })
    toast.success('Member added!')
    userId.value = ''
    role.value = WorkspaceRole.Viewer
    emit('added')
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form v-if="canManageMembers" @submit.prevent="handleAdd" class="flex items-end gap-3">
    <div class="flex-1">
      <label class="mb-1 block text-sm font-medium text-gray-700">User ID</label>
      <InputText v-model="userId" placeholder="Enter user ID" class="w-full" />
    </div>
    <div class="w-36">
      <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
      <Select v-model="role" :options="roleOptions" option-label="label" option-value="value" class="w-full" />
    </div>
    <Button type="submit" label="Add" icon="pi pi-plus" :loading="loading" />
  </form>
</template>
