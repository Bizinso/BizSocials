<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import TicketDetail from '@/components/support/TicketDetail.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import { supportApi } from '@/api/support'
import type { SupportTicketData } from '@/types/support'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const ticket = ref<SupportTicketData | null>(null)
const loading = ref(true)

async function loadTicket(id: string) {
  loading.value = true
  try {
    ticket.value = await supportApi.getTicket(id)
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.ticketId as string,
  (id) => { if (id) loadTicket(id) },
  { immediate: true },
)

function onUpdated() {
  loadTicket(route.params.ticketId as string)
}
</script>

<template>
  <AppPageHeader :title="ticket?.ticket_number || 'Loading...'" description="Support ticket details">
    <template #actions>
      <Button label="Back to Tickets" icon="pi pi-arrow-left" severity="secondary" size="small" @click="router.push({ name: 'support-tickets' })" />
    </template>
  </AppPageHeader>

  <AppCard>
    <AppLoadingSkeleton v-if="loading" :lines="10" />
    <TicketDetail v-else-if="ticket" :ticket="ticket" @updated="onUpdated" />
  </AppCard>
</template>
