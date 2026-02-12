<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import TicketList from '@/components/support/TicketList.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import { supportApi } from '@/api/support'
import type { SupportTicketSummaryData } from '@/types/support'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'

const router = useRouter()
const tickets = ref<SupportTicketSummaryData[]>([])
const loading = ref(true)
const totalRecords = ref(0)
const currentPage = ref(1)

async function fetchTickets() {
  loading.value = true
  try {
    const result = await supportApi.listTickets({ page: currentPage.value })
    tickets.value = result.data
    totalRecords.value = result.meta.total
  } finally {
    loading.value = false
  }
}

onMounted(fetchTickets)

function onPage(event: { page: number }) {
  currentPage.value = event.page + 1
  fetchTickets()
}
</script>

<template>
  <AppPageHeader title="Support Tickets" description="View and manage your support requests">
    <template #actions>
      <Button label="New Ticket" icon="pi pi-plus" @click="router.push({ name: 'support-new-ticket' })" />
    </template>
  </AppPageHeader>

  <AppCard>
    <AppLoadingSkeleton v-if="loading" :lines="6" />
    <template v-else-if="tickets.length > 0">
      <TicketList :tickets="tickets" />
      <Paginator
        v-if="totalRecords > 10"
        :rows="10"
        :total-records="totalRecords"
        :first="(currentPage - 1) * 10"
        class="mt-4"
        @page="onPage"
      />
    </template>
    <AppEmptyState
      v-else
      icon="pi pi-ticket"
      title="No support tickets"
      description="Need help? Create a support ticket."
    >
      <Button label="Create Ticket" icon="pi pi-plus" size="small" @click="router.push({ name: 'support-new-ticket' })" />
    </AppEmptyState>
  </AppCard>
</template>
