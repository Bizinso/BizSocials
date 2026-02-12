<script setup lang="ts">
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import TicketCreateForm from '@/components/support/TicketCreateForm.vue'
</script>

<template>
  <AppPageHeader title="New Support Ticket" description="Describe your issue and we'll get back to you" />

  <AppCard>
    <TicketCreateForm />
  </AppCard>
</template>
