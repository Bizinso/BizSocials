<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { mediaLibraryApi } from '@/api/media-library'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { MediaLibraryItemData, MediaFolderData } from '@/types/media-library'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const items = ref<MediaLibraryItemData[]>([])
const folders = ref<MediaFolderData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const uploading = ref(false)
const currentFolder = ref<string | undefined>(undefined)
const searchQuery = ref('')
const typeFilter = ref('')

// Create folder
const showCreateFolder = ref(false)
const newFolderName = ref('')

onMounted(() => {
  fetchItems()
  fetchFolders()
})

async function fetchItems(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 24 }
    if (currentFolder.value) params.folder_id = currentFolder.value
    if (searchQuery.value) params.search = searchQuery.value
    if (typeFilter.value) params.type = typeFilter.value
    const res = await mediaLibraryApi.list(workspaceId.value, params)
    items.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function fetchFolders() {
  try {
    folders.value = await mediaLibraryApi.listFolders(workspaceId.value)
  } catch (e) { /* ignore */ }
}

async function handleUpload(event: Event) {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files?.length) return
  uploading.value = true
  try {
    for (const file of Array.from(files)) {
      await mediaLibraryApi.upload(workspaceId.value, file, currentFolder.value)
    }
    toast.success(`${files.length} file(s) uploaded`)
    fetchItems()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally {
    uploading.value = false
    target.value = ''
  }
}

async function createFolder() {
  if (!newFolderName.value.trim()) return
  try {
    await mediaLibraryApi.createFolder(workspaceId.value, {
      name: newFolderName.value,
      parent_id: currentFolder.value,
    })
    toast.success('Folder created')
    newFolderName.value = ''
    showCreateFolder.value = false
    fetchFolders()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteItem(item: MediaLibraryItemData) {
  if (!confirm(`Delete "${item.original_name}"?`)) return
  try {
    await mediaLibraryApi.delete(workspaceId.value, item.id)
    toast.success('Deleted')
    fetchItems()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function navigateFolder(folderId: string | undefined) {
  currentFolder.value = folderId
  fetchItems()
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1048576).toFixed(1)} MB`
}

function isImage(mime: string) { return mime.startsWith('image/') }
function isVideo(mime: string) { return mime.startsWith('video/') }
</script>

<template>
  <AppPageHeader title="Media Library" description="Upload and manage media assets">
    <template #actions>
      <div class="flex gap-2">
        <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showCreateFolder = !showCreateFolder">
          <i class="pi pi-folder-plus mr-1" /> New Folder
        </button>
        <label class="cursor-pointer rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">
          <i class="pi pi-upload mr-1" /> Upload
          <input type="file" multiple accept="image/*,video/*,application/pdf,.doc,.docx" class="hidden" @change="handleUpload" />
        </label>
      </div>
    </template>
  </AppPageHeader>

  <!-- Create folder form -->
  <AppCard v-if="showCreateFolder" class="mb-4">
    <form class="flex items-end gap-3" @submit.prevent="createFolder">
      <div class="flex-1">
        <label class="mb-1 block text-sm font-medium text-gray-700">Folder Name</label>
        <input v-model="newFolderName" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="My Folder" />
      </div>
      <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Create</button>
      <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreateFolder = false">Cancel</button>
    </form>
  </AppCard>

  <!-- Filters -->
  <div class="mb-4 flex gap-3">
    <input v-model="searchQuery" type="text" placeholder="Search files..." class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm" @keyup.enter="fetchItems()" />
    <select v-model="typeFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" @change="fetchItems()">
      <option value="">All Types</option>
      <option value="image">Images</option>
      <option value="video">Videos</option>
      <option value="application">Documents</option>
    </select>
  </div>

  <!-- Folder breadcrumb -->
  <div v-if="currentFolder" class="mb-3 flex items-center gap-1 text-sm text-gray-500">
    <button class="hover:text-primary-600" @click="navigateFolder(undefined)">Root</button>
    <i class="pi pi-angle-right text-xs" />
    <span class="font-medium text-gray-900">{{ folders.find(f => f.id === currentFolder)?.name || 'Folder' }}</span>
  </div>

  <!-- Folders row -->
  <div v-if="folders.length && !currentFolder" class="mb-4 flex flex-wrap gap-2">
    <button v-for="folder in folders.filter(f => !f.parent_id)" :key="folder.id" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50" @click="navigateFolder(folder.id)">
      <i class="pi pi-folder text-amber-500" />
      {{ folder.name }}
    </button>
  </div>

  <!-- Upload indicator -->
  <div v-if="uploading" class="mb-4 flex items-center gap-2 rounded-lg bg-blue-50 p-3 text-sm text-blue-700">
    <i class="pi pi-spin pi-spinner" /> Uploading...
  </div>

  <!-- Items grid -->
  <AppCard :padding="false">
    <div v-if="loading && items.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="items.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-images mb-2 text-3xl" /><p class="text-sm">No media files</p></div>
    <div v-else class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
      <div v-for="item in items" :key="item.id" class="group relative overflow-hidden rounded-lg border border-gray-200">
        <div class="aspect-square bg-gray-100">
          <img v-if="isImage(item.mime_type)" :src="item.thumbnail_url || item.url" :alt="item.alt_text || item.original_name" class="h-full w-full object-cover" />
          <div v-else-if="isVideo(item.mime_type)" class="flex h-full items-center justify-center">
            <i class="pi pi-video text-3xl text-gray-400" />
          </div>
          <div v-else class="flex h-full items-center justify-center">
            <i class="pi pi-file text-3xl text-gray-400" />
          </div>
        </div>
        <div class="p-2">
          <p class="truncate text-xs font-medium text-gray-900">{{ item.original_name }}</p>
          <p class="text-xs text-gray-400">{{ formatFileSize(item.file_size) }}</p>
        </div>
        <button class="absolute right-1 top-1 hidden rounded bg-white/80 p-1 text-gray-500 hover:text-red-500 group-hover:block" @click="deleteItem(item)">
          <i class="pi pi-trash text-xs" />
        </button>
      </div>
    </div>
  </AppCard>
</template>
