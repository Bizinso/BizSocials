<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { inboxApi } from '@/api/inbox'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxItemData, InboxReplyData } from '@/types/inbox'
import AppAvatar from '@/components/shared/AppAvatar.vue'
import AppPlatformIcon from '@/components/shared/AppPlatformIcon.vue'
import AppLoadingSkeleton from '@/components/shared/AppLoadingSkeleton.vue'
import InboxReplyForm from './InboxReplyForm.vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { formatRelative } from '@/utils/formatters'

const props = defineProps<{
  workspaceId: string
  item: InboxItemData
}>()

const emit = defineEmits<{
  updated: [item: InboxItemData]
  assign: [item: InboxItemData]
}>()

const toast = useToast()
const replies = ref<InboxReplyData[]>([])
const loadingReplies = ref(false)

const canReply = computed(() => props.item.item_type === 'comment')

onMounted(async () => {
  loadingReplies.value = true
  try {
    replies.value = await inboxApi.listReplies(props.workspaceId, props.item.id)
  } finally {
    loadingReplies.value = false
  }
})

async function markResolved() {
  try {
    const updated = await inboxApi.resolve(props.workspaceId, props.item.id)
    toast.success('Marked as resolved')
    emit('updated', updated)
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function archive() {
  try {
    const updated = await inboxApi.archive(props.workspaceId, props.item.id)
    toast.success('Archived')
    emit('updated', updated)
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

function onReplied(reply: InboxReplyData) {
  replies.value.push(reply)
}
</script>

<template>
  <div class="space-y-4">
    <!-- Original item -->
    <div class="rounded-lg border border-gray-200 p-4">
      <div class="mb-3 flex items-start justify-between">
        <div class="flex items-center gap-3">
          <AppAvatar :name="item.author_name" :src="item.author_avatar_url" size="md" />
          <div>
            <div class="flex items-center gap-2">
              <span class="font-medium text-gray-900">{{ item.author_name }}</span>
              <span v-if="item.author_username" class="text-sm text-gray-400">@{{ item.author_username }}</span>
              <AppPlatformIcon v-if="item.platform" :platform="item.platform" size="sm" />
            </div>
            <p class="text-xs text-gray-400">{{ formatRelative(item.platform_created_at) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Tag :value="item.item_type" :severity="item.item_type === 'comment' ? 'info' : 'warn'" />
          <Tag :value="item.status" />
        </div>
      </div>

      <p class="mb-4 whitespace-pre-wrap text-sm text-gray-800">{{ item.content_text }}</p>

      <div class="flex items-center gap-2">
        <Button
          v-if="item.status !== 'resolved'"
          label="Resolve"
          icon="pi pi-check"
          severity="success"
          size="small"
          @click="markResolved"
        />
        <Button
          v-if="item.status !== 'archived'"
          label="Archive"
          icon="pi pi-box"
          severity="secondary"
          size="small"
          @click="archive"
        />
        <Button
          label="Assign"
          icon="pi pi-user"
          severity="info"
          size="small"
          outlined
          @click="emit('assign', item)"
        />
      </div>
    </div>

    <!-- Replies -->
    <div>
      <h4 class="mb-2 text-sm font-medium text-gray-700">
        Replies ({{ replies.length }})
      </h4>

      <AppLoadingSkeleton v-if="loadingReplies" :lines="2" :count="2" />

      <div v-else class="space-y-3">
        <div
          v-for="reply in replies"
          :key="reply.id"
          class="rounded-lg border border-gray-100 bg-gray-50 p-3"
        >
          <div class="mb-1 flex items-center gap-2">
            <span class="text-sm font-medium text-gray-900">{{ reply.replied_by_name }}</span>
            <span class="text-xs text-gray-400">{{ formatRelative(reply.sent_at) }}</span>
            <Tag v-if="reply.failed_at" value="Failed" severity="danger" class="!text-[10px]" />
          </div>
          <p class="whitespace-pre-wrap text-sm text-gray-700">{{ reply.content_text }}</p>
          <p v-if="reply.failure_reason" class="mt-1 text-xs text-red-500">{{ reply.failure_reason }}</p>
        </div>
      </div>

      <InboxReplyForm
        :workspace-id="workspaceId"
        :item-id="item.id"
        :can-reply="canReply"
        class="mt-4"
        @replied="onReplied"
      />
    </div>
  </div>
</template>
