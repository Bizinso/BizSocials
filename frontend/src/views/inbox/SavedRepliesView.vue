<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { savedReplyApi } from '@/api/inbox-extended'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { SavedReplyData, CreateSavedReplyRequest } from '@/types/inbox-extended'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const replies = ref<SavedReplyData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)
const filterCategory = ref('')
const filterSearch = ref('')
const editingId = ref<string | null>(null)

const form = ref({
  title: '',
  content: '',
  shortcut: '',
  category: '',
})

const categories = computed(() => {
  const cats = new Set<string>()
  replies.value.forEach(r => { if (r.category) cats.add(r.category) })
  return Array.from(cats).sort()
})

onMounted(() => fetchReplies())

async function fetchReplies(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 20 }
    if (filterCategory.value) params.category = filterCategory.value
    if (filterSearch.value) params.search = filterSearch.value
    const res = await savedReplyApi.list(workspaceId.value, params)
    replies.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

function resetForm() {
  form.value = { title: '', content: '', shortcut: '', category: '' }
  editingId.value = null
}

function startEdit(reply: SavedReplyData) {
  form.value = {
    title: reply.title,
    content: reply.content,
    shortcut: reply.shortcut || '',
    category: reply.category || '',
  }
  editingId.value = reply.id
  showCreate.value = true
}

async function saveReply() {
  saving.value = true
  try {
    const data: CreateSavedReplyRequest = { title: form.value.title, content: form.value.content }
    if (form.value.shortcut) data.shortcut = form.value.shortcut
    if (form.value.category) data.category = form.value.category

    if (editingId.value) {
      await savedReplyApi.update(workspaceId.value, editingId.value, data)
      toast.success('Saved reply updated')
    } else {
      await savedReplyApi.create(workspaceId.value, data)
      toast.success('Saved reply created')
    }
    showCreate.value = false
    resetForm()
    fetchReplies()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function deleteReply(reply: SavedReplyData) {
  if (!confirm(`Delete "${reply.title}"?`)) return
  try {
    await savedReplyApi.delete(workspaceId.value, reply.id)
    toast.success('Deleted')
    fetchReplies()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function copyContent(content: string) {
  navigator.clipboard.writeText(content)
  toast.success('Copied to clipboard')
}
</script>

<template>
  <AppPageHeader title="Saved Replies" description="Pre-written reply templates for quick inbox responses">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate; resetForm()"><i class="pi pi-plus mr-1" /> New Reply</button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="saveReply">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Title *</label>
        <input v-model="form.title" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Thank You Response" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Content *</label>
        <textarea v-model="form.content" required rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Type your reply template..." />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Shortcut</label>
          <input v-model="form.shortcut" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="/thanks" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
          <input v-model="form.category" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Support" />
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false; resetForm()">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">{{ editingId ? 'Update' : 'Create' }}</button>
      </div>
    </form>
  </AppCard>

  <!-- Filters -->
  <div class="mb-4 flex items-center gap-3">
    <input v-model="filterSearch" type="text" class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-sm" placeholder="Search replies..." @input="fetchReplies(1)" />
    <select v-model="filterCategory" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm" @change="fetchReplies(1)">
      <option value="">All Categories</option>
      <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
    </select>
  </div>

  <AppCard :padding="false">
    <div v-if="loading && replies.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="replies.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-comment mb-2 text-3xl" /><p class="text-sm">No saved replies yet</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Title</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Shortcut</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Category</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Used</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="reply in replies" :key="reply.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <p class="text-sm font-medium text-gray-900">{{ reply.title }}</p>
            <p class="mt-0.5 max-w-md truncate text-xs text-gray-400">{{ reply.content }}</p>
          </td>
          <td class="px-4 py-2.5">
            <span v-if="reply.shortcut" class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">{{ reply.shortcut }}</span>
            <span v-else class="text-xs text-gray-300">--</span>
          </td>
          <td class="px-4 py-2.5">
            <span v-if="reply.category" class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ reply.category }}</span>
            <span v-else class="text-xs text-gray-300">--</span>
          </td>
          <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ reply.usage_count }}</td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-gray-100" title="Copy" @click="copyContent(reply.content)"><i class="pi pi-copy text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-blue-50 hover:text-blue-500" title="Edit" @click="startEdit(reply)"><i class="pi pi-pencil text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" title="Delete" @click="deleteReply(reply)"><i class="pi pi-trash text-sm" /></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
