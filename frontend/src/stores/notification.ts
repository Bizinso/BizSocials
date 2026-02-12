import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notificationsApi } from '@/api/notifications'
import type { NotificationData } from '@/types/notification'

const POLL_INTERVAL = 30_000 // 30 seconds (fallback when WebSocket unavailable)

export const useNotificationStore = defineStore('notification', () => {
  const unreadCount = ref(0)
  const recent = ref<NotificationData[]>([])
  const pollTimer = ref<ReturnType<typeof setInterval> | null>(null)
  const usingWebSocket = ref(false)

  function setUnreadCount(count: number) {
    unreadCount.value = count
  }

  function decrement() {
    if (unreadCount.value > 0) {
      unreadCount.value--
    }
  }

  async function fetchUnreadCount() {
    try {
      const data = await notificationsApi.unreadCount()
      unreadCount.value = data.count
    } catch {
      // silently fail on polling errors
    }
  }

  async function fetchRecent() {
    try {
      recent.value = await notificationsApi.recent()
    } catch {
      // silently fail
    }
  }

  async function markAsRead(notificationId: string) {
    await notificationsApi.markAsRead(notificationId)
    const item = recent.value.find((n) => n.id === notificationId)
    if (item && !item.is_read) {
      item.is_read = true
      decrement()
    }
  }

  async function markAllAsRead() {
    await notificationsApi.markAllAsRead()
    unreadCount.value = 0
    recent.value.forEach((n) => (n.is_read = true))
  }

  /** Mark that WebSocket is active — disables polling fallback */
  function setWebSocketActive(active: boolean) {
    usingWebSocket.value = active
    if (active) {
      stopPolling()
    }
  }

  /** Start polling as fallback (only when WebSocket is not active) */
  function startPolling() {
    if (usingWebSocket.value) return
    stopPolling()
    fetchUnreadCount()
    pollTimer.value = setInterval(fetchUnreadCount, POLL_INTERVAL)
  }

  function stopPolling() {
    if (pollTimer.value) {
      clearInterval(pollTimer.value)
      pollTimer.value = null
    }
  }

  function clear() {
    stopPolling()
    unreadCount.value = 0
    recent.value = []
    usingWebSocket.value = false
  }

  return {
    unreadCount,
    recent,
    usingWebSocket,
    setUnreadCount,
    decrement,
    fetchUnreadCount,
    fetchRecent,
    markAsRead,
    markAllAsRead,
    setWebSocketActive,
    startPolling,
    stopPolling,
    clear,
  }
})
