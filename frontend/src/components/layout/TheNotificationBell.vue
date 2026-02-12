<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'
import Badge from 'primevue/badge'
import Popover from 'primevue/popover'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

const router = useRouter()
const notificationStore = useNotificationStore()
const popover = ref()

function toggle(event: Event) {
  popover.value?.toggle(event)
  if (!popover.value?.visible) {
    notificationStore.fetchRecent()
  }
}

function handleClick(notification: { id: string; action_url: string | null; is_read: boolean }) {
  if (!notification.is_read) {
    notificationStore.markAsRead(notification.id)
  }
  popover.value?.hide()
  if (notification.action_url) {
    router.push(notification.action_url)
  }
}

function handleMarkAllAsRead() {
  notificationStore.markAllAsRead()
}

function viewAll() {
  popover.value?.hide()
  router.push({ name: 'notifications' })
}

function formatTime(date: string) {
  return dayjs(date).fromNow()
}

onMounted(() => {
  notificationStore.fetchUnreadCount()
})
</script>

<template>
  <button
    class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100"
    aria-label="Notifications"
    @click="toggle"
  >
    <i class="pi pi-bell text-lg" />
    <Badge
      v-if="notificationStore.unreadCount > 0"
      :value="notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount"
      severity="danger"
      class="absolute -right-0.5 -top-0.5"
    />
  </button>

  <Popover ref="popover" class="w-80">
    <div class="max-h-96 overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between border-b px-4 py-3">
        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
        <button
          v-if="notificationStore.unreadCount > 0"
          class="text-xs text-indigo-600 hover:text-indigo-800"
          @click="handleMarkAllAsRead"
        >
          Mark all as read
        </button>
      </div>

      <!-- Notification list -->
      <div class="max-h-72 overflow-y-auto">
        <template v-if="notificationStore.recent.length > 0">
          <div
            v-for="item in notificationStore.recent"
            :key="item.id"
            class="cursor-pointer border-b border-gray-50 px-4 py-3 transition-colors hover:bg-gray-50"
            :class="{ 'bg-indigo-50/50': !item.is_read }"
            @click="handleClick(item)"
          >
            <div class="flex items-start gap-3">
              <div
                v-if="!item.is_read"
                class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500"
              />
              <div v-else class="mt-1.5 h-2 w-2 flex-shrink-0" />
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900">{{ item.title }}</p>
                <p class="mt-0.5 line-clamp-2 text-xs text-gray-500">{{ item.message }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ formatTime(item.created_at) }}</p>
              </div>
            </div>
          </div>
        </template>
        <div v-else class="px-4 py-8 text-center text-sm text-gray-400">
          No notifications yet
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-4 py-2 text-center">
        <button
          class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
          @click="viewAll"
        >
          View all notifications
        </button>
      </div>
    </div>
  </Popover>
</template>
