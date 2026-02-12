import { ref } from 'vue'
import { contentApi } from '@/api/content'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { PostMediaData } from '@/types/content'
import { MAX_FILE_SIZE, SUPPORTED_IMAGE_TYPES, SUPPORTED_VIDEO_TYPES } from '@/utils/constants'

export function useMediaUpload(workspaceId: string, postId: string) {
  const uploading = ref(false)
  const progress = ref(0)
  const toast = useToast()

  function validateFile(file: File): string | null {
    const allowedTypes = [...SUPPORTED_IMAGE_TYPES, ...SUPPORTED_VIDEO_TYPES]
    if (!allowedTypes.includes(file.type)) {
      return `Unsupported file type: ${file.type}`
    }
    if (file.size > MAX_FILE_SIZE) {
      return `File too large. Maximum size is ${MAX_FILE_SIZE / 1024 / 1024}MB`
    }
    return null
  }

  async function uploadFile(file: File): Promise<PostMediaData | null> {
    const error = validateFile(file)
    if (error) {
      toast.error(error)
      return null
    }

    uploading.value = true
    progress.value = 0
    try {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('media_type', file.type.startsWith('video/') ? 'video' : 'image')
      formData.append('original_filename', file.name)
      formData.append('file_size', file.size.toString())
      formData.append('mime_type', file.type)

      const media = await contentApi.uploadMedia(workspaceId, postId, formData)
      progress.value = 100
      return media
    } catch (e) {
      toast.error(parseApiError(e).message)
      return null
    } finally {
      uploading.value = false
    }
  }

  async function uploadFiles(files: File[]): Promise<PostMediaData[]> {
    const results: PostMediaData[] = []
    for (const file of files) {
      const media = await uploadFile(file)
      if (media) results.push(media)
    }
    return results
  }

  return {
    uploading,
    progress,
    validateFile,
    uploadFile,
    uploadFiles,
  }
}
