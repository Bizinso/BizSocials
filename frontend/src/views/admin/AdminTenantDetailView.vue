<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminTenantsApi } from '@/api/admin'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/formatters'
import type { AdminTenantData } from '@/types/admin'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { confirmDelete } = useConfirm()
const tenant = ref<AdminTenantData | null>(null)
const loading = ref(true)
const suspendReason = ref('')
const showSuspendForm = ref(false)

async function loadTenant(id: string) {
  loading.value = true
  try {
    tenant.value = await adminTenantsApi.get(id)
  } finally {
    loading.value = false
  }
}

watch(() => route.params.tenantId as string, (id) => { if (id) loadTenant(id) }, { immediate: true })

async function suspendTenant() {
  if (!tenant.value || !suspendReason.value) return
  try {
    await adminTenantsApi.suspend(tenant.value.id, { reason: suspendReason.value })
    toast.success('Tenant suspended')
    loadTenant(tenant.value.id)
    showSuspendForm.value = false
  } catch { toast.error('Failed to suspend tenant') }
}

async function activateTenant() {
  if (!tenant.value) return
  try {
    await adminTenantsApi.activate(tenant.value.id)
    toast.success('Tenant activated')
    loadTenant(tenant.value.id)
  } catch { toast.error('Failed to activate tenant') }
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
    <Button label="Back" icon="pi pi-arrow-left" severity="secondary" text size="small" class="mb-4" @click="router.push({ name: 'admin-tenants' })" />

    <AppLoadingSkeleton v-if="loading" :lines="10" />
    <template v-else-if="tenant">
      <div class="mb-6 flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ tenant.name }}</h1>
          <div class="mt-2 flex items-center gap-3">
            <Tag :value="tenant.status_label" :severity="statusSeverity(tenant.status)" />
            <span class="text-sm text-gray-500">{{ tenant.type_label }}</span>
            <span v-if="tenant.plan_name" class="text-sm text-gray-500">Plan: {{ tenant.plan_name }}</span>
          </div>
        </div>
        <div class="flex gap-2">
          <Button v-if="tenant.status === 'active'" label="Suspend" severity="danger" size="small" @click="showSuspendForm = true" />
          <Button v-if="tenant.status === 'suspended'" label="Activate" severity="success" size="small" @click="activateTenant" />
        </div>
      </div>

      <!-- Suspend Form -->
      <div v-if="showSuspendForm" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
        <p class="mb-2 text-sm font-medium text-red-700">Suspend this tenant</p>
        <div class="flex gap-2">
          <InputText v-model="suspendReason" placeholder="Reason for suspension..." class="flex-1" />
          <Button label="Confirm" severity="danger" size="small" @click="suspendTenant" />
          <Button label="Cancel" severity="secondary" size="small" @click="showSuspendForm = false" />
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
        <div>
          <p class="text-sm text-gray-500">Users</p>
          <p class="text-lg font-semibold">{{ tenant.user_count }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Workspaces</p>
          <p class="text-lg font-semibold">{{ tenant.workspace_count }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Created</p>
          <p class="text-lg font-semibold">{{ formatDate(tenant.created_at) }}</p>
        </div>
        <div v-if="tenant.trial_ends_at">
          <p class="text-sm text-gray-500">Trial Ends</p>
          <p class="text-lg font-semibold">{{ formatDate(tenant.trial_ends_at) }}</p>
        </div>
        <div v-if="tenant.suspension_reason">
          <p class="text-sm text-gray-500">Suspension Reason</p>
          <p class="text-sm text-red-600">{{ tenant.suspension_reason }}</p>
        </div>
      </div>
    </template>
  </div>
</template>
