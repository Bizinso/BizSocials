<script setup lang="ts">
import { ref, computed } from 'vue'
import type { SendWhatsAppMessageRequest } from '@/types/whatsapp'
import WhatsAppQuickReplyPicker from './WhatsAppQuickReplyPicker.vue'
import type { QuickReply } from './WhatsAppQuickReplyPicker.vue'

const props = defineProps<{
  isWithinServiceWindow: boolean
  sending?: boolean
  quickReplies?: QuickReply[]
}>()

const emit = defineEmits<{
  send: [data: SendWhatsAppMessageRequest]
  'send-media': [data: SendWhatsAppMessageRequest]
}>()

const messageText = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const canSend = computed(() => messageText.value.trim().length > 0 && !props.sending)

function handleSend() {
  if (!canSend.value) return
  emit('send', {
    type: 'text',
    content: messageText.value.trim(),
  })
  messageText.value = ''
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    handleSend()
  }
}

function onQuickReply(content: string) {
  messageText.value = content
}

function triggerFileUpload() {
  fileInput.value?.click()
}

function onFileSelected(e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  const mimeType = file.type
  let type: 'image' | 'video' | 'document' | 'audio' = 'document'
  if (mimeType.startsWith('image/')) type = 'image'
  else if (mimeType.startsWith('video/')) type = 'video'
  else if (mimeType.startsWith('audio/')) type = 'audio'

  emit('send-media', {
    type,
    media_url: URL.createObjectURL(file),
    caption: messageText.value.trim() || undefined,
  })
  messageText.value = ''
  target.value = ''
}
</script>

<template>
  <div class="border-t border-gray-200 bg-white px-4 py-3">
    <!-- Outside service window warning -->
    <div v-if="!isWithinServiceWindow" class="mb-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-700">
      <i class="pi pi-exclamation-triangle mr-1" />
      24-hour service window expired. Only template messages can be sent.
    </div>

    <div class="flex items-end gap-2">
      <!-- Quick replies -->
      <WhatsAppQuickReplyPicker
        v-if="quickReplies && quickReplies.length > 0"
        :replies="quickReplies"
        @select="onQuickReply"
      />

      <!-- Attachment -->
      <button
        type="button"
        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
        title="Attach file"
        @click="triggerFileUpload"
      >
        <i class="pi pi-paperclip" />
      </button>
      <input ref="fileInput" type="file" class="hidden" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx" @change="onFileSelected" />

      <!-- Text input -->
      <div class="flex-1">
        <textarea
          v-model="messageText"
          rows="1"
          class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
          placeholder="Type a message..."
          @keydown="handleKeydown"
        />
      </div>

      <!-- Send -->
      <button
        type="button"
        class="rounded-lg bg-green-600 p-2 text-white transition-colors hover:bg-green-700 disabled:opacity-50"
        :disabled="!canSend"
        @click="handleSend"
      >
        <i v-if="sending" class="pi pi-spin pi-spinner" />
        <i v-else class="pi pi-send" />
      </button>
    </div>
  </div>
</template>
