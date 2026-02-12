<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useBillingStore } from '@/stores/billing'
import { billingApi } from '@/api/billing'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { BillingCycle } from '@/types/enums'
import type { PlanData } from '@/types/billing'
import { formatCurrency } from '@/utils/formatters'
import Button from 'primevue/button'
import ToggleSwitch from 'primevue/toggleswitch'

const emit = defineEmits<{
  subscribed: []
}>()

const billingStore = useBillingStore()
const toast = useToast()
const yearly = ref(true)
const subscribing = ref(false)
const selectedPlanId = ref('')

const currentPlanId = computed(() => billingStore.summary?.current_subscription?.plan_id)

onMounted(async () => {
  await billingStore.fetchPlans()
})

const publicPlans = computed(() =>
  billingStore.plans.filter((p) => p.is_active && p.is_public).sort((a, b) => a.sort_order - b.sort_order),
)

function getPrice(plan: PlanData): string {
  return yearly.value ? plan.price_inr_yearly : plan.price_inr_monthly
}

function getPriceLabel(plan: PlanData): string {
  const price = parseFloat(getPrice(plan))
  if (price === 0) return 'Free'
  return formatCurrency(price) + (yearly.value ? '/year' : '/month')
}

async function subscribe(plan: PlanData) {
  subscribing.value = true
  selectedPlanId.value = plan.id
  try {
    if (currentPlanId.value) {
      const sub = await billingApi.changePlan({
        plan_id: plan.id,
        billing_cycle: yearly.value ? BillingCycle.Yearly : BillingCycle.Monthly,
      })
      billingStore.setSubscription(sub)
      toast.success('Plan changed successfully')
    } else {
      const sub = await billingApi.createSubscription({
        plan_id: plan.id,
        billing_cycle: yearly.value ? BillingCycle.Yearly : BillingCycle.Monthly,
      })
      billingStore.setSubscription(sub)
      toast.success('Subscribed successfully')
    }
    emit('subscribed')
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    subscribing.value = false
    selectedPlanId.value = ''
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-center gap-3">
      <span :class="yearly ? 'text-gray-400' : 'font-medium text-gray-900'">Monthly</span>
      <ToggleSwitch v-model="yearly" />
      <span :class="yearly ? 'font-medium text-gray-900' : 'text-gray-400'">Yearly <span class="text-green-600">(Save 20%)</span></span>
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="plan in publicPlans"
        :key="plan.id"
        class="rounded-lg border-2 p-6 transition-shadow hover:shadow-md"
        :class="plan.id === currentPlanId ? 'border-primary-500 bg-primary-50/30' : 'border-gray-200'"
      >
        <h3 class="mb-1 text-lg font-semibold text-gray-900">{{ plan.name }}</h3>
        <p v-if="plan.description" class="mb-4 text-sm text-gray-500">{{ plan.description }}</p>
        <p class="mb-4 text-3xl font-bold text-gray-900">{{ getPriceLabel(plan) }}</p>

        <ul class="mb-6 space-y-2 text-sm text-gray-600">
          <li v-for="feature in plan.features" :key="feature" class="flex items-center gap-2">
            <i class="pi pi-check text-green-500" />
            {{ feature }}
          </li>
        </ul>

        <Button
          v-if="plan.id === currentPlanId"
          label="Current Plan"
          severity="secondary"
          class="w-full"
          disabled
        />
        <Button
          v-else
          :label="currentPlanId ? 'Switch Plan' : 'Get Started'"
          class="w-full"
          :loading="subscribing && selectedPlanId === plan.id"
          @click="subscribe(plan)"
        />
      </div>
    </div>
  </div>
</template>
