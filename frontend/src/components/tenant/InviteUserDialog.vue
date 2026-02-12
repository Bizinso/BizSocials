<script setup lang="ts">
import { ref } from 'vue'
import { tenantApi } from '@/api/tenant'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { TenantRole } from '@/types/enums'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  invited: []
}>()

const toast = useToast()

const form = ref({
  email: '',
  role: TenantRole.Member as TenantRole,
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

const roleOptions = [
  { label: 'Admin', value: TenantRole.Admin },
  { label: 'Member', value: TenantRole.Member },
]

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  try {
    await tenantApi.sendInvitation({
      email: form.value.email,
      role: form.value.role,
    })
    toast.success('Invitation sent!')
    form.value = { email: '', role: TenantRole.Member }
    emit('update:visible', false)
    emit('invited')
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
    header="Invite Team Member"
    :modal="true"
    :closable="true"
    :style="{ width: '440px' }"
    @update:visible="onHide"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label for="invite-email" class="mb-1 block text-sm font-medium text-gray-700">Email address</label>
        <InputText
          id="invite-email"
          v-model="form.email"
          type="email"
          placeholder="colleague@company.com"
          class="w-full"
          :invalid="!!errors.email"
          autofocus
        />
        <small v-if="errors.email" class="mt-1 text-red-500">{{ errors.email[0] }}</small>
      </div>

      <div>
        <label for="invite-role" class="mb-1 block text-sm font-medium text-gray-700">Role</label>
        <Select
          id="invite-role"
          v-model="form.role"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          class="w-full"
        />
      </div>
    </form>

    <template #footer>
      <Button label="Cancel" severity="secondary" text @click="onHide" />
      <Button label="Send invitation" icon="pi pi-send" :loading="loading" @click="handleSubmit" />
    </template>
  </Dialog>
</template>
