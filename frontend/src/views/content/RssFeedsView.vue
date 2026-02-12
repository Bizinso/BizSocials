<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { rssFeedApi } from '@/api/content-engine'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { RssFeedData, RssFeedItemData } from '@/types/content-engine'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const feeds = ref<RssFeedData[]>([])
const feedPagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref({ url: '', name: '', fetch_interval_hours: 6 })

// Feed items panel
const selectedFeed = ref<RssFeedData | null>(null)
const feedItems = ref<RssFeedItemData[]>([])
const loadingItems = ref(false)

onMounted(() => fetchFeeds())

async function fetchFeeds(page = 1) {
  loading.value = true
  try {
    const res = await rssFeedApi.list(workspaceId.value, { page })
    feeds.value = res.data
    feedPagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createFeed() {
  saving.value = true
  try {
    await rssFeedApi.create(workspaceId.value, form.value)
    toast.success('RSS feed added')
    showCreate.value = false
    form.value = { url: '', name: '', fetch_interval_hours: 6 }
    fetchFeeds()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function viewItems(feed: RssFeedData) {
  selectedFeed.value = feed
  loadingItems.value = true
  try {
    const res = await rssFeedApi.items(workspaceId.value, feed.id, { per_page: 20 })
    feedItems.value = res.data
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loadingItems.value = false }
}

async function fetchNow(feed: RssFeedData) {
  try {
    await rssFeedApi.fetch(workspaceId.value, feed.id)
    toast.success('Feed fetched')
    if (selectedFeed.value?.id === feed.id) viewItems(feed)
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteFeed(feed: RssFeedData) {
  if (!confirm(`Delete "${feed.name}"?`)) return
  try {
    await rssFeedApi.delete(workspaceId.value, feed.id)
    toast.success('Deleted')
    if (selectedFeed.value?.id === feed.id) selectedFeed.value = null
    fetchFeeds()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="RSS Feeds" description="Curate content from RSS feeds">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate"><i class="pi pi-plus mr-1" /> Add Feed</button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createFeed">
      <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2">
          <label class="mb-1 block text-sm font-medium text-gray-700">Feed URL *</label>
          <input v-model="form.url" type="url" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="https://blog.example.com/rss" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Name *</label>
          <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Fetch Interval (hours)</label>
          <input v-model.number="form.fetch_interval_hours" type="number" min="1" max="168" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Add Feed</button>
      </div>
    </form>
  </AppCard>

  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <!-- Feed list -->
    <AppCard :padding="false">
      <div v-if="loading" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
      <div v-else-if="feeds.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-rss mb-2 text-3xl" /><p class="text-sm">No RSS feeds</p></div>
      <div v-else class="divide-y divide-gray-100">
        <div v-for="feed in feeds" :key="feed.id" class="flex items-center justify-between p-3 hover:bg-gray-50" :class="selectedFeed?.id === feed.id ? 'bg-primary-50' : ''">
          <button class="flex-1 text-left" @click="viewItems(feed)">
            <p class="text-sm font-medium text-gray-900">{{ feed.name }}</p>
            <p class="text-xs text-gray-400">{{ feed.last_fetched_at ? `Fetched: ${new Date(feed.last_fetched_at).toLocaleString()}` : 'Never fetched' }}</p>
          </button>
          <div class="flex items-center gap-1">
            <span :class="feed.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-1.5 py-0.5 text-xs">{{ feed.is_active ? 'Active' : 'Inactive' }}</span>
            <button class="rounded p-1 text-gray-400 hover:bg-blue-50 hover:text-blue-500" @click="fetchNow(feed)"><i class="pi pi-refresh text-sm" /></button>
            <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteFeed(feed)"><i class="pi pi-trash text-sm" /></button>
          </div>
        </div>
      </div>
    </AppCard>

    <!-- Feed items -->
    <AppCard :padding="false">
      <div v-if="!selectedFeed" class="py-12 text-center text-gray-400"><p class="text-sm">Select a feed to view items</p></div>
      <div v-else-if="loadingItems" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
      <div v-else-if="feedItems.length === 0" class="py-12 text-center text-gray-400"><p class="text-sm">No items yet</p></div>
      <div v-else class="divide-y divide-gray-100">
        <div v-for="item in feedItems" :key="item.id" class="p-3">
          <a :href="item.link" target="_blank" class="text-sm font-medium text-blue-600 hover:underline">{{ item.title }}</a>
          <p v-if="item.description" class="mt-0.5 text-xs text-gray-500 line-clamp-2">{{ item.description }}</p>
          <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">
            <span v-if="item.published_at">{{ new Date(item.published_at).toLocaleDateString() }}</span>
            <span v-if="item.is_used" class="rounded bg-green-50 px-1 text-green-600">Used</span>
          </div>
        </div>
      </div>
    </AppCard>
  </div>
</template>
