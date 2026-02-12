<script setup lang="ts">
import { ref } from 'vue'
import FileUpload from 'primevue/fileupload'

defineProps<{
  accept?: string
  maxFileSize?: number
  multiple?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  select: [files: File[]]
}>()

function onSelect(event: { files: File[] }) {
  emit('select', event.files)
}
</script>

<template>
  <FileUpload
    mode="basic"
    :accept="accept || 'image/*,video/*'"
    :max-file-size="maxFileSize || 10485760"
    :multiple="multiple !== false"
    :disabled="disabled"
    :auto="false"
    choose-label="Choose files"
    @select="onSelect"
  />
</template>
