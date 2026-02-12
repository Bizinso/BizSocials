<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { contentApi } from '@/api/content'
import { useSocialStore } from '@/stores/social'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { parseApiError } from '@/utils/error-handler'
import type { PostData, PostDetailData, PostMediaData, CreatePostRequest, UpdatePostRequest, SchedulePostRequest } from '@/types/content'
import { PostStatus } from '@/types/enums'
import PostTargetSelector from './PostTargetSelector.vue'
import PostMediaGallery from './PostMediaGallery.vue'
import PostMediaUploader from './PostMediaUploader.vue'
import PostPreview from './PostPreview.vue'
import PostEditorToolbar from './PostEditorToolbar.vue'
import AIAssistPanel from './AIAssistPanel.vue'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const props = defineProps<{
  workspaceId: string
  postId?: string
}>()

const emit = defineEmits<{
  saved: [post: PostData]
  cancelled: []
}>()

const toast = useToast()
const socialStore = useSocialStore()

const saving = ref(false)
const contentText = ref('')
const linkUrl = ref('')
const firstComment = ref('')
const hashtagsText = ref('')
const selectedAccountIds = ref<string[]>([])
const media = ref<PostMediaData[]>([])
const currentPost = ref<PostData | null>(null)

const showScheduleDialog = ref(false)
const showAIPanel = ref(false)
const scheduleDate = ref<Date | null>(null)

const isEdit = computed(() => !!props.postId)
const canSubmit = computed(() => {
  if (!currentPost.value) return false
  return currentPost.value.status === PostStatus.Draft
})
const canSchedule = computed(() => {
  if (!currentPost.value) return false
  return [PostStatus.Draft, PostStatus.Approved].includes(currentPost.value.status)
})
const canPublish = computed(() => {
  if (!currentPost.value) return false
  return [PostStatus.Draft, PostStatus.Approved].includes(currentPost.value.status)
})

const previewAccount = computed(() => {
  if (selectedAccountIds.value.length === 0) return null
  return socialStore.connectedAccounts.find((a) => a.id === selectedAccountIds.value[0]) || null
})

onMounted(async () => {
  await socialStore.fetchAccounts(props.workspaceId)
  if (props.postId) {
    await loadPost()
  }
})

async function loadPost() {
  try {
    const detail = await contentApi.getPost(props.workspaceId, props.postId!)
    currentPost.value = detail.post
    contentText.value = detail.post.content_text || ''
    linkUrl.value = detail.post.link_url || ''
    firstComment.value = detail.post.first_comment || ''
    hashtagsText.value = detail.post.hashtags?.join(', ') || ''
    selectedAccountIds.value = detail.targets.map((t) => t.social_account_id)
    media.value = detail.media
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function buildHashtags(): string[] | null {
  if (!hashtagsText.value.trim()) return null
  return hashtagsText.value
    .split(',')
    .map((t) => t.trim())
    .filter(Boolean)
}

async function savePost() {
  saving.value = true
  try {
    if (isEdit.value && currentPost.value) {
      const data: UpdatePostRequest = {
        content_text: contentText.value || null,
        hashtags: buildHashtags(),
        link_url: linkUrl.value || null,
        first_comment: firstComment.value || null,
      }
      const updated = await contentApi.updatePost(props.workspaceId, currentPost.value.id, data)
      if (selectedAccountIds.value.length > 0) {
        await contentApi.updateTargets(props.workspaceId, currentPost.value.id, {
          social_account_ids: selectedAccountIds.value,
        })
      }
      currentPost.value = updated
      toast.success('Post updated')
      emit('saved', updated)
    } else {
      const data: CreatePostRequest = {
        content_text: contentText.value || null,
        hashtags: buildHashtags(),
        link_url: linkUrl.value || null,
        first_comment: firstComment.value || null,
        social_account_ids: selectedAccountIds.value.length > 0 ? selectedAccountIds.value : null,
      }
      const created = await contentApi.createPost(props.workspaceId, data)
      currentPost.value = created
      toast.success('Post created')
      emit('saved', created)
    }
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

async function submitForApproval() {
  if (!currentPost.value) return
  saving.value = true
  try {
    await savePost()
    if (currentPost.value) {
      const submitted = await contentApi.submitPost(props.workspaceId, currentPost.value.id)
      currentPost.value = submitted
      toast.success('Submitted for approval')
      emit('saved', submitted)
    }
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

function openScheduleDialog() {
  showScheduleDialog.value = true
}

async function confirmSchedule() {
  if (!currentPost.value || !scheduleDate.value) return
  saving.value = true
  try {
    await savePost()
    if (currentPost.value) {
      const data: SchedulePostRequest = {
        scheduled_at: scheduleDate.value.toISOString(),
      }
      const scheduled = await contentApi.schedulePost(props.workspaceId, currentPost.value.id, data)
      currentPost.value = scheduled
      showScheduleDialog.value = false
      toast.success('Post scheduled')
      emit('saved', scheduled)
    }
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

async function publishNow() {
  if (!currentPost.value) return
  saving.value = true
  try {
    await savePost()
    if (currentPost.value) {
      const published = await contentApi.publishPost(props.workspaceId, currentPost.value.id)
      currentPost.value = published
      toast.success('Post published')
      emit('saved', published)
    }
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

function onMediaUploaded(mediaItem: PostMediaData) {
  media.value.push(mediaItem)
}

async function onMediaRemoved(mediaItem: PostMediaData) {
  if (!currentPost.value) return
  try {
    await contentApi.deleteMedia(props.workspaceId, currentPost.value.id, mediaItem.id)
    media.value = media.value.filter((m) => m.id !== mediaItem.id)
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-3">
    <!-- Editor -->
    <div class="space-y-4 lg:col-span-2">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Post Content</h3>
        <Button label="AI Assist" icon="pi pi-sparkles" severity="secondary" size="small" @click="showAIPanel = true" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Content</label>
        <Textarea
          v-model="contentText"
          rows="6"
          auto-resize
          placeholder="What would you like to share?"
          class="w-full"
        />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Link URL</label>
        <InputText v-model="linkUrl" placeholder="https://..." class="w-full" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Hashtags</label>
        <InputText v-model="hashtagsText" placeholder="marketing, social, tips (comma separated)" class="w-full" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">First Comment</label>
        <Textarea v-model="firstComment" rows="2" auto-resize placeholder="Optional first comment after publishing" class="w-full" />
      </div>

      <!-- Media -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-700">Media</label>
        <PostMediaGallery :media="media" editable @remove="onMediaRemoved" />
        <div v-if="currentPost" class="mt-3">
          <PostMediaUploader
            :workspace-id="workspaceId"
            :post-id="currentPost.id"
            @uploaded="onMediaUploaded"
          />
        </div>
        <p v-else class="mt-2 text-xs text-gray-400">Save as draft first to upload media.</p>
      </div>

      <!-- Toolbar -->
      <PostEditorToolbar
        :saving="saving"
        :can-submit="canSubmit"
        :can-schedule="canSchedule"
        :can-publish="canPublish"
        @save="savePost"
        @submit="submitForApproval"
        @schedule="openScheduleDialog"
        @publish="publishNow"
      />
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
      <PostTargetSelector v-model="selectedAccountIds" />
      <PostPreview :content-text="contentText" :media="media" :account="previewAccount" />
    </div>
  </div>

  <!-- AI Assist Panel -->
  <AIAssistPanel
    v-model:visible="showAIPanel"
    @insert-caption="(caption: string) => { contentText = caption }"
    @insert-hashtags="(hashtags: string[]) => { hashtagsText = hashtagsText ? hashtagsText + ', ' + hashtags.join(', ') : hashtags.join(', ') }"
  />

  <!-- Schedule Dialog -->
  <Dialog v-model:visible="showScheduleDialog" header="Schedule Post" :style="{ width: '400px' }" modal>
    <div class="space-y-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Date & Time</label>
        <DatePicker
          v-model="scheduleDate"
          show-time
          hour-format="12"
          :min-date="new Date()"
          placeholder="Select date and time"
          class="w-full"
        />
      </div>
    </div>
    <template #footer>
      <Button label="Cancel" severity="secondary" @click="showScheduleDialog = false" />
      <Button label="Schedule" icon="pi pi-clock" :disabled="!scheduleDate" :loading="saving" @click="confirmSchedule" />
    </template>
  </Dialog>
</template>
