import { defineStore } from 'pinia'
import { ref } from 'vue'
import { billingApi } from '@/api/billing'
import type {
  BillingSummaryData,
  UsageData,
  PlanData,
  SubscriptionData,
  PaymentMethodData,
} from '@/types/billing'

export const useBillingStore = defineStore('billing', () => {
  const summary = ref<BillingSummaryData | null>(null)
  const usage = ref<UsageData | null>(null)
  const plans = ref<PlanData[]>([])
  const paymentMethods = ref<PaymentMethodData[]>([])
  const loading = ref(false)

  async function fetchSummary() {
    loading.value = true
    try {
      summary.value = await billingApi.summary()
    } finally {
      loading.value = false
    }
  }

  async function fetchUsage() {
    usage.value = await billingApi.usage()
  }

  async function fetchPlans() {
    plans.value = await billingApi.plans()
  }

  async function fetchPaymentMethods() {
    paymentMethods.value = await billingApi.listPaymentMethods()
  }

  function setSubscription(subscription: SubscriptionData) {
    if (summary.value) {
      summary.value.current_subscription = subscription
    }
  }

  function addPaymentMethod(method: PaymentMethodData) {
    paymentMethods.value.push(method)
  }

  function removePaymentMethod(id: string) {
    paymentMethods.value = paymentMethods.value.filter((m) => m.id !== id)
  }

  function clear() {
    summary.value = null
    usage.value = null
    plans.value = []
    paymentMethods.value = []
  }

  return {
    summary,
    usage,
    plans,
    paymentMethods,
    loading,
    fetchSummary,
    fetchUsage,
    fetchPlans,
    fetchPaymentMethods,
    setSubscription,
    addPaymentMethod,
    removePaymentMethod,
    clear,
  }
})
