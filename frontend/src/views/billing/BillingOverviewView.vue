<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useBillingStore } from '@/stores/billing'
import { billingApi } from '@/api/billing'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import SubscriptionCard from '@/components/billing/SubscriptionCard.vue'
import UsageMetrics from '@/components/billing/UsageMetrics.vue'

const router = useRouter()
const billingStore = useBillingStore()
const toast = useToast()
const { confirmDelete } = useConfirm()

onMounted(async () => {
  try {
    await Promise.all([billingStore.fetchSummary(), billingStore.fetchUsage()])
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
})

function goToPlans() {
  router.push('/app/billing/plans')
}

function cancelSubscription() {
  confirmDelete({
    message: 'Are you sure you want to cancel your subscription? You will retain access until the end of the current billing period.',
    async onAccept() {
      try {
        const sub = await billingApi.cancelSubscription()
        billingStore.setSubscription(sub)
        toast.success('Subscription cancelled')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}

async function reactivateSubscription() {
  try {
    const sub = await billingApi.reactivateSubscription()
    billingStore.setSubscription(sub)
    toast.success('Subscription reactivated')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}
</script>

<template>
  <AppPageHeader title="Billing" description="Manage your subscription and billing" />

  <AppLoadingSkeleton v-if="billingStore.loading" :lines="5" />

  <div v-else class="grid gap-6 lg:grid-cols-2">
    <AppCard title="Subscription">
      <SubscriptionCard
        :subscription="billingStore.summary?.current_subscription || null"
        @change-plan="goToPlans"
        @cancel="cancelSubscription"
        @reactivate="reactivateSubscription"
      />
    </AppCard>

    <AppCard title="Usage">
      <UsageMetrics :usage="billingStore.usage" />
    </AppCard>
  </div>
</template>
