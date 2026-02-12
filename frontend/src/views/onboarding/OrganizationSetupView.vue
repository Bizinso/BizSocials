<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useOnboardingStore } from '@/stores/onboarding'
import AuthLayout from '@/layouts/AuthLayout.vue'

const router = useRouter()
const onboardingStore = useOnboardingStore()

const form = reactive({
  name: '',
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
  industry: '',
  country: '',
})

const localErrors = ref<Record<string, string>>({})
const submitted = ref(false)

function validateField(field: string): boolean {
  const errors: Record<string, string> = {}

  if (field === 'name' || field === 'all') {
    if (!form.name.trim()) errors.name = 'Organization name is required'
    else if (form.name.trim().length < 2) errors.name = 'Name must be at least 2 characters'
    else if (form.name.trim().length > 100) errors.name = 'Name must be 100 characters or less'
  }

  if (field === 'timezone' || field === 'all') {
    if (!form.timezone.trim()) errors.timezone = 'Timezone is required'
  }

  if (field === 'industry' || field === 'all') {
    if (!form.industry.trim()) errors.industry = 'Industry is required'
  }

  if (field === 'country' || field === 'all') {
    if (!form.country.trim()) errors.country = 'Country is required'
    else if (form.country.trim().length !== 2) errors.country = 'Country must be a 2-letter code'
  }

  if (field === 'all') {
    localErrors.value = errors
  } else {
    localErrors.value = { ...localErrors.value, ...errors }
    if (!errors[field]) {
      delete localErrors.value[field]
    }
  }

  return Object.keys(errors).length === 0
}

function getFieldError(field: string): string | undefined {
  return localErrors.value[field] || onboardingStore.fieldErrors[field]?.[0]
}

async function handleSubmit() {
  submitted.value = true

  if (!validateField('all')) return

  try {
    await onboardingStore.submitOrganization({
      name: form.name.trim(),
      timezone: form.timezone.trim(),
      industry: form.industry.trim(),
      country: form.country.trim().toUpperCase(),
    })
    router.push('/onboarding/workspace')
  } catch {
    // Error is handled by the store
  }
}
</script>

<template>
  <AuthLayout>
    <div class="mx-auto max-w-lg">
      <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
          <h2 class="text-2xl font-bold text-gray-900">Set up your organization</h2>
          <p class="mt-2 text-sm text-gray-600">
            Tell us about your organization to get started.
          </p>
        </div>

        <div
          v-if="onboardingStore.error && !Object.keys(onboardingStore.fieldErrors).length"
          class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
          role="alert"
        >
          {{ onboardingStore.error }}
        </div>

        <form @submit.prevent="handleSubmit" novalidate>
          <div class="space-y-4">
            <!-- Organization Name -->
            <div>
              <label for="org-name" class="mb-1 block text-sm font-medium text-gray-700">
                Organization name
              </label>
              <input
                id="org-name"
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="getFieldError('name') ? 'border-red-300' : 'border-gray-300'"
                placeholder="Acme Inc."
                @blur="validateField('name')"
              />
              <p v-if="getFieldError('name')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('name') }}
              </p>
            </div>

            <!-- Timezone -->
            <div>
              <label for="timezone" class="mb-1 block text-sm font-medium text-gray-700">
                Timezone
              </label>
              <select
                id="timezone"
                v-model="form.timezone"
                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="getFieldError('timezone') ? 'border-red-300' : 'border-gray-300'"
                @blur="validateField('timezone')"
              >
                <option value="" disabled>Select timezone</option>
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time (US)</option>
                <option value="America/Chicago">Central Time (US)</option>
                <option value="America/Denver">Mountain Time (US)</option>
                <option value="America/Los_Angeles">Pacific Time (US)</option>
                <option value="Europe/London">London (GMT)</option>
                <option value="Europe/Paris">Paris (CET)</option>
                <option value="Europe/Berlin">Berlin (CET)</option>
                <option value="Asia/Kolkata">India (IST)</option>
                <option value="Asia/Shanghai">China (CST)</option>
                <option value="Asia/Tokyo">Japan (JST)</option>
                <option value="Asia/Dubai">Dubai (GST)</option>
                <option value="Asia/Singapore">Singapore (SGT)</option>
                <option value="Australia/Sydney">Sydney (AEST)</option>
                <option value="Pacific/Auckland">Auckland (NZST)</option>
              </select>
              <p v-if="getFieldError('timezone')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('timezone') }}
              </p>
            </div>

            <!-- Industry -->
            <div>
              <label for="industry" class="mb-1 block text-sm font-medium text-gray-700">
                Industry
              </label>
              <select
                id="industry"
                v-model="form.industry"
                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="getFieldError('industry') ? 'border-red-300' : 'border-gray-300'"
                @blur="validateField('industry')"
              >
                <option value="" disabled>Select your industry</option>
                <option value="technology">Technology</option>
                <option value="healthcare">Healthcare</option>
                <option value="finance">Finance & Banking</option>
                <option value="education">Education</option>
                <option value="retail">Retail & E-commerce</option>
                <option value="manufacturing">Manufacturing</option>
                <option value="media">Media & Entertainment</option>
                <option value="real_estate">Real Estate</option>
                <option value="travel">Travel & Hospitality</option>
                <option value="food">Food & Beverage</option>
                <option value="professional_services">Professional Services</option>
                <option value="nonprofit">Non-profit</option>
                <option value="other">Other</option>
              </select>
              <p v-if="getFieldError('industry')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('industry') }}
              </p>
            </div>

            <!-- Country -->
            <div>
              <label for="country" class="mb-1 block text-sm font-medium text-gray-700">
                Country
              </label>
              <select
                id="country"
                v-model="form.country"
                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="getFieldError('country') ? 'border-red-300' : 'border-gray-300'"
                @blur="validateField('country')"
              >
                <option value="" disabled>Select your country</option>
                <option value="US">United States</option>
                <option value="GB">United Kingdom</option>
                <option value="CA">Canada</option>
                <option value="AU">Australia</option>
                <option value="DE">Germany</option>
                <option value="FR">France</option>
                <option value="IN">India</option>
                <option value="JP">Japan</option>
                <option value="SG">Singapore</option>
                <option value="AE">United Arab Emirates</option>
                <option value="BR">Brazil</option>
                <option value="NL">Netherlands</option>
                <option value="NZ">New Zealand</option>
              </select>
              <p v-if="getFieldError('country')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('country') }}
              </p>
            </div>
          </div>

          <button
            type="submit"
            class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="onboardingStore.loading"
          >
            <span v-if="onboardingStore.loading">Saving...</span>
            <span v-else>Continue</span>
          </button>
        </form>
      </div>
    </div>
  </AuthLayout>
</template>
