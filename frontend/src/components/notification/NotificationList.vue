<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { notificationsApi } from '@/api/notifications'
import { useNotificationStore } from '@/stores/notification'
import { useRouter } from 'vue-router'
import type { NotificationData } from '@/types/notification'
import type { PaginationMeta } from '@/types/api'
import NotificationItem from './NotificationItem.vue'
import AppEmptyState from '@/components/shared/AppEmptyState.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'

const router = useRouter()
const notificationStore = useNotificationStore()

const notifications = ref<NotificationData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)

onMounted(() => fetchNotifications())

async function fetchNotifications(page = 1) {
  loading.value = true
  try {
    const response = await notificationsApi.list({ page })
    notifications.value = response.data
    pagination.value = response.meta
  } finally {
    loading.value = false
  }
}

async function onNotificationClick(notification: NotificationData) {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id)
    notification.is_read = true
  }
  if (notification.action_url) {
    router.push(notification.action_url)
  }
}

async function markAllRead() {
  await notificationStore.markAllAsRead()
  notifications.value.forEach((n) => (n.is_read = true))
}

function onPageChange(event: any) {
  fetchNotifications(event.page + 1)
}
</script>

<template>
  <div>
    <div v-if="notifications.length > 0" class="mb-3 flex justify-end">
      <Button label="Mark all as read" severity="secondary" size="small" text @click="markAllRead" />
    </div>

    <AppLoadingSkeleton v-if="loading" :lines="3" :count="5" />

    <template v-else-if="notifications.length > 0">
      <div class="overflow-hidden rounded-lg border border-gray-200">
        <NotificationItem
          v-for="n in notifications"
          :key="n.id"
          :notification="n"
          @click="onNotificationClick"
        />
      </div>

      <Paginator
        v-if="pagination && pagination.last_page > 1"
        :rows="pagination.per_page"
        :total-records="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page"
        class="mt-4"
        @page="onPageChange"
      />
    </template>

    <AppEmptyState
      v-else
      title="No notifications"
      description="You're all caught up!"
      icon="pi pi-bell"
    />
  </div>
</template>
