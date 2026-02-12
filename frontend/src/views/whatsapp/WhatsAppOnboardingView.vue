<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWhatsAppStore } from '@/stores/whatsapp'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import WhatsAppOnboardingWizard from '@/components/whatsapp/WhatsAppOnboardingWizard.vue'
import type { WhatsAppBusinessAccountData } from '@/types/whatsapp'

const route = useRoute()
const router = useRouter()
const whatsappStore = useWhatsAppStore()

const workspaceId = computed(() => route.params.workspaceId as string)

onMounted(() => {
  whatsappStore.fetchAccounts()
})

function onComplete(account: WhatsAppBusinessAccountData) {
  router.push(`/app/w/${workspaceId.value}/whatsapp/inbox`)
}
</script>

<template>
  <AppPageHeader
    title="WhatsApp Setup"
    description="Connect your WhatsApp Business Account to start messaging customers"
  />

  <div class="space-y-4">
    <!-- Already connected accounts -->
    <AppCard v-if="whatsappStore.hasAccounts">
      <h3 class="mb-3 text-sm font-semibold text-gray-900">Connected Accounts</h3>
      <ul class="space-y-2">
        <li
          v-for="acc in whatsappStore.accounts"
          :key="acc.id"
          class="flex items-center gap-3 rounded-lg border border-gray-200 p-3"
        >
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
            <i class="pi pi-whatsapp text-lg text-green-600" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900">{{ acc.name }}</p>
            <p class="text-xs text-gray-500">WABA: {{ acc.waba_id }} &middot; {{ acc.status }}</p>
          </div>
          <span
            class="rounded-full px-2 py-0.5 text-xs font-medium"
            :class="acc.status === 'verified' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
          >
            {{ acc.status }}
          </span>
        </li>
      </ul>
    </AppCard>

    <!-- Onboarding wizard -->
    <AppCard>
      <WhatsAppOnboardingWizard @complete="onComplete" />
    </AppCard>
  </div>
</template>
