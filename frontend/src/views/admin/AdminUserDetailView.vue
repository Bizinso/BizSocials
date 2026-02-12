<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminUsersApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { formatDate } from '@/utils/formatters'
import type { AdminUserData } from '@/types/admin'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const user = ref<AdminUserData | null>(null)
const loading = ref(true)

async function loadUser(id: string) {
  loading.value = true
  try {
    user.value = await adminUsersApi.get(id)
  } finally {
    loading.value = false
  }
}

watch(() => route.params.userId as string, (id) => { if (id) loadUser(id) }, { immediate: true })

async function suspendUser() {
  if (!user.value) return
  try {
    await adminUsersApi.suspend(user.value.id, { reason: 'Admin action' })
    toast.success('User suspended')
    loadUser(user.value.id)
  } catch { toast.error('Failed to suspend user') }
}

async function activateUser() {
  if (!user.value) return
  try {
    await adminUsersApi.activate(user.value.id)
    toast.success('User activated')
    loadUser(user.value.id)
  } catch { toast.error('Failed to activate user') }
}

async function resetPassword() {
  if (!user.value) return
  try {
    await adminUsersApi.resetPassword(user.value.id)
    toast.success('Password reset email sent')
  } catch { toast.error('Failed to send reset email') }
}

function statusSeverity(s: string) {
  if (s === 'active') return 'success'
  if (s === 'suspended') return 'danger'
  if (s === 'pending') return 'warn'
  return 'secondary'
}
</script>

<template>
  <div>
    <Button label="Back" icon="pi pi-arrow-left" severity="secondary" text size="small" class="mb-4" @click="router.push({ name: 'admin-users' })" />

    <AppLoadingSkeleton v-if="loading" :lines="8" />
    <template v-else-if="user">
      <div class="mb-6 flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ user.name }}</h1>
          <p class="text-sm text-gray-500">{{ user.email }}</p>
          <div class="mt-2 flex items-center gap-3">
            <Tag :value="user.status_label" :severity="statusSeverity(user.status)" />
            <span class="text-sm text-gray-500">{{ user.role_label }}</span>
          </div>
        </div>
        <div class="flex gap-2">
          <Button label="Reset Password" severity="secondary" size="small" @click="resetPassword" />
          <Button v-if="user.status === 'active'" label="Suspend" severity="danger" size="small" @click="suspendUser" />
          <Button v-if="user.status === 'suspended'" label="Activate" severity="success" size="small" @click="activateUser" />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
        <div>
          <p class="text-sm text-gray-500">Tenant</p>
          <p class="font-medium">{{ user.tenant_name || '—' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">MFA</p>
          <p class="font-medium">{{ user.mfa_enabled ? 'Enabled' : 'Disabled' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Timezone</p>
          <p class="font-medium">{{ user.timezone || '—' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Last Login</p>
          <p class="font-medium">{{ user.last_login_at ? formatDate(user.last_login_at) : '—' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Email Verified</p>
          <p class="font-medium">{{ user.email_verified_at ? formatDate(user.email_verified_at) : 'Not verified' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Joined</p>
          <p class="font-medium">{{ formatDate(user.created_at) }}</p>
        </div>
      </div>
    </template>
  </div>
</template>
