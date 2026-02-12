<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'

const router = useRouter()
const route = useRoute()
const { login } = useAuth()
const toast = useToast()

const form = ref({
  email: '',
  password: '',
  remember: false,
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  try {
    await login(form.value)
    toast.success('Welcome back!')
    const redirect = (route.query.redirect as string) || '/app/dashboard'
    router.push(redirect)
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
        autofocus
      />
      <small v-if="errors.email" class="mt-1 text-red-500">{{ errors.email[0] }}</small>
    </div>

    <div>
      <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
      <Password
        id="password"
        v-model="form.password"
        placeholder="Enter your password"
        :feedback="false"
        toggle-mask
        class="w-full"
        input-class="w-full"
        :invalid="!!errors.password"
      />
      <small v-if="errors.password" class="mt-1 text-red-500">{{ errors.password[0] }}</small>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Checkbox v-model="form.remember" :binary="true" input-id="remember" />
        <label for="remember" class="text-sm text-gray-600">Remember me</label>
      </div>
      <RouterLink to="/forgot-password" class="text-sm font-medium text-primary-600 hover:text-primary-500">
        Forgot password?
      </RouterLink>
    </div>

    <Button type="submit" label="Sign in" :loading="loading" class="w-full" />

    <p class="text-center text-sm text-gray-600">
      Don't have an account?
      <RouterLink to="/register" class="font-medium text-primary-600 hover:text-primary-500">Sign up</RouterLink>
    </p>
  </form>
</template>
