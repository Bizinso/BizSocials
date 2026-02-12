<script setup lang="ts">
import { ref } from 'vue'
import { billingApi } from '@/api/billing'
import { useBillingStore } from '@/stores/billing'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { PaymentMethodType } from '@/types/enums'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  added: []
}>()

const billingStore = useBillingStore()
const toast = useToast()
const saving = ref(false)

const paymentType = ref<PaymentMethodType>(PaymentMethodType.Card)
const token = ref('')

const typeOptions = [
  { label: 'Credit/Debit Card', value: PaymentMethodType.Card },
  { label: 'UPI', value: PaymentMethodType.Upi },
  { label: 'Net Banking', value: PaymentMethodType.Netbanking },
]

async function addMethod() {
  if (!token.value.trim()) return
  saving.value = true
  try {
    const method = await billingApi.addPaymentMethod({
      type: paymentType.value,
      token: token.value,
    })
    billingStore.addPaymentMethod(method)
    toast.success('Payment method added')
    emit('added')
    emit('update:visible', false)
    token.value = ''
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    header="Add Payment Method"
    :style="{ width: '450px' }"
    modal
    @update:visible="emit('update:visible', $event)"
  >
    <div class="space-y-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Payment Type</label>
        <Select
          v-model="paymentType"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          class="w-full"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Payment Token</label>
        <InputText
          v-model="token"
          placeholder="Token from payment gateway"
          class="w-full"
        />
        <p class="mt-1 text-xs text-gray-400">This will be replaced by Razorpay checkout integration.</p>
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" @click="emit('update:visible', false)" />
      <Button label="Add" icon="pi pi-plus" :disabled="!token.trim()" :loading="saving" @click="addMethod" />
    </template>
  </Dialog>
</template>
