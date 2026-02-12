<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { whatsappQuickReplyApi } from '@/api/whatsapp-automation'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppQuickReplyData, CreateQuickReplyRequest } from '@/types/whatsapp-automation'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const replies = ref<WhatsAppQuickReplyData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref<CreateQuickReplyRequest>({ title: '', content: '', shortcut: '', category: '' })

onMounted(() => fetchReplies())

async function fetchReplies(page = 1) {
  loading.value = true
  try {
    const res = await whatsappQuickReplyApi.list(workspaceId.value, { page, per_page: 50 })
    replies.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createReply() {
  saving.value = true
  try {
    await whatsappQuickReplyApi.create(workspaceId.value, form.value)
    toast.success('Quick reply created')
    showCreate.value = false
    form.value = { title: '', content: '', shortcut: '', category: '' }
    fetchReplies()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function deleteReply(reply: WhatsAppQuickReplyData) {
  if (!confirm(`Delete "${reply.title}"?`)) return
  try {
    await whatsappQuickReplyApi.delete(workspaceId.value, reply.id)
    toast.success('Deleted')
    fetchReplies()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Quick Replies" description="Saved message shortcuts for faster responses">
    <template #actions>
      <button class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" />
        Add Reply
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createReply">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Title *</label>
          <input v-model="form.title" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Greeting" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Shortcut</label>
          <input v-model="form.shortcut" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="/hello" />
        </div>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Content *</label>
        <textarea v-model="form.content" rows="3" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Hi! Thanks for reaching out..." />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
        <input v-model="form.category" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="General" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">Save</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && replies.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="replies.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-bolt mb-2 text-3xl" /><p class="text-sm">No quick replies</p></div>
    <div v-else class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="reply in replies" :key="reply.id" class="rounded-lg border border-gray-200 p-3">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">{{ reply.title }}</p>
            <span v-if="reply.shortcut" class="rounded bg-gray-100 px-1 text-xs text-gray-500">{{ reply.shortcut }}</span>
          </div>
          <button class="text-gray-400 hover:text-red-500" @click="deleteReply(reply)"><i class="pi pi-trash text-sm" /></button>
        </div>
        <p class="mt-1 text-xs text-gray-600 line-clamp-3">{{ reply.content }}</p>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
          <span>{{ reply.category || 'Uncategorized' }}</span>
          <span>{{ reply.usage_count }} uses</span>
        </div>
      </div>
    </div>
  </AppCard>
</template>
