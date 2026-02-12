<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { billingApi } from '@/api/billing'
import type { InvoiceData } from '@/types/billing'
import type { PaginationMeta } from '@/types/api'
import { formatDate, formatCurrency } from '@/utils/formatters'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'

const invoices = ref<InvoiceData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)

onMounted(() => fetchInvoices())

async function fetchInvoices(page = 1) {
  loading.value = true
  try {
    const response = await billingApi.listInvoices({ page })
    invoices.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

function statusSeverity(status: string) {
  switch (status) {
    case 'paid': return 'success'
    case 'issued': return 'info'
    case 'draft': return 'secondary'
    case 'cancelled': case 'expired': return 'danger'
    default: return 'secondary'
  }
}

function downloadInvoice(invoice: InvoiceData) {
  if (invoice.pdf_url) {
    window.open(invoice.pdf_url, '_blank')
  } else {
    window.open(billingApi.downloadInvoiceUrl(invoice.id), '_blank')
  }
}

function onPageChange(event: any) {
  fetchInvoices(event.page + 1)
}
</script>

<template>
  <div>
    <AppLoadingSkeleton v-if="loading" :lines="4" :count="3" />

    <template v-else-if="invoices.length > 0">
      <DataTable :value="invoices" striped-rows class="text-sm">
        <Column header="Invoice #" class="w-[140px]">
          <template #body="{ data }">
            <span class="font-medium text-gray-900">{{ data.invoice_number }}</span>
          </template>
        </Column>
        <Column header="Date" class="w-[120px]">
          <template #body="{ data }">
            {{ formatDate(data.created_at) }}
          </template>
        </Column>
        <Column header="Amount" class="w-[120px]">
          <template #body="{ data }">
            <span class="font-medium">{{ formatCurrency(data.total, data.currency) }}</span>
          </template>
        </Column>
        <Column header="Status" class="w-[100px]">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="statusSeverity(data.status)" />
          </template>
        </Column>
        <Column header="" class="w-[60px]">
          <template #body="{ data }">
            <Button icon="pi pi-download" text rounded size="small" @click="downloadInvoice(data)" />
          </template>
        </Column>
      </DataTable>

      <Paginator
        v-if="pagination && pagination.last_page > 1"
        :rows="pagination.per_page"
        :total-records="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        class="mt-4"
        @page="onPageChange"
      />
    </template>

    <AppEmptyState
      v-else
      title="No invoices"
      description="Invoices will appear here once you have an active subscription."
      icon="pi pi-file"
    />
  </div>
</template>
