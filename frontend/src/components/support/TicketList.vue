<script setup lang="ts">
import { useRouter } from 'vue-router'
import { formatDate } from '@/utils/formatters'
import type { SupportTicketSummaryData } from '@/types/support'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineProps<{
  tickets: SupportTicketSummaryData[]
}>()

const router = useRouter()

function statusSeverity(status: string) {
  switch (status) {
    case 'new': return 'info'
    case 'open': return 'info'
    case 'in_progress': return 'warn'
    case 'waiting_customer': return 'warn'
    case 'resolved': return 'success'
    case 'closed': return 'secondary'
    default: return 'secondary'
  }
}

function prioritySeverity(priority: string) {
  switch (priority) {
    case 'urgent': return 'danger'
    case 'high': return 'warn'
    case 'medium': return 'info'
    case 'low': return 'secondary'
    default: return 'secondary'
  }
}

function openTicket(event: { data: SupportTicketSummaryData }) {
  router.push({ name: 'support-ticket-detail', params: { ticketId: event.data.id } })
}
</script>

<template>
  <DataTable
    :value="tickets"
    :rows="10"
    striped-rows
    class="cursor-pointer"
    @row-click="openTicket"
  >
    <Column field="ticket_number" header="Ticket" style="width: 120px">
      <template #body="{ data }">
        <span class="font-mono text-sm font-medium text-primary-600">{{ data.ticket_number }}</span>
      </template>
    </Column>
    <Column field="subject" header="Subject">
      <template #body="{ data }">
        <span class="font-medium text-gray-900">{{ data.subject }}</span>
      </template>
    </Column>
    <Column field="status" header="Status" style="width: 140px">
      <template #body="{ data }">
        <Tag :value="data.status.replace(/_/g, ' ')" :severity="statusSeverity(data.status)" class="!text-xs capitalize" />
      </template>
    </Column>
    <Column field="priority" header="Priority" style="width: 100px">
      <template #body="{ data }">
        <Tag :value="data.priority" :severity="prioritySeverity(data.priority)" class="!text-xs capitalize" />
      </template>
    </Column>
    <Column field="comment_count" header="Replies" style="width: 80px">
      <template #body="{ data }">
        <span class="text-sm text-gray-500">{{ data.comment_count }}</span>
      </template>
    </Column>
    <Column field="created_at" header="Created" style="width: 140px">
      <template #body="{ data }">
        <span class="text-sm text-gray-500">{{ formatDate(data.created_at) }}</span>
      </template>
    </Column>
  </DataTable>
</template>
