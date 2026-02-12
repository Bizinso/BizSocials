<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import Menu from 'primevue/menu'
import AppAvatar from '@/components/shared/AppAvatar.vue'

const router = useRouter()
const { user, logout } = useAuth()
const menu = ref()

const menuItems = ref([
  {
    label: 'Profile',
    icon: 'pi pi-user',
    command: () => router.push('/app/settings/profile'),
  },
  {
    label: 'Settings',
    icon: 'pi pi-cog',
    command: () => router.push('/app/settings/tenant'),
  },
  { separator: true },
  {
    label: 'Logout',
    icon: 'pi pi-sign-out',
    command: async () => {
      await logout()
      router.push('/login')
    },
  },
])

function toggleMenu(event: Event) {
  menu.value.toggle(event)
}
</script>

<template>
  <button class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-gray-100" @click="toggleMenu">
    <AppAvatar :name="user?.name || ''" :src="user?.avatar_url" size="sm" />
    <span class="hidden text-sm font-medium text-gray-700 lg:block">{{ user?.name }}</span>
    <i class="pi pi-chevron-down hidden text-xs text-gray-400 lg:block" />
  </button>
  <Menu ref="menu" :model="menuItems" :popup="true" />
</template>
