<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const form = ref({
  email: (route.query.email as string) || '',
  token: route.params.token as string,
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  try {
    await authApi.resetPassword(form.value)
    toast.success('Password reset!', 'You can now sign in with your new password.')
    router.push('/login')
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
  <form @submit.prevent="handleSubmit" class="space-y-5">
    <div>
      <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
      <InputText
        id="email"
        v-model="form.email"
        type="email"
        placeholder="you@company.com"
        class="w-full"
        :invalid="!!errors.email"
      />
      <small v-if="errors.email" class="mt-1 text-red-500">{{ errors.email[0] }}</small>
    </div>

    <div>
      <label for="password" class="mb-1 block text-sm font-medium text-gray-700">New password</label>
      <Password
        id="password"
        v-model="form.password"
        placeholder="Min. 8 characters"
        toggle-mask
        class="w-full"
        input-class="w-full"
        :invalid="!!errors.password"
      />
      <small v-if="errors.password" class="mt-1 text-red-500">{{ errors.password[0] }}</small>
    </div>

    <div>
      <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm password</label>
      <Password
        id="password_confirmation"
        v-model="form.password_confirmation"
        placeholder="Repeat your password"
        :feedback="false"
        toggle-mask
        class="w-full"
        input-class="w-full"
      />
    </div>

    <Button type="submit" label="Reset password" :loading="loading" class="w-full" />
  </form>
</template>
