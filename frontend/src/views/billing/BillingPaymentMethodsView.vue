<script setup lang="ts">
import { ref } from 'vue'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import PaymentMethodList from '@/components/billing/PaymentMethodList.vue'
import AddPaymentDialog from '@/components/billing/AddPaymentDialog.vue'
import Button from 'primevue/button'

const showAddDialog = ref(false)
const listKey = ref(0)

function onAdded() {
  listKey.value++
}
</script>

<template>
  <AppPageHeader title="Payment Methods" description="Manage your payment methods">
    <template #actions>
      <Button label="Add Payment Method" icon="pi pi-plus" @click="showAddDialog = true" />
    </template>
  </AppPageHeader>

  <AppCard>
    <PaymentMethodList :key="listKey" />
  </AppCard>

  <AddPaymentDialog v-model:visible="showAddDialog" @added="onAdded" />
</template>
