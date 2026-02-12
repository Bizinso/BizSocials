<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'

const router = useRouter()
const { register } = useAuth()
const toast = useToast()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  try {
    await register(form.value)
    toast.success('Account created!', 'Please check your email to verify your account.')
    router.push('/app/dashboard')
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
      <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Full name</label>
      <InputText
        id="name"
        v-model="form.name"
        placeholder="John Doe"
        class="w-full"
        :invalid="!!errors.name"
        autofocus
      />
      <small v-if="errors.name" class="mt-1 text-red-500">{{ errors.name[0] }}</small>
    </div>

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
      <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
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

    <Button type="submit" label="Create account" :loading="loading" class="w-full" />

    <p class="text-center text-sm text-gray-600">
      Already have an account?
      <RouterLink to="/login" class="font-medium text-primary-600 hover:text-primary-500">Sign in</RouterLink>
    </p>
  </form>
</template>
