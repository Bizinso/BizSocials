<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { post } from '@/api/client'
import { parseApiError } from '@/utils/error-handler'
import BlankLayout from '@/layouts/BlankLayout.vue'
import AppLoadingSpinner from '@/components/shared/AppLoadingSpinner.vue'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const { isAuthenticated } = useAuth()
const toast = useToast()

const loading = ref(false)
const accepted = ref(false)
const errorMessage = ref('')
const invitationToken = route.params.token as string

onMounted(() => {
  if (!isAuthenticated.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
  }
})

async function acceptInvitation() {
  loading.value = true
  try {
    await post(`/invitations/${invitationToken}/accept`)
    accepted.value = true
    toast.success('Invitation accepted!')
  } catch (e) {
    const err = parseApiError(e)
    errorMessage.value = err.message
    toast.error(err.message)
  } finally {
    loading.value = false
  }
}

async function declineInvitation() {
  loading.value = true
  try {
    await post(`/invitations/${invitationToken}/decline`)
    toast.info('Invitation declined.')
    router.push('/app/dashboard')
  } catch (e) {
    const err = parseApiError(e)
    toast.error(err.message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <BlankLayout>
    <div class="flex min-h-screen items-center justify-center px-4">
      <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <template v-if="accepted">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
            <i class="pi pi-check text-2xl text-green-600" />
          </div>
          <h2 class="mb-2 text-xl font-bold text-gray-900">You're in!</h2>
          <p class="mb-6 text-sm text-gray-500">You've successfully joined the team.</p>
          <Button label="Go to dashboard" @click="router.push('/app/dashboard')" />
        </template>

        <template v-else-if="errorMessage">
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
            <i class="pi pi-times text-2xl text-red-600" />
          </div>
          <h2 class="mb-2 text-xl font-bold text-gray-900">Something went wrong</h2>
          <p class="mb-6 text-sm text-gray-500">{{ errorMessage }}</p>
          <Button label="Back to dashboard" @click="router.push('/app/dashboard')" />
        </template>

        <template v-else>
          <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-100">
            <i class="pi pi-envelope text-2xl text-primary-600" />
          </div>
          <h2 class="mb-2 text-xl font-bold text-gray-900">Team Invitation</h2>
          <p class="mb-6 text-sm text-gray-500">You've been invited to join a team on BizSocials.</p>
          <div class="flex gap-3 justify-center">
            <Button label="Decline" severity="secondary" outlined :loading="loading" @click="declineInvitation" />
            <Button label="Accept invitation" :loading="loading" @click="acceptInvitation" />
          </div>
        </template>
      </div>
    </div>
  </BlankLayout>
</template>
