<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { formatDate } from '@/utils/formatters'
import { supportApi } from '@/api/support'
import { useToast } from '@/composables/useToast'
import type { SupportTicketData, SupportCommentData } from '@/types/support'
import TicketCommentList from './TicketCommentList.vue'
import TicketCommentForm from './TicketCommentForm.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const props = defineProps<{
  ticket: SupportTicketData
}>()

const emit = defineEmits<{
  updated: []
}>()

const toast = useToast()
const comments = ref<SupportCommentData[]>([])

onMounted(async () => {
  comments.value = await supportApi.listComments(props.ticket.id)
})

function onCommented(comment: SupportCommentData) {
  comments.value.push(comment)
}

async function closeTicket() {
  try {
    await supportApi.closeTicket(props.ticket.id)
    toast.success('Ticket closed')
    emit('updated')
  } catch {
    toast.error('Failed to close ticket')
  }
}

async function reopenTicket() {
  try {
    await supportApi.reopenTicket(props.ticket.id)
    toast.success('Ticket reopened')
    emit('updated')
  } catch {
    toast.error('Failed to reopen ticket')
  }
}

function statusSeverity(status: string) {
  switch (status) {
    case 'new': case 'open': return 'info'
    case 'in_progress': case 'waiting_customer': return 'warn'
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
    default: return 'secondary'
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between">
      <div>
        <div class="flex items-center gap-3">
          <span class="font-mono text-sm text-gray-500">{{ ticket.ticket_number }}</span>
          <Tag :value="ticket.status.replace(/_/g, ' ')" :severity="statusSeverity(ticket.status)" class="capitalize" />
          <Tag :value="ticket.priority" :severity="prioritySeverity(ticket.priority)" class="capitalize" />
        </div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ ticket.subject }}</h1>
        <p class="mt-1 text-sm text-gray-500">
          Created {{ formatDate(ticket.created_at) }}
          <span v-if="ticket.assigned_to_name"> &middot; Assigned to {{ ticket.assigned_to_name }}</span>
        </p>
      </div>

      <div class="flex gap-2">
        <Button
          v-if="ticket.status !== 'closed' && ticket.status !== 'resolved'"
          label="Close"
          severity="secondary"
          size="small"
          @click="closeTicket"
        />
        <Button
          v-if="ticket.status === 'closed' || ticket.status === 'resolved'"
          label="Reopen"
          severity="secondary"
          size="small"
          @click="reopenTicket"
        />
      </div>
    </div>

    <!-- Description -->
    <div class="mb-8 rounded-lg border border-gray-200 bg-gray-50 p-4">
      <p class="whitespace-pre-wrap text-sm text-gray-700">{{ ticket.description }}</p>
    </div>

    <!-- Info Grid -->
    <div class="mb-8 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
      <div>
        <span class="text-gray-500">Type</span>
        <p class="font-medium capitalize text-gray-900">{{ ticket.type.replace(/_/g, ' ') }}</p>
      </div>
      <div v-if="ticket.category_name">
        <span class="text-gray-500">Category</span>
        <p class="font-medium text-gray-900">{{ ticket.category_name }}</p>
      </div>
      <div v-if="ticket.first_response_at">
        <span class="text-gray-500">First Response</span>
        <p class="font-medium text-gray-900">{{ formatDate(ticket.first_response_at) }}</p>
      </div>
      <div v-if="ticket.resolved_at">
        <span class="text-gray-500">Resolved</span>
        <p class="font-medium text-gray-900">{{ formatDate(ticket.resolved_at) }}</p>
      </div>
    </div>

    <!-- Comments -->
    <h2 class="mb-4 text-lg font-semibold text-gray-900">Conversation</h2>
    <TicketCommentList :comments="comments" />

    <!-- Reply -->
    <div v-if="ticket.status !== 'closed'" class="mt-6">
      <TicketCommentForm :ticket-id="ticket.id" @commented="onCommented" />
    </div>
  </div>
</template>
