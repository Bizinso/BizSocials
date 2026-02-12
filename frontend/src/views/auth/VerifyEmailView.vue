<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import BlankLayout from '@/layouts/BlankLayout.vue'
import AppLoadingSpinner from '@/components/shared/AppLoadingSpinner.vue'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const success = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  try {
    const id = route.params.id as string
    const hash = route.params.hash as string
    await authApi.verifyEmail(id, hash)
    success.value = true
  } catch {
    errorMessage.value = 'This verification link is invalid or has expired.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <BlankLayout>
    <div class="flex min-h-screen items-center justify-center px-4">
      <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <AppLoadingSpinner v-if="loading" label="Verifying your email..." />

        <template v-else-if="success">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
            <i class="pi pi-check text-2xl text-green-600" />
          </div>
          <h2 class="mb-2 text-xl font-bold text-gray-900">Email verified!</h2>
          <p class="mb-6 text-sm text-gray-500">Your email address has been successfully verified.</p>
          <Button label="Go to dashboard" @click="router.push('/app/dashboard')" />
        </template>

        <template v-else>
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
            <i class="pi pi-times text-2xl text-red-600" />
          </div>
          <h2 class="mb-2 text-xl font-bold text-gray-900">Verification failed</h2>
          <p class="mb-6 text-sm text-gray-500">{{ errorMessage }}</p>
          <Button label="Back to login" @click="router.push('/login')" />
        </template>
      </div>
    </div>
  </BlankLayout>
</template>
