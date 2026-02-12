import { get, post, put, getPaginated } from './client'
import type {
  NotificationData,
  NotificationPreferenceData,
  UpdatePreferencesRequest,
} from '@/types/notification'
import type { PaginationParams } from '@/types/api'

export const notificationsApi = {
  list(params?: PaginationParams) {
    return getPaginated<NotificationData>('/notifications', params as Record<string, unknown>)
  },

  unreadCount() {
    return get<{ count: number }>('/notifications/unread-count')
  },

  recent() {
    return get<NotificationData[]>('/notifications/recent')
  },

  markAsRead(notificationId: string) {
    return post<NotificationData>(`/notifications/${notificationId}/read`)
  },

  markAllAsRead() {
    return post<void>('/notifications/read-all')
  },

  markMultipleAsRead(ids: string[]) {
    return post<void>('/notifications/read-multiple', { ids })
  },

  getPreferences() {
    return get<NotificationPreferenceData[]>('/notifications/preferences')
  },

  updatePreferences(data: UpdatePreferencesRequest) {
    return put<NotificationPreferenceData[]>('/notifications/preferences', data)
  },
}
