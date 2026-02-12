<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { workspaceApi } from '@/api/workspace'
import { inboxApi } from '@/api/inbox'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WorkspaceMemberData } from '@/types/workspace'
import type { InboxItemData } from '@/types/inbox'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Button from 'primevue/button'

const props = defineProps<{
  visible: boolean
  workspaceId: string
  item: InboxItemData | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  assigned: [item: InboxItemData]
}>()

const toast = useToast()
const members = ref<WorkspaceMemberData[]>([])
const selectedUserId = ref('')
const loading = ref(false)

onMounted(async () => {
  try {
    const response = await workspaceApi.getMembers(props.workspaceId)
    members.value = response.data
  } catch {
    // fail silently
  }
})

async function assign() {
  if (!props.item || !selectedUserId.value) return
  loading.value = true
  try {
    const updated = await inboxApi.assign(props.workspaceId, props.item.id, { user_id: selectedUserId.value })
    toast.success('Item assigned')
    emit('assigned', updated)
    emit('update:visible', false)
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    header="Assign to Team Member"
    :style="{ width: '400px' }"
    modal
    @update:visible="emit('update:visible', $event)"
  >
    <div class="space-y-3">
      <Select
        v-model="selectedUserId"
        :options="members"
        option-label="name"
        option-value="user_id"
        placeholder="Select a member"
        class="w-full"
      />
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" @click="emit('update:visible', false)" />
      <Button label="Assign" icon="pi pi-user" :disabled="!selectedUserId" :loading="loading" @click="assign" />
    </template>
  </Dialog>
</template>
