<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { contentCategoryApi, hashtagGroupApi } from '@/api/content-engine'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { ContentCategoryData, HashtagGroupData } from '@/types/content-engine'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const activeTab = ref<'categories' | 'hashtags'>('categories')

// Categories
const categories = ref<ContentCategoryData[]>([])
const loadingCats = ref(false)
const showCatForm = ref(false)
const catForm = ref({ name: '', color: '#6366f1', description: '' })

// Hashtags
const hashtags = ref<HashtagGroupData[]>([])
const hashtagPagination = ref<PaginationMeta | null>(null)
const loadingHash = ref(false)
const showHashForm = ref(false)
const hashForm = ref({ name: '', hashtags: '' as string, description: '' })

onMounted(() => {
  fetchCategories()
  fetchHashtags()
})

async function fetchCategories() {
  loadingCats.value = true
  try { categories.value = await contentCategoryApi.list(workspaceId.value) }
  catch (e) { toast.error(parseApiError(e).message) }
  finally { loadingCats.value = false }
}

async function createCategory() {
  try {
    await contentCategoryApi.create(workspaceId.value, catForm.value)
    toast.success('Category created')
    catForm.value = { name: '', color: '#6366f1', description: '' }
    showCatForm.value = false
    fetchCategories()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteCategory(cat: ContentCategoryData) {
  if (!confirm(`Delete "${cat.name}"?`)) return
  try {
    await contentCategoryApi.delete(workspaceId.value, cat.id)
    toast.success('Deleted')
    fetchCategories()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function fetchHashtags(page = 1) {
  loadingHash.value = true
  try {
    const res = await hashtagGroupApi.list(workspaceId.value, { page })
    hashtags.value = res.data
    hashtagPagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loadingHash.value = false }
}

async function createHashtagGroup() {
  try {
    const tags = hashForm.value.hashtags.split(/[\s,]+/).filter(Boolean).map(t => t.startsWith('#') ? t : `#${t}`)
    await hashtagGroupApi.create(workspaceId.value, { name: hashForm.value.name, hashtags: tags, description: hashForm.value.description || undefined })
    toast.success('Hashtag group created')
    hashForm.value = { name: '', hashtags: '', description: '' }
    showHashForm.value = false
    fetchHashtags()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteHashtagGroup(group: HashtagGroupData) {
  if (!confirm(`Delete "${group.name}"?`)) return
  try {
    await hashtagGroupApi.delete(workspaceId.value, group.id)
    toast.success('Deleted')
    fetchHashtags()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Content Organization" description="Manage categories and hashtag groups" />

  <div class="mb-4 flex gap-1 rounded-lg bg-gray-100 p-1">
    <button v-for="tab in (['categories', 'hashtags'] as const)" :key="tab" class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium capitalize transition-colors" :class="activeTab === tab ? 'bg-white text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'" @click="activeTab = tab">{{ tab }}</button>
  </div>

  <!-- Categories -->
  <div v-if="activeTab === 'categories'">
    <div class="mb-3 flex justify-end">
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCatForm = !showCatForm"><i class="pi pi-plus mr-1" /> Add Category</button>
    </div>
    <AppCard v-if="showCatForm" class="mb-4">
      <form class="space-y-3" @submit.prevent="createCategory">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Name *</label>
            <input v-model="catForm.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Color</label>
            <input v-model="catForm.color" type="color" class="h-9 w-full rounded-lg border border-gray-300" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
          <input v-model="catForm.description" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCatForm = false">Cancel</button>
          <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Create</button>
        </div>
      </form>
    </AppCard>
    <div v-if="loadingCats" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="categories.length === 0" class="rounded-lg border border-gray-200 py-12 text-center text-gray-400"><p class="text-sm">No categories</p></div>
    <div v-else class="space-y-2">
      <div v-for="cat in categories" :key="cat.id" class="flex items-center justify-between rounded-lg border border-gray-200 p-3">
        <div class="flex items-center gap-3">
          <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: cat.color || '#6b7280' }" />
          <div>
            <p class="text-sm font-medium text-gray-900">{{ cat.name }}</p>
            <p v-if="cat.description" class="text-xs text-gray-500">{{ cat.description }}</p>
          </div>
        </div>
        <button class="text-gray-400 hover:text-red-500" @click="deleteCategory(cat)"><i class="pi pi-trash text-sm" /></button>
      </div>
    </div>
  </div>

  <!-- Hashtag Groups -->
  <div v-if="activeTab === 'hashtags'">
    <div class="mb-3 flex justify-end">
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showHashForm = !showHashForm"><i class="pi pi-plus mr-1" /> Add Group</button>
    </div>
    <AppCard v-if="showHashForm" class="mb-4">
      <form class="space-y-3" @submit.prevent="createHashtagGroup">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Group Name *</label>
          <input v-model="hashForm.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Hashtags * (comma or space separated)</label>
          <textarea v-model="hashForm.hashtags" rows="2" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="#marketing #socialmedia #growth" />
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showHashForm = false">Cancel</button>
          <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Create</button>
        </div>
      </form>
    </AppCard>
    <div v-if="loadingHash" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="hashtags.length === 0" class="rounded-lg border border-gray-200 py-12 text-center text-gray-400"><p class="text-sm">No hashtag groups</p></div>
    <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <AppCard v-for="group in hashtags" :key="group.id">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">{{ group.name }}</p>
            <p class="text-xs text-gray-400">{{ group.usage_count }} uses</p>
          </div>
          <button class="text-gray-400 hover:text-red-500" @click="deleteHashtagGroup(group)"><i class="pi pi-trash text-sm" /></button>
        </div>
        <div class="mt-2 flex flex-wrap gap-1">
          <span v-for="tag in group.hashtags" :key="tag" class="rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-600">{{ tag }}</span>
        </div>
      </AppCard>
    </div>
  </div>
</template>
