<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { tenantApi } from '@/api/tenant'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import type { InvitationData } from '@/types/tenant'
import { formatRelative } from '@/utils/formatters'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const toast = useToast()
const { confirmDelete } = useConfirm()

const invitations = ref<InvitationData[]>([])
const loading = ref(false)

onMounted(() => fetchInvitations())

async function fetchInvitations() {
  loading.value = true
  try {
    const response = await tenantApi.getInvitations()
    invitations.value = response.data
  } finally {
    loading.value = false
  }
}

async function resend(invitation: InvitationData) {
  try {
    await tenantApi.resendInvitation(invitation.id)
    toast.success('Invitation resent!')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function cancel(invitation: InvitationData) {
  confirmDelete({
    message: `Cancel invitation to ${invitation.email}?`,
    async onAccept() {
      try {
        await tenantApi.cancelInvitation(invitation.id)
        invitations.value = invitations.value.filter((i) => i.id !== invitation.id)
        toast.success('Invitation cancelled')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}

function statusSeverity(status: string) {
  const map: Record<string, string> = {
    pending: 'warn',
    accepted: 'success',
    expired: 'secondary',
    revoked: 'danger',
  }
  return (map[status] || 'secondary') as 'warn' | 'success' | 'secondary' | 'danger'
}
</script>

<template>
  <DataTable :value="invitations" :loading="loading" striped-rows class="text-sm">
    <Column header="Email" field="email" class="min-w-[200px]" />
    <Column header="Role" field="role" class="w-[100px]">
      <template #body="{ data }">
        <span class="capitalize">{{ data.role }}</span>
      </template>
    </Column>
    <Column header="Status" class="w-[100px]">
      <template #body="{ data }">
        <Tag :value="data.status" :severity="statusSeverity(data.status)" />
      </template>
    </Column>
    <Column header="Sent" class="w-[120px]">
      <template #body="{ data }">
        <span class="text-xs text-gray-500">{{ formatRelative(data.created_at) }}</span>
      </template>
    </Column>
    <Column header="" class="w-[120px]">
      <template #body="{ data }">
        <div v-if="data.status === 'pending'" class="flex gap-1">
          <Button icon="pi pi-refresh" v-tooltip="'Resend'" text rounded size="small" @click="resend(data)" />
          <Button icon="pi pi-times" v-tooltip="'Cancel'" severity="danger" text rounded size="small" @click="cancel(data)" />
        </div>
      </template>
    </Column>
  </DataTable>
</template>
