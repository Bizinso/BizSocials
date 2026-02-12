import { onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'
import { useWorkspaceStore } from '@/stores/workspace'
import { createEcho, disconnectEcho, getEcho } from '@/plugins/echo'
import type { NotificationData } from '@/types/notification'

interface BroadcastNotification {
  id: string
  type: string
  title: string
  message: string
  action_url: string | null
  created_at: string
}

interface BroadcastPostStatus {
  post_id: string
  title: string
  status: string
  previous_status: string
  updated_at: string
}

interface BroadcastInboxItem {
  id: string
  platform: string
  type: string
  author_name: string
  content_preview: string
  created_at: string
}

export function useEcho() {
  const authStore = useAuthStore()
  const notificationStore = useNotificationStore()
  const workspaceStore = useWorkspaceStore()

  let userChannelBound = false
  let workspaceChannelId: string | null = null

  function connect() {
    const token = authStore.token
    if (!token) return

    const echo = createEcho(token)

    // Subscribe to user-specific notifications
    if (authStore.user?.id && !userChannelBound) {
      echo
        .private(`user.${authStore.user.id}`)
        .listen('.notification.new', (data: BroadcastNotification) => {
          notificationStore.setUnreadCount(notificationStore.unreadCount + 1)
          notificationStore.fetchRecent()
        })
      userChannelBound = true
    }

    // Subscribe to workspace events
    subscribeToWorkspace()
  }

  function subscribeToWorkspace() {
    const echo = getEcho()
    if (!echo) return

    const wsId = workspaceStore.currentWorkspace?.id
    if (!wsId || wsId === workspaceChannelId) return

    // Leave previous workspace channel
    if (workspaceChannelId) {
      echo.leave(`workspace.${workspaceChannelId}`)
      echo.leave(`workspace.${workspaceChannelId}.inbox`)
    }

    workspaceChannelId = wsId

    echo
      .private(`workspace.${wsId}`)
      .listen('.post.status_changed', (_data: BroadcastPostStatus) => {
        // Components can react to this via event bus or store refresh
      })

    echo
      .private(`workspace.${wsId}.inbox`)
      .listen('.inbox.item_received', (_data: BroadcastInboxItem) => {
        // Components can react to inbox updates
      })
  }

  function disconnect() {
    userChannelBound = false
    workspaceChannelId = null
    disconnectEcho()
  }

  // Re-subscribe when workspace changes
  watch(
    () => workspaceStore.currentWorkspace?.id,
    () => {
      if (getEcho()) {
        subscribeToWorkspace()
      }
    },
  )

  onUnmounted(() => {
    disconnect()
  })

  return {
    connect,
    disconnect,
    subscribeToWorkspace,
  }
}
