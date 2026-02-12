<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLogo from '@/components/shared/AppLogo.vue'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()
const collapsed = ref(false)

interface AdminMenuItem {
  label: string
  icon: string
  to: string
}

const menuItems: AdminMenuItem[] = [
  { label: 'Dashboard', icon: 'pi pi-home', to: '/admin/dashboard' },
  { label: 'Tenants', icon: 'pi pi-building', to: '/admin/tenants' },
  { label: 'Users', icon: 'pi pi-users', to: '/admin/users' },
  { label: 'Plans', icon: 'pi pi-box', to: '/admin/plans' },
  { label: 'Feature Flags', icon: 'pi pi-flag', to: '/admin/feature-flags' },
  { label: 'Integrations', icon: 'pi pi-link', to: '/admin/integrations' },
  { label: 'Config', icon: 'pi pi-sliders-h', to: '/admin/config' },
  { label: 'KB Articles', icon: 'pi pi-book', to: '/admin/kb' },
  { label: 'Feedback', icon: 'pi pi-comments', to: '/admin/feedback' },
  { label: 'Support', icon: 'pi pi-ticket', to: '/admin/support' },
  { label: 'Privacy', icon: 'pi pi-shield', to: '/admin/privacy' },
]

function isActive(item: AdminMenuItem): boolean {
  return route.path.startsWith(item.to)
}

const sidebarWidth = computed(() => (collapsed.value ? 'w-16' : 'w-60'))
const mainMargin = computed(() => (collapsed.value ? 'ml-16' : 'ml-60'))
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Admin Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 border-r border-gray-200 bg-gray-900 transition-all duration-300"
      :class="sidebarWidth"
    >
      <div class="flex h-16 items-center justify-between px-4">
        <div v-if="!collapsed" class="flex items-center gap-2">
          <AppLogo class="h-7 w-auto brightness-200" />
          <span class="text-xs font-semibold text-gray-400">ADMIN</span>
        </div>
        <Button
          :icon="collapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"
          severity="secondary"
          text
          size="small"
          class="!text-gray-400"
          @click="collapsed = !collapsed"
        />
      </div>

      <nav class="mt-2 space-y-1 px-2">
        <button
          v-for="item in menuItems"
          :key="item.to"
          class="flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
          :class="[
            isActive(item)
              ? 'bg-gray-800 text-white'
              : 'text-gray-400 hover:bg-gray-800 hover:text-white',
          ]"
          :title="collapsed ? item.label : undefined"
          @click="router.push(item.to)"
        >
          <i :class="item.icon" class="text-base" />
          <span v-if="!collapsed" class="ml-3">{{ item.label }}</span>
        </button>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="transition-all duration-300" :class="mainMargin">
      <!-- Admin Topbar -->
      <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
        <h1 class="text-lg font-semibold text-gray-900">Admin Panel</h1>
        <Button
          label="Back to App"
          icon="pi pi-arrow-left"
          severity="secondary"
          size="small"
          @click="router.push('/app/dashboard')"
        />
      </header>

      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
