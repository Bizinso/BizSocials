<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
import TheNotificationBell from './TheNotificationBell.vue'
import TheUserMenu from './TheUserMenu.vue'
import TheBreadcrumb from './TheBreadcrumb.vue'

const uiStore = useUiStore()
</script>

<template>
  <header class="sticky top-0 z-20 flex h-[var(--topbar-height)] items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
    <div class="flex items-center gap-3">
      <!-- Mobile menu toggle -->
      <button class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden" @click="uiStore.toggleMobileSidebar()">
        <i class="pi pi-bars text-lg" />
      </button>

      <!-- Desktop sidebar toggle -->
      <button class="hidden rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 lg:block" @click="uiStore.toggleSidebar()">
        <i class="pi pi-bars text-lg" />
      </button>

      <TheBreadcrumb />
    </div>

    <div class="flex items-center gap-2">
      <TheNotificationBell />
      <TheUserMenu />
    </div>
  </header>
</template>
