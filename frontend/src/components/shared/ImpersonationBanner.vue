<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

function exitImpersonation() {
  authStore.exitImpersonation()
}
</script>

<template>
  <div
    v-if="authStore.isImpersonating"
    class="fixed inset-x-0 top-0 z-50 flex items-center justify-between bg-amber-500 px-4 py-2 text-sm font-medium text-amber-950"
  >
    <div class="flex items-center gap-2">
      <i class="pi pi-exclamation-triangle" />
      <span>
        You are impersonating
        <strong>{{ authStore.impersonatedTenantName ?? 'a tenant' }}</strong>
      </span>
    </div>
    <button
      class="rounded bg-amber-700 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-800"
      @click="exitImpersonation"
    >
      Exit Impersonation
    </button>
  </div>
</template>
