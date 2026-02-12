<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import { useEcho } from '@/composables/useEcho'
import TheSidebar from '@/components/layout/TheSidebar.vue'
import TheTopbar from '@/components/layout/TheTopbar.vue'
import ImpersonationBanner from '@/components/shared/ImpersonationBanner.vue'

const uiStore = useUiStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const { connect, disconnect } = useEcho()

const mainClass = computed(() => ({
  'ml-[var(--sidebar-width)]': !uiStore.sidebarCollapsed,
  'ml-[var(--sidebar-collapsed-width)]': uiStore.sidebarCollapsed,
}))

onMounted(() => {
  if (authStore.isAuthenticated) {
    try {
      connect()
      notificationStore.setWebSocketActive(true)
    } catch {
      // Fallback to polling if WebSocket connection fails
      notificationStore.startPolling()
    }
  }
})

onUnmounted(() => {
  disconnect()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <ImpersonationBanner />
    <TheSidebar />
    <div class="transition-all duration-300 lg:block" :class="mainClass">
      <TheTopbar />
      <main class="p-6">
        <slot />
      </main>
    </div>

    <!-- Mobile overlay -->
    <div
      v-if="uiStore.sidebarMobileOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="uiStore.closeMobileSidebar()"
    />
  </div>
</template>
