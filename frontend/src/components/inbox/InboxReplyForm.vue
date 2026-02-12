<script setup lang="ts">
import { ref } from 'vue'
import { inboxApi } from '@/api/inbox'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxReplyData } from '@/types/inbox'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const props = defineProps<{
  workspaceId: string
  itemId: string
  canReply?: boolean
}>()

const emit = defineEmits<{
  replied: [reply: InboxReplyData]
}>()

const toast = useToast()
const replyText = ref('')
const sending = ref(false)

async function sendReply() {
  if (!replyText.value.trim()) return
  sending.value = true
  try {
    const reply = await inboxApi.createReply(props.workspaceId, props.itemId, {
      content_text: replyText.value,
    })
    replyText.value = ''
    toast.success('Reply sent')
    emit('replied', reply)
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div v-if="canReply !== false" class="border-t border-gray-200 pt-4">
    <Textarea
      v-model="replyText"
      rows="3"
      auto-resize
      placeholder="Type your reply..."
      class="w-full"
    />
    <div class="mt-2 flex justify-end">
      <Button
        label="Send Reply"
        icon="pi pi-send"
        size="small"
        :disabled="!replyText.trim()"
        :loading="sending"
        @click="sendReply"
      />
    </div>
  </div>
  <div v-else class="border-t border-gray-200 pt-3 text-center text-sm text-gray-400">
    Replies are not available for mentions.
  </div>
</template>
