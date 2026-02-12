<script setup lang="ts">
import { useMediaUpload } from '@/composables/useMediaUpload'
import type { PostMediaData } from '@/types/content'
import AppFileUploader from '@/components/shared/AppFileUploader.vue'
import ProgressBar from 'primevue/progressbar'

const props = defineProps<{
  workspaceId: string
  postId: string
}>()

const emit = defineEmits<{
  uploaded: [media: PostMediaData]
}>()

const { uploading, progress, uploadFiles } = useMediaUpload(props.workspaceId, props.postId)

async function onFilesSelected(files: File[]) {
  const uploaded = await uploadFiles(files)
  for (const media of uploaded) {
    emit('uploaded', media)
  }
}
</script>

<template>
  <div>
    <AppFileUploader :disabled="uploading" @select="onFilesSelected" />
    <ProgressBar v-if="uploading" :value="progress" class="mt-2" style="height: 6px" />
  </div>
</template>
