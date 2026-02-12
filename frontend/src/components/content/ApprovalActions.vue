<script setup lang="ts">
import { ref } from 'vue'
import { contentApi } from '@/api/content'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { PostData } from '@/types/content'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'

const props = defineProps<{
  workspaceId: string
  post: PostData
}>()

const emit = defineEmits<{
  decided: [post: PostData]
}>()

const toast = useToast()
const processing = ref(false)
const showRejectDialog = ref(false)
const rejectReason = ref('')
const approveComment = ref('')

async function approve() {
  processing.value = true
  try {
    await contentApi.approvePost(props.workspaceId, props.post.id, {
      comment: approveComment.value || null,
    })
    toast.success('Post approved')
    emit('decided', props.post)
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    processing.value = false
  }
}

async function reject() {
  if (!rejectReason.value.trim()) return
  processing.value = true
  try {
    await contentApi.rejectPost(props.workspaceId, props.post.id, {
      reason: rejectReason.value,
    })
    showRejectDialog.value = false
    toast.success('Post rejected')
    emit('decided', props.post)
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    processing.value = false
  }
}
</script>

<template>
  <div class="flex items-center gap-2">
    <Button
      label="Approve"
      icon="pi pi-check"
      severity="success"
      size="small"
      :loading="processing"
      @click="approve"
    />
    <Button
      label="Reject"
      icon="pi pi-times"
      severity="danger"
      size="small"
      :loading="processing"
      @click="showRejectDialog = true"
    />
  </div>

  <Dialog v-model:visible="showRejectDialog" header="Reject Post" :style="{ width: '450px' }" modal>
    <div class="space-y-3">
      <p class="text-sm text-gray-600">Please provide a reason for rejecting this post.</p>
      <Textarea
        v-model="rejectReason"
        rows="3"
        placeholder="Reason for rejection..."
        class="w-full"
      />
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" @click="showRejectDialog = false" />
      <Button
        label="Reject"
        icon="pi pi-times"
        severity="danger"
        :disabled="!rejectReason.trim()"
        :loading="processing"
        @click="reject"
      />
    </template>
  </Dialog>
</template>
