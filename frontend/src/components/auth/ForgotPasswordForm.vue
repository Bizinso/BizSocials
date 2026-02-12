<script setup lang="ts">
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const toast = useToast()

const email = ref('')
const loading = ref(false)
const sent = ref(false)
const errors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  try {
    await authApi.forgotPassword({ email: email.value })
    sent.value = true
    toast.success('Reset link sent!', 'Check your email for a password reset link.')
  } catch (e) {
    const err = parseApiError(e)
    errors.value = err.errors
    if (!Object.keys(err.errors).length) {
      toast.error(err.message)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="sent" class="text-center">
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
      <i class="pi pi-check text-xl text-green-600" />
    </div>
    <h3 class="mb-2 text-lg font-semibold text-gray-900">Check your email</h3>
    <p class="mb-6 text-sm text-gray-500">
      We sent a password reset link to <strong>{{ email }}</strong>
    </p>
    <RouterLink to="/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
      Back to sign in
    </RouterLink>
  </div>

  <form v-else @submit.prevent="handleSubmit" class="space-y-5">
    <p class="text-sm text-gray-600">
      Enter your email address and we'll send you a link to reset your password.
    </p>

    <div>
      <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
      <InputText
        id="email"
        v-model="email"
        type="email"
        placeholder="you@company.com"
        class="w-full"
        :invalid="!!errors.email"
        autofocus
      />
      <small v-if="errors.email" class="mt-1 text-red-500">{{ errors.email[0] }}</small>
    </div>

    <Button type="submit" label="Send reset link" :loading="loading" class="w-full" />

    <p class="text-center text-sm text-gray-600">
      <RouterLink to="/login" class="font-medium text-primary-600 hover:text-primary-500">Back to sign in</RouterLink>
    </p>
  </form>
</template>
