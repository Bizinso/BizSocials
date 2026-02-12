<script setup lang="ts">
import { ref, computed } from 'vue'
import { whatsappApi } from '@/api/whatsapp'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppBusinessAccountData, WhatsAppPhoneNumberData } from '@/types/whatsapp'

const emit = defineEmits<{
  complete: [account: WhatsAppBusinessAccountData]
}>()

const toast = useToast()

const currentStep = ref(0)
const loading = ref(false)

// Step 1: Access token
const metaAccessToken = ref('')

// Step 2: Account (populated after onboard)
const account = ref<WhatsAppBusinessAccountData | null>(null)

// Step 3: Phone numbers
const phoneNumbers = ref<WhatsAppPhoneNumberData[]>([])

// Step 4: Profile
const profileForm = ref({
  description: '',
  address: '',
  website: '',
  support_email: '',
})

const steps = ['Connect Meta', 'Review Account', 'Phone Numbers', 'Business Profile', 'Compliance']
const canProceed = computed(() => {
  if (currentStep.value === 0) return metaAccessToken.value.length >= 10
  if (currentStep.value === 1) return !!account.value
  if (currentStep.value === 2) return phoneNumbers.value.length > 0
  if (currentStep.value === 3) return true
  return true
})

async function onboardAccount() {
  loading.value = true
  try {
    account.value = await whatsappApi.onboard({ meta_access_token: metaAccessToken.value })
    toast.success('WhatsApp Business Account connected')
    currentStep.value = 1
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

async function fetchPhoneNumbers() {
  if (!account.value) return
  loading.value = true
  try {
    phoneNumbers.value = await whatsappApi.getPhoneNumbers(account.value.id)
    currentStep.value = 2
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

async function updateProfile() {
  if (!account.value) return
  loading.value = true
  try {
    await whatsappApi.updateProfile(account.value.id, profileForm.value)
    toast.success('Business profile updated')
    currentStep.value = 4
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

async function acceptCompliance() {
  if (!account.value) return
  loading.value = true
  try {
    await whatsappApi.acceptCompliance(account.value.id)
    toast.success('Compliance accepted. Setup complete!')
    emit('complete', account.value)
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

function next() {
  if (currentStep.value === 0) return onboardAccount()
  if (currentStep.value === 1) return fetchPhoneNumbers()
  if (currentStep.value === 2) {
    currentStep.value = 3
    return
  }
  if (currentStep.value === 3) return updateProfile()
  if (currentStep.value === 4) return acceptCompliance()
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <!-- Step indicator -->
    <nav class="mb-8">
      <ol class="flex items-center">
        <li v-for="(step, i) in steps" :key="step" class="flex items-center" :class="{ 'flex-1': i < steps.length - 1 }">
          <div class="flex items-center gap-2">
            <span
              class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium"
              :class="i <= currentStep ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500'"
            >
              <i v-if="i < currentStep" class="pi pi-check text-xs" />
              <span v-else>{{ i + 1 }}</span>
            </span>
            <span class="hidden text-sm font-medium sm:inline" :class="i <= currentStep ? 'text-green-700' : 'text-gray-500'">
              {{ step }}
            </span>
          </div>
          <div v-if="i < steps.length - 1" class="mx-3 h-px flex-1 bg-gray-200" />
        </li>
      </ol>
    </nav>

    <!-- Step 0: Connect Meta -->
    <div v-if="currentStep === 0" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Connect your Meta Business Account</h2>
      <p class="text-sm text-gray-600">
        Enter a long-lived system user access token from your Meta Business Manager with
        <code class="rounded bg-gray-100 px-1 text-xs">whatsapp_business_management</code> and
        <code class="rounded bg-gray-100 px-1 text-xs">whatsapp_business_messaging</code> permissions.
      </p>
      <div>
        <label for="token" class="mb-1 block text-sm font-medium text-gray-700">Access Token</label>
        <textarea
          id="token"
          v-model="metaAccessToken"
          rows="3"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
          placeholder="EAAxxxxxx..."
        />
      </div>
    </div>

    <!-- Step 1: Review Account -->
    <div v-if="currentStep === 1 && account" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Account Connected</h2>
      <div class="rounded-lg border border-gray-200 p-4">
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">Business Name</dt>
            <dd class="font-medium text-gray-900">{{ account.name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">WABA ID</dt>
            <dd class="font-mono text-gray-700">{{ account.waba_id }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Status</dt>
            <dd class="capitalize text-gray-700">{{ account.status }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Quality Rating</dt>
            <dd class="capitalize text-gray-700">{{ account.quality_rating }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Step 2: Phone Numbers -->
    <div v-if="currentStep === 2" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Phone Numbers</h2>
      <p class="text-sm text-gray-600">The following phone numbers are registered with your WhatsApp Business Account.</p>
      <ul class="space-y-2">
        <li v-for="phone in phoneNumbers" :key="phone.id" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
            <i class="pi pi-phone text-green-600" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900">{{ phone.display_name }}</p>
            <p class="text-xs text-gray-500">{{ phone.phone_number }}</p>
          </div>
          <span v-if="phone.is_primary" class="ml-auto rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
            Primary
          </span>
        </li>
      </ul>
    </div>

    <!-- Step 3: Business Profile -->
    <div v-if="currentStep === 3" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Business Profile</h2>
      <p class="text-sm text-gray-600">This information is shown to customers on your WhatsApp profile.</p>
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="profileForm.description"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
            placeholder="Brief description of your business"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Address</label>
          <input
            v-model="profileForm.address"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
            placeholder="Business address"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Website</label>
          <input
            v-model="profileForm.website"
            type="url"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
            placeholder="https://example.com"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Support Email</label>
          <input
            v-model="profileForm.support_email"
            type="email"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
            placeholder="support@example.com"
          />
        </div>
      </div>
    </div>

    <!-- Step 4: Compliance -->
    <div v-if="currentStep === 4" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Accept WhatsApp Business Policies</h2>
      <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="mb-2 font-medium">By proceeding, you agree to:</p>
        <ul class="list-inside list-disc space-y-1">
          <li>WhatsApp Business Terms of Service</li>
          <li>WhatsApp Commerce Policy</li>
          <li>Meta Business Messaging Policy</li>
          <li>Only message users who have opted in</li>
          <li>Respect the 24-hour customer service window</li>
        </ul>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex justify-between">
      <button
        v-if="currentStep > 0"
        type="button"
        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        @click="currentStep--"
      >
        Back
      </button>
      <div v-else />
      <button
        type="button"
        class="rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-50"
        :disabled="!canProceed || loading"
        @click="next"
      >
        <i v-if="loading" class="pi pi-spin pi-spinner mr-1" />
        {{ currentStep === 4 ? 'Accept & Complete' : 'Continue' }}
      </button>
    </div>
  </div>
</template>
