<script setup lang="ts">
import type { SubscriptionData } from '@/types/billing'
import { formatDate, formatCurrency } from '@/utils/formatters'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const props = defineProps<{
  subscription: SubscriptionData | null
}>()

const emit = defineEmits<{
  changePlan: []
  cancel: []
  reactivate: []
}>()

function statusSeverity(status: string) {
  switch (status) {
    case 'active': return 'success'
    case 'pending': case 'created': case 'authenticated': return 'info'
    case 'halted': case 'cancelled': return 'warn'
    case 'expired': case 'completed': return 'danger'
    default: return 'secondary'
  }
}
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-6">
    <template v-if="subscription">
      <div class="mb-4 flex items-start justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ subscription.plan_name }}</h3>
          <Tag :value="subscription.status" :severity="statusSeverity(subscription.status)" class="mt-1" />
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(subscription.amount, subscription.currency) }}</p>
          <p class="text-sm text-gray-500">per {{ subscription.billing_cycle === 'yearly' ? 'year' : 'month' }}</p>
        </div>
      </div>

      <div class="mb-4 space-y-2 text-sm text-gray-600">
        <div v-if="subscription.is_on_trial" class="flex items-center gap-2">
          <i class="pi pi-clock text-orange-500" />
          <span>Trial ends {{ formatDate(subscription.trial_end) }} ({{ subscription.trial_days_remaining }} days left)</span>
        </div>
        <div v-if="subscription.current_period_end" class="flex items-center gap-2">
          <i class="pi pi-calendar text-gray-400" />
          <span>{{ subscription.cancel_at_period_end ? 'Expires' : 'Renews' }} {{ formatDate(subscription.current_period_end) }}</span>
        </div>
        <div v-if="subscription.cancel_at_period_end" class="flex items-center gap-2 text-orange-600">
          <i class="pi pi-exclamation-triangle" />
          <span>Scheduled for cancellation</span>
        </div>
      </div>

      <div class="flex gap-2">
        <Button label="Change Plan" severity="secondary" size="small" @click="emit('changePlan')" />
        <Button
          v-if="subscription.cancel_at_period_end"
          label="Reactivate"
          severity="success"
          size="small"
          @click="emit('reactivate')"
        />
        <Button
          v-else-if="subscription.status === 'active'"
          label="Cancel"
          severity="danger"
          size="small"
          text
          @click="emit('cancel')"
        />
      </div>
    </template>

    <div v-else class="text-center">
      <i class="pi pi-credit-card mb-2 text-3xl text-gray-300" />
      <p class="mb-3 text-gray-500">No active subscription</p>
      <Button label="Choose a Plan" @click="emit('changePlan')" />
    </div>
  </div>
</template>
