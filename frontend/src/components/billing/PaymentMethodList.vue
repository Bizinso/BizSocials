<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { billingApi } from '@/api/billing'
import { useBillingStore } from '@/stores/billing'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import type { PaymentMethodData } from '@/types/billing'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const billingStore = useBillingStore()
const toast = useToast()
const { confirmDelete } = useConfirm()
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    await billingStore.fetchPaymentMethods()
  } finally {
    loading.value = false
  }
})

function methodIcon(type: string): string {
  switch (type) {
    case 'card': return 'pi pi-credit-card'
    case 'upi': return 'pi pi-mobile'
    case 'netbanking': return 'pi pi-building'
    case 'wallet': return 'pi pi-wallet'
    default: return 'pi pi-credit-card'
  }
}

async function setDefault(method: PaymentMethodData) {
  try {
    await billingApi.setDefaultPaymentMethod(method.id)
    billingStore.paymentMethods.forEach((m) => (m.is_default = m.id === method.id))
    toast.success('Default payment method updated')
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function removeMethod(method: PaymentMethodData) {
  confirmDelete({
    message: `Remove ${method.display_name}?`,
    async onAccept() {
      try {
        await billingApi.removePaymentMethod(method.id)
        billingStore.removePaymentMethod(method.id)
        toast.success('Payment method removed')
      } catch (e) {
        toast.error(parseApiError(e).message)
      }
    },
  })
}
</script>

<template>
  <div>
    <AppLoadingSkeleton v-if="loading" :lines="3" :count="2" />

    <template v-else-if="billingStore.paymentMethods.length > 0">
      <div class="space-y-3">
        <div
          v-for="method in billingStore.paymentMethods"
          :key="method.id"
          class="flex items-center justify-between rounded-lg border border-gray-200 p-4"
          :class="{ 'border-primary-300 bg-primary-50/30': method.is_default }"
        >
          <div class="flex items-center gap-3">
            <i :class="methodIcon(method.type)" class="text-xl text-gray-500" />
            <div>
              <p class="font-medium text-gray-900">
                {{ method.display_name }}
                <Tag v-if="method.is_default" value="Default" severity="info" class="ml-2 !text-[10px]" />
                <Tag v-if="method.is_expired" value="Expired" severity="danger" class="ml-2 !text-[10px]" />
              </p>
              <p class="text-xs text-gray-500">{{ method.type_label }}</p>
            </div>
          </div>
          <div class="flex gap-1">
            <Button
              v-if="!method.is_default"
              label="Set Default"
              severity="secondary"
              size="small"
              text
              @click="setDefault(method)"
            />
            <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="removeMethod(method)" />
          </div>
        </div>
      </div>
    </template>

    <AppEmptyState
      v-else
      title="No payment methods"
      description="Add a payment method to manage your subscription."
      icon="pi pi-credit-card"
    />
  </div>
</template>
