<script setup lang="ts">
import type { NotificationData } from '@/types/notification'
import { formatRelative } from '@/utils/formatters'

const props = defineProps<{
  notification: NotificationData
}>()

const emit = defineEmits<{
  click: [notification: NotificationData]
}>()
</script>

<template>
  <div
    class="cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50"
    :class="{ 'bg-blue-50/40': !notification.is_read }"
    @click="emit('click', notification)"
  >
    <div class="flex items-start gap-3">
      <div
        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
        :class="notification.is_urgent ? 'bg-red-100 text-red-600' : 'bg-primary-100 text-primary-600'"
      >
        <i :class="notification.icon" class="text-sm" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-sm text-gray-900" :class="{ 'font-medium': !notification.is_read }">
          {{ notification.title }}
        </p>
        <p class="text-xs text-gray-500">{{ notification.message }}</p>
        <p class="mt-0.5 text-xs text-gray-400">{{ formatRelative(notification.created_at) }}</p>
      </div>
      <span
        v-if="!notification.is_read"
        class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary-500"
      />
    </div>
  </div>
</template>
