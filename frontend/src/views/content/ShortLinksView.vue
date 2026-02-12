<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { shortLinkApi } from '@/api/content-engine'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { ShortLinkData, ShortLinkStatsData, CreateShortLinkRequest } from '@/types/content-engine'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const links = ref<ShortLinkData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)
const showStats = ref(false)
const stats = ref<ShortLinkStatsData | null>(null)

const form = ref({
  original_url: '',
  title: '',
  custom_alias: '',
  utm_source: '',
  utm_medium: '',
  utm_campaign: '',
  utm_term: '',
  utm_content: '',
})

onMounted(() => fetchLinks())

async function fetchLinks(page = 1) {
  loading.value = true
  try {
    const res = await shortLinkApi.list(workspaceId.value, { page, per_page: 20 })
    links.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createLink() {
  saving.value = true
  try {
    const data: CreateShortLinkRequest = { original_url: form.value.original_url }
    if (form.value.title) data.title = form.value.title
    if (form.value.custom_alias) data.custom_alias = form.value.custom_alias
    if (form.value.utm_source) data.utm_source = form.value.utm_source
    if (form.value.utm_medium) data.utm_medium = form.value.utm_medium
    if (form.value.utm_campaign) data.utm_campaign = form.value.utm_campaign
    if (form.value.utm_term) data.utm_term = form.value.utm_term
    if (form.value.utm_content) data.utm_content = form.value.utm_content
    await shortLinkApi.create(workspaceId.value, data)
    toast.success('Short link created')
    showCreate.value = false
    form.value = { original_url: '', title: '', custom_alias: '', utm_source: '', utm_medium: '', utm_campaign: '', utm_term: '', utm_content: '' }
    fetchLinks()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function viewStats(link: ShortLinkData) {
  try {
    stats.value = await shortLinkApi.stats(workspaceId.value, link.id)
    showStats.value = true
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteLink(link: ShortLinkData) {
  if (!confirm('Delete this link?')) return
  try {
    await shortLinkApi.delete(workspaceId.value, link.id)
    toast.success('Deleted')
    fetchLinks()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function copyLink(url: string) {
  navigator.clipboard.writeText(url)
  toast.success('Copied to clipboard')
}
</script>

<template>
  <AppPageHeader title="Short Links" description="Shorten URLs with UTM tracking">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate"><i class="pi pi-plus mr-1" /> Create Link</button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createLink">
      <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2">
          <label class="mb-1 block text-sm font-medium text-gray-700">URL *</label>
          <input v-model="form.original_url" type="url" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="https://example.com/page" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Title</label>
          <input v-model="form.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Custom Alias</label>
          <input v-model="form.custom_alias" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="my-link" />
        </div>
      </div>
      <details class="rounded-lg border border-gray-200 p-3">
        <summary class="cursor-pointer text-sm font-medium text-gray-700">UTM Parameters</summary>
        <div class="mt-3 grid grid-cols-2 gap-3">
          <div><label class="mb-1 block text-xs text-gray-500">Source</label><input v-model="form.utm_source" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm" placeholder="facebook" /></div>
          <div><label class="mb-1 block text-xs text-gray-500">Medium</label><input v-model="form.utm_medium" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm" placeholder="social" /></div>
          <div><label class="mb-1 block text-xs text-gray-500">Campaign</label><input v-model="form.utm_campaign" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm" placeholder="spring_sale" /></div>
          <div><label class="mb-1 block text-xs text-gray-500">Term</label><input v-model="form.utm_term" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm" /></div>
          <div class="col-span-2"><label class="mb-1 block text-xs text-gray-500">Content</label><input v-model="form.utm_content" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm" /></div>
        </div>
      </details>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && links.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="links.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-link mb-2 text-3xl" /><p class="text-sm">No short links</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Title / URL</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Short URL</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Clicks</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="link in links" :key="link.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <p class="text-sm font-medium text-gray-900">{{ link.title || 'Untitled' }}</p>
            <p class="max-w-xs truncate text-xs text-gray-400">{{ link.original_url }}</p>
          </td>
          <td class="px-4 py-2.5">
            <button class="text-sm text-blue-600 hover:underline" @click="copyLink(link.full_url)">{{ link.short_code }}</button>
          </td>
          <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ link.click_count }}</td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-blue-50 hover:text-blue-500" @click="viewStats(link)"><i class="pi pi-chart-bar text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-gray-100" @click="copyLink(link.full_url)"><i class="pi pi-copy text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteLink(link)"><i class="pi pi-trash text-sm" /></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>

  <!-- Stats modal -->
  <div v-if="showStats && stats" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showStats = false">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">Link Stats</h3>
        <button class="text-gray-400 hover:text-gray-600" @click="showStats = false"><i class="pi pi-times" /></button>
      </div>
      <p class="mb-3 text-2xl font-bold text-gray-900">{{ stats.click_count }} clicks</p>
      <div v-if="Object.keys(stats.device_breakdown).length" class="space-y-1">
        <p class="text-xs font-medium text-gray-500">By Device</p>
        <div v-for="(count, device) in stats.device_breakdown" :key="device" class="flex items-center justify-between text-sm">
          <span class="capitalize text-gray-700">{{ device }}</span>
          <span class="font-medium text-gray-900">{{ count }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
