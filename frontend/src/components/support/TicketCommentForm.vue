<script setup lang="ts">
import { ref } from 'vue'
import { supportApi } from '@/api/support'
import { useToast } from '@/composables/useToast'
import type { SupportCommentData } from '@/types/support'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

const props = defineProps<{
  ticketId: string
}>()

const emit = defineEmits<{
  commented: [comment: SupportCommentData]
}>()

const toast = useToast()
const content = ref('')
const submitting = ref(false)

async function submit() {
  if (!content.value.trim()) return
  submitting.value = true
  try {
    const comment = await supportApi.addComment(props.ticketId, {
      content: content.value,
    })
    emit('commented', comment)
    content.value = ''
  } catch {
    toast.error('Failed to post reply')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form @submit.prevent="submit">
    <Textarea v-model="content" placeholder="Write a reply..." rows="3" class="w-full" />
    <Button
      type="submit"
      label="Reply"
      icon="pi pi-send"
      size="small"
      class="mt-2"
      :loading="submitting"
      :disabled="!content.trim()"
    />
  </form>
</template>
