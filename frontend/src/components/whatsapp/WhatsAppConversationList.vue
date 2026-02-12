<script setup lang="ts">
import { computed } from 'vue'
import type { WhatsAppConversationData } from '@/types/whatsapp'
import type { PaginationMeta } from '@/types/api'
import WhatsAppServiceWindowBadge from './WhatsAppServiceWindowBadge.vue'

const props = defineProps<{
  conversations: WhatsAppConversationData[]
  loading: boolean
  pagination: PaginationMeta | null
  activeId?: string
}>()

const emit = defineEmits<{
  select: [conversation: WhatsAppConversationData]
  page: [page: number]
}>()

function formatTime(dateStr: string | null): string {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

const statusColors: Record<string, string> = {
  active: 'bg-green-500',
  pending: 'bg-yellow-500',
  resolved: 'bg-gray-400',
  archived: 'bg-gray-300',
}
</script>

<template>
  <div class="flex h-full flex-col">
    <!-- Loading state -->
    <div v-if="loading && conversations.length === 0" class="flex flex-1 items-center justify-center py-12">
      <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
    </div>

    <!-- Empty state -->
    <div v-else-if="conversations.length === 0" class="flex flex-1 flex-col items-center justify-center py-12 text-gray-400">
      <i class="pi pi-comments mb-2 text-3xl" />
      <p class="text-sm">No conversations found</p>
    </div>

    <!-- List -->
    <ul v-else class="flex-1 overflow-y-auto">
      <li
        v-for="conv in conversations"
        :key="conv.id"
        class="cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50"
        :class="{ 'bg-primary-50': conv.id === activeId }"
        @click="emit('select', conv)"
      >
        <div class="flex items-start gap-3">
          <!-- Avatar placeholder -->
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-semibold text-green-700">
            {{ (conv.customer_name || conv.customer_phone || '?').charAt(0).toUpperCase() }}
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between">
              <p class="truncate text-sm font-medium text-gray-900">
                {{ conv.customer_name || conv.customer_profile_name || conv.customer_phone }}
              </p>
              <span class="ml-2 shrink-0 text-xs text-gray-400">
                {{ formatTime(conv.last_message_at) }}
              </span>
            </div>

            <div class="mt-0.5 flex items-center gap-1.5">
              <span class="inline-block h-2 w-2 shrink-0 rounded-full" :class="statusColors[conv.status] || 'bg-gray-300'" />
              <span class="truncate text-xs capitalize text-gray-500">{{ conv.status }}</span>
              <span class="text-xs text-gray-300">|</span>
              <span class="text-xs text-gray-500">{{ conv.message_count }} msgs</span>
            </div>

            <div class="mt-1 flex items-center gap-2">
              <WhatsAppServiceWindowBadge
                :last-customer-message-at="conv.last_customer_message_at"
                :is-within-service-window="conv.is_within_service_window"
              />
              <span v-if="conv.assigned_to_name" class="truncate text-xs text-gray-400">
                {{ conv.assigned_to_name }}
              </span>
            </div>
          </div>
        </div>
      </li>
    </ul>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="border-t border-gray-200 px-4 py-2 text-center">
      <div class="flex items-center justify-between text-xs text-gray-500">
        <button
          :disabled="pagination.current_page <= 1"
          class="rounded px-2 py-1 hover:bg-gray-100 disabled:opacity-50"
          @click="emit('page', pagination!.current_page - 1)"
        >
          Prev
        </button>
        <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button
          :disabled="pagination.current_page >= pagination.last_page"
          class="rounded px-2 py-1 hover:bg-gray-100 disabled:opacity-50"
          @click="emit('page', pagination!.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>
