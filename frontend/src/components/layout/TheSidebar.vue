<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
import { useWorkspaceStore } from '@/stores/workspace'
import AppLogo from '@/components/shared/AppLogo.vue'
import AppWorkspaceSwitcher from '@/components/shared/AppWorkspaceSwitcher.vue'
import TheSidebarMenu from './TheSidebarMenu.vue'

const uiStore = useUiStore()
const workspaceStore = useWorkspaceStore()
</script>

<template>
  <!-- Desktop sidebar -->
  <aside
    class="fixed left-0 top-0 z-40 hidden h-screen border-r border-gray-200 bg-white transition-all duration-300 lg:block"
    :class="uiStore.sidebarCollapsed ? 'w-[var(--sidebar-collapsed-width)]' : 'w-[var(--sidebar-width)]'"
  >
    <div class="flex h-[var(--topbar-height)] items-center border-b border-gray-200 px-4">
      <AppLogo v-if="!uiStore.sidebarCollapsed" class="h-7" />
      <AppLogo v-else class="h-7" icon-only />
    </div>
    <div class="overflow-y-auto p-3" :class="uiStore.sidebarCollapsed ? 'h-[calc(100vh-var(--topbar-height))]' : 'h-[calc(100vh-var(--topbar-height)-60px)]'">
      <TheSidebarMenu :collapsed="uiStore.sidebarCollapsed" />
    </div>
    <div v-if="!uiStore.sidebarCollapsed && workspaceStore.workspaces.length > 0" class="border-t border-gray-200 p-3">
      <AppWorkspaceSwitcher />
    </div>
  </aside>

  <!-- Mobile sidebar -->
  <aside
    class="fixed left-0 top-0 z-50 h-screen w-[var(--sidebar-width)] border-r border-gray-200 bg-white transition-transform duration-300 lg:hidden"
    :class="uiStore.sidebarMobileOpen ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-[var(--topbar-height)] items-center justify-between border-b border-gray-200 px-4">
      <AppLogo class="h-7" />
      <button class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" @click="uiStore.closeMobileSidebar()">
        <i class="pi pi-times text-lg" />
      </button>
    </div>
    <div class="overflow-y-auto p-3" :class="workspaceStore.workspaces.length > 0 ? 'h-[calc(100vh-var(--topbar-height)-60px)]' : 'h-[calc(100vh-var(--topbar-height))]'">
      <TheSidebarMenu :collapsed="false" />
    </div>
    <div v-if="workspaceStore.workspaces.length > 0" class="border-t border-gray-200 p-3">
      <AppWorkspaceSwitcher />
    </div>
  </aside>
</template>
