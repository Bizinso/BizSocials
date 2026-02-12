<script setup lang="ts">
import { computed } from 'vue'
import { WhatsAppMessageDirection, WhatsAppMessageStatus } from '@/types/enums'
import type { WhatsAppMessageData } from '@/types/whatsapp'

const props = defineProps<{
  message: WhatsAppMessageData
}>()

const isOutbound = computed(() => props.message.direction === WhatsAppMessageDirection.Outbound)

const statusIcon = computed(() => {
  switch (props.message.status) {
    case WhatsAppMessageStatus.Pending: return 'pi pi-clock'
    case WhatsAppMessageStatus.Sent: return 'pi pi-check'
    case WhatsAppMessageStatus.Delivered: return 'pi pi-check-circle'
    case WhatsAppMessageStatus.Read: return 'pi pi-eye'
    case WhatsAppMessageStatus.Failed: return 'pi pi-exclamation-triangle'
    default: return ''
  }
})

const statusColor = computed(() => {
  if (props.message.status === WhatsAppMessageStatus.Read) return 'text-blue-500'
  if (props.message.status === WhatsAppMessageStatus.Failed) return 'text-red-500'
  return 'text-gray-400'
})

const timeLabel = computed(() => {
  const d = new Date(props.message.platform_timestamp || props.message.created_at)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
})

const isMedia = computed(() => ['image', 'video', 'audio', 'document'].includes(props.message.type))
</script>

<template>
  <div class="flex" :class="isOutbound ? 'justify-end' : 'justify-start'">
    <div
      class="max-w-[75%] rounded-xl px-3 py-2 text-sm shadow-sm"
      :class="isOutbound ? 'rounded-tr-none bg-green-100' : 'rounded-tl-none bg-white'"
    >
      <!-- Sender name for outbound -->
      <p v-if="isOutbound && message.sent_by_name" class="mb-0.5 text-xs font-medium text-green-700">
        {{ message.sent_by_name }}
      </p>

      <!-- Media content -->
      <div v-if="isMedia" class="mb-1">
        <img
          v-if="message.type === 'image' && message.media_url"
          :src="message.media_url"
          class="max-h-64 rounded-lg"
          alt="Image"
        />
        <video
          v-else-if="message.type === 'video' && message.media_url"
          :src="message.media_url"
          controls
          class="max-h-64 rounded-lg"
        />
        <audio v-else-if="message.type === 'audio' && message.media_url" :src="message.media_url" controls class="w-full" />
        <a
          v-else-if="message.type === 'document' && message.media_url"
          :href="message.media_url"
          target="_blank"
          class="flex items-center gap-2 rounded-lg bg-gray-50 p-2 text-primary-600 hover:underline"
        >
          <i class="pi pi-file" />
          <span>Document</span>
        </a>
      </div>

      <!-- Location -->
      <div v-if="message.type === 'location' && message.content_payload" class="mb-1">
        <div class="flex items-center gap-1 text-xs text-gray-500">
          <i class="pi pi-map-marker" />
          <span>Location shared</span>
        </div>
      </div>

      <!-- Text content -->
      <p v-if="message.content_text" class="whitespace-pre-wrap break-words text-gray-800">
        {{ message.content_text }}
      </p>

      <!-- Error -->
      <p v-if="message.error_message" class="mt-1 text-xs text-red-500">
        {{ message.error_message }}
      </p>

      <!-- Time + status -->
      <div class="mt-0.5 flex items-center justify-end gap-1">
        <span class="text-[10px] text-gray-400">{{ timeLabel }}</span>
        <i v-if="isOutbound && statusIcon" :class="[statusIcon, statusColor]" class="text-[10px]" />
      </div>
    </div>
  </div>
</template>
