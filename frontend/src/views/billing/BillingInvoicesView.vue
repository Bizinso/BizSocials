<script setup lang="ts">
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import InvoiceList from '@/components/billing/InvoiceList.vue'
</script>

<template>
  <AppPageHeader title="Invoices" description="View and download your billing invoices" />

  <AppCard>
    <InvoiceList />
  </AppCard>
</template>
