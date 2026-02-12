<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import type { WhatsAppMessageData, WhatsAppConversationData, SendWhatsAppMessageRequest } from '@/types/whatsapp'
import type { PaginationMeta } from '@/types/api'
import WhatsAppMessageBubble from './WhatsAppMessageBubble.vue'
import WhatsAppMessageInput from './WhatsAppMessageInput.vue'

const props = defineProps<{
  conversation: WhatsAppConversationData
  messages: WhatsAppMessageData[]
  loading: boolean
  sending: boolean
  pagination: PaginationMeta | null
}>()

const emit = defineEmits<{
  send: [data: SendWhatsAppMessageRequest]
  'send-media': [data: SendWhatsAppMessageRequest]
  'load-more': []
}>()

const threadRef = ref<HTMLElement | null>(null)

// Scroll to bottom when messages change
watch(
  () => props.messages.length,
  async () => {
    await nextTick()
    if (threadRef.value) {
      threadRef.value.scrollTop = threadRef.value.scrollHeight
    }
  },
)

const hasMore = computed(() => {
  if (!props.pagination) return false
  return props.pagination.current_page < props.pagination.last_page
})

function dateLabel(dateStr: string): string {
  const d = new Date(dateStr)
  const now = new Date()
  if (d.toDateString() === now.toDateString()) return 'Today'
  const yesterday = new Date(now)
  yesterday.setDate(yesterday.getDate() - 1)
  if (d.toDateString() === yesterday.toDateString()) return 'Yesterday'
  return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}

// Group messages by date
const groupedMessages = computed(() => {
  const groups: { label: string; messages: WhatsAppMessageData[] }[] = []
  let currentLabel = ''
  // Messages come newest-first from API, reverse for display
  const sorted = [...props.messages].reverse()
  for (const msg of sorted) {
    const label = dateLabel(msg.platform_timestamp || msg.created_at)
    if (label !== currentLabel) {
      currentLabel = label
      groups.push({ label, messages: [] })
    }
    groups[groups.length - 1].messages.push(msg)
  }
  return groups
})
</script>

<template>
  <div class="flex h-full flex-col bg-[#ECE5DD]">
    <!-- Header -->
    <div class="flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-2.5">
      <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-sm font-semibold text-green-700">
        {{ (conversation.customer_name || conversation.customer_phone || '?').charAt(0).toUpperCase() }}
      </div>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-900">
          {{ conversation.customer_name || conversation.customer_profile_name || conversation.customer_phone }}
        </p>
        <p class="text-xs text-gray-500">{{ conversation.customer_phone }}</p>
      </div>
    </div>

    <!-- Messages -->
    <div ref="threadRef" class="flex-1 overflow-y-auto px-4 py-3">
      <!-- Load more -->
      <div v-if="hasMore" class="mb-3 text-center">
        <button
          class="rounded-full bg-white px-3 py-1 text-xs text-gray-500 shadow-sm hover:bg-gray-50"
          @click="emit('load-more')"
        >
          Load older messages
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading && messages.length === 0" class="flex items-center justify-center py-12">
        <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
      </div>

      <!-- Date groups -->
      <div v-for="group in groupedMessages" :key="group.label" class="mb-4">
        <div class="mb-2 flex justify-center">
          <span class="rounded-full bg-white/80 px-3 py-0.5 text-xs text-gray-500 shadow-sm">
            {{ group.label }}
          </span>
        </div>
        <div class="space-y-2">
          <WhatsAppMessageBubble v-for="msg in group.messages" :key="msg.id" :message="msg" />
        </div>
      </div>

      <!-- Empty -->
      <div v-if="!loading && messages.length === 0" class="flex items-center justify-center py-12 text-gray-400">
        <p class="text-sm">No messages yet</p>
      </div>
    </div>

    <!-- Input -->
    <WhatsAppMessageInput
      :is-within-service-window="conversation.is_within_service_window"
      :sending="sending"
      @send="emit('send', $event)"
      @send-media="emit('send-media', $event)"
    />
  </div>
</template>
