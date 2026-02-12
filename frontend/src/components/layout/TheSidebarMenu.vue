<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWorkspaceStore } from '@/stores/workspace'

defineProps<{
  collapsed: boolean
}>()

const route = useRoute()
const router = useRouter()
const workspaceStore = useWorkspaceStore()

interface MenuItem {
  label: string
  icon: string
  to?: string
  separator?: boolean
  children?: MenuItem[]
}

const wsId = computed(() => workspaceStore.currentWorkspaceId)

const menuItems = computed<MenuItem[]>(() => {
  const items: MenuItem[] = [
    { label: 'Dashboard', icon: 'pi pi-home', to: '/app/dashboard' },
    { label: 'Workspaces', icon: 'pi pi-th-large', to: '/app/workspaces' },
  ]

  if (wsId.value) {
    const prefix = `/app/w/${wsId.value}`
    items.push(
      { label: '', icon: '', separator: true },
      { label: 'Posts', icon: 'pi pi-file-edit', to: `${prefix}/posts` },
      { label: 'Calendar', icon: 'pi pi-calendar', to: `${prefix}/calendar` },
      { label: 'Inbox', icon: 'pi pi-inbox', to: `${prefix}/inbox` },
      { label: 'Social Accounts', icon: 'pi pi-share-alt', to: `${prefix}/social-accounts` },
      { label: 'Approvals', icon: 'pi pi-check-circle', to: `${prefix}/approvals` },
      { label: 'Analytics', icon: 'pi pi-chart-bar', to: `${prefix}/analytics` },
      { label: 'Reports', icon: 'pi pi-file-pdf', to: `${prefix}/reports` },
      { label: '', icon: '', separator: true },
      { label: 'Media Library', icon: 'pi pi-images', to: `${prefix}/media-library` },
      { label: 'Categories', icon: 'pi pi-tag', to: `${prefix}/categories` },
      { label: 'Short Links', icon: 'pi pi-link', to: `${prefix}/short-links` },
      { label: 'RSS Feeds', icon: 'pi pi-rss', to: `${prefix}/rss-feeds` },
      { label: 'Evergreen', icon: 'pi pi-replay', to: `${prefix}/evergreen` },
      { label: 'Tasks', icon: 'pi pi-list-check', to: `${prefix}/tasks` },
      { label: 'Workflows', icon: 'pi pi-sitemap', to: `${prefix}/approval-workflows` },
      { label: 'Saved Replies', icon: 'pi pi-comment', to: `${prefix}/saved-replies` },
      { label: 'CRM Contacts', icon: 'pi pi-id-card', to: `${prefix}/inbox-contacts` },
      { label: 'Listening', icon: 'pi pi-volume-up', to: `${prefix}/listening` },
      { label: 'Webhooks', icon: 'pi pi-arrows-h', to: `${prefix}/webhooks` },
      { label: 'Image Editor', icon: 'pi pi-image', to: `${prefix}/image-editor` },
      { label: '', icon: '', separator: true },
      { label: 'WhatsApp', icon: 'pi pi-whatsapp', to: `${prefix}/whatsapp/inbox` },
      { label: 'WA Contacts', icon: 'pi pi-users', to: `${prefix}/whatsapp/contacts` },
      { label: 'WA Templates', icon: 'pi pi-file-edit', to: `${prefix}/whatsapp/templates` },
      { label: 'WA Campaigns', icon: 'pi pi-megaphone', to: `${prefix}/whatsapp/campaigns` },
      { label: 'WA Automation', icon: 'pi pi-bolt', to: `${prefix}/whatsapp/automation` },
      { label: 'WA Replies', icon: 'pi pi-reply', to: `${prefix}/whatsapp/quick-replies` },
      { label: 'WA Analytics', icon: 'pi pi-chart-line', to: `${prefix}/whatsapp/analytics` },
    )
  }

  items.push(
    { label: '', icon: '', separator: true },
    { label: 'Support', icon: 'pi pi-ticket', to: '/app/support' },
    { label: 'Billing', icon: 'pi pi-credit-card', to: '/app/billing' },
    { label: 'Settings', icon: 'pi pi-cog', to: '/app/settings' },
  )

  return items
})

function isActive(item: MenuItem): boolean {
  if (!item.to) return false
  return route.path.startsWith(item.to)
}

function navigate(item: MenuItem) {
  if (item.to) {
    router.push(item.to)
  }
}
</script>

<template>
  <ul class="space-y-1">
    <li v-for="(item, index) in menuItems" :key="item.label || `sep-${index}`">
      <hr v-if="item.separator" class="my-2 border-gray-200" />
      <button
        v-else
        class="flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
        :class="[
          isActive(item)
            ? 'bg-primary-50 text-primary-700'
            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900',
        ]"
        :title="collapsed ? item.label : undefined"
        @click="navigate(item)"
      >
        <i :class="item.icon" class="text-base" />
        <span v-if="!collapsed" class="ml-3">{{ item.label }}</span>
      </button>
    </li>
  </ul>
</template>
