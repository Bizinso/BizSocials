<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { keywordMonitoringApi } from '@/api/listening'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { MonitoredKeywordData, KeywordMentionData } from '@/types/listening'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const keywords = ref<MonitoredKeywordData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const expandedKeywordId = ref<string | null>(null)
const mentions = ref<KeywordMentionData[]>([])
const mentionsLoading = ref(false)

const platformOptions = ['facebook', 'instagram', 'twitter', 'linkedin']

const form = ref({
  keyword: '',
  platforms: [] as string[],
  notify_on_match: false,
})

const sentimentColors: Record<string, string> = {
  positive: 'bg-green-50 text-green-700',
  negative: 'bg-red-50 text-red-700',
  neutral: 'bg-gray-100 text-gray-600',
  unknown: 'bg-gray-50 text-gray-400',
}

onMounted(() => fetchKeywords())

async function fetchKeywords(page = 1) {
  loading.value = true
  try {
    const res = await keywordMonitoringApi.list(workspaceId.value, { page })
    keywords.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createKeyword() {
  saving.value = true
  try {
    await keywordMonitoringApi.create(workspaceId.value, {
      keyword: form.value.keyword,
      platforms: form.value.platforms,
      notify_on_match: form.value.notify_on_match,
    })
    toast.success('Keyword added')
    showCreate.value = false
    form.value = { keyword: '', platforms: [], notify_on_match: false }
    fetchKeywords()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function toggleExpand(keyword: MonitoredKeywordData) {
  if (expandedKeywordId.value === keyword.id) {
    expandedKeywordId.value = null
    mentions.value = []
    return
  }
  expandedKeywordId.value = keyword.id
  mentionsLoading.value = true
  try {
    const res = await keywordMonitoringApi.mentions(workspaceId.value, keyword.id, { per_page: 10 })
    mentions.value = res.data
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { mentionsLoading.value = false }
}

async function toggleActive(keyword: MonitoredKeywordData) {
  try {
    await keywordMonitoringApi.update(workspaceId.value, keyword.id, { is_active: !keyword.is_active })
    toast.success(keyword.is_active ? 'Keyword paused' : 'Keyword activated')
    fetchKeywords()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteKeyword(keyword: MonitoredKeywordData) {
  if (!confirm(`Delete keyword "${keyword.keyword}"?`)) return
  try {
    await keywordMonitoringApi.delete(workspaceId.value, keyword.id)
    toast.success('Deleted')
    if (expandedKeywordId.value === keyword.id) {
      expandedKeywordId.value = null
      mentions.value = []
    }
    fetchKeywords()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Social Listening" description="Monitor keywords and brand mentions across platforms">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" /> New Keyword
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createKeyword">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Keyword *</label>
        <input v-model="form.keyword" type="text" required placeholder="e.g. brand name, product, topic" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-700">Platforms *</label>
        <div class="flex flex-wrap gap-3">
          <label v-for="platform in platformOptions" :key="platform" class="flex items-center gap-1.5 text-sm text-gray-700">
            <input v-model="form.platforms" :value="platform" type="checkbox" class="rounded border-gray-300" />
            <span class="capitalize">{{ platform }}</span>
          </label>
        </div>
      </div>
      <label class="flex items-center gap-2 text-sm text-gray-700">
        <input v-model="form.notify_on_match" type="checkbox" class="rounded border-gray-300" />
        Notify on match
      </label>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving || form.platforms.length === 0" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && keywords.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="keywords.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-volume-up mb-2 text-3xl" /><p class="text-sm">No monitored keywords</p></div>
    <div v-else>
      <table class="w-full">
        <thead class="border-b border-gray-200 bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Keyword</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Platforms</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Matches</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="kw in keywords" :key="kw.id">
            <tr class="cursor-pointer border-b border-gray-100 hover:bg-gray-50" @click="toggleExpand(kw)">
              <td class="px-4 py-2.5">
                <div class="flex items-center gap-1.5">
                  <i :class="expandedKeywordId === kw.id ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" class="text-xs text-gray-400" />
                  <span class="text-sm font-medium text-gray-900">{{ kw.keyword }}</span>
                  <i v-if="kw.notify_on_match" class="pi pi-bell text-xs text-yellow-500" title="Notifications enabled" />
                </div>
              </td>
              <td class="px-4 py-2.5">
                <div class="flex flex-wrap gap-1">
                  <span v-for="p in kw.platforms" :key="p" class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{{ p }}</span>
                </div>
              </td>
              <td class="px-4 py-2.5 text-sm text-gray-600">{{ kw.match_count.toLocaleString() }}</td>
              <td class="px-4 py-2.5">
                <span :class="kw.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-2 py-0.5 text-xs">{{ kw.is_active ? 'Active' : 'Paused' }}</span>
              </td>
              <td class="px-4 py-2.5 text-right" @click.stop>
                <div class="flex items-center justify-end gap-1">
                  <button class="rounded p-1 text-gray-400 hover:bg-primary-50 hover:text-primary-500" :title="kw.is_active ? 'Pause' : 'Activate'" @click="toggleActive(kw)">
                    <i :class="kw.is_active ? 'pi pi-pause' : 'pi pi-play'" class="text-sm" />
                  </button>
                  <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteKeyword(kw)"><i class="pi pi-trash text-sm" /></button>
                </div>
              </td>
            </tr>
            <!-- Expanded Mentions -->
            <tr v-if="expandedKeywordId === kw.id">
              <td colspan="5" class="bg-gray-50 px-6 py-3">
                <div v-if="mentionsLoading" class="flex items-center justify-center py-4"><i class="pi pi-spin pi-spinner text-gray-400" /></div>
                <div v-else-if="mentions.length === 0" class="py-4 text-center text-xs text-gray-400">No mentions found</div>
                <div v-else class="space-y-2">
                  <p class="mb-2 text-xs font-medium text-gray-500">Recent Mentions</p>
                  <div v-for="mention in mentions" :key="mention.id" class="rounded-lg border border-gray-200 bg-white p-3">
                    <div class="flex items-start justify-between">
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="text-sm font-medium text-gray-800">{{ mention.author_name }}</span>
                          <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">{{ mention.platform }}</span>
                          <span :class="sentimentColors[mention.sentiment]" class="rounded px-1.5 py-0.5 text-xs">{{ mention.sentiment }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ mention.content_text }}</p>
                      </div>
                      <a v-if="mention.url" :href="mention.url" target="_blank" class="shrink-0 text-primary-600 hover:text-primary-700"><i class="pi pi-external-link text-sm" /></a>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">{{ new Date(mention.platform_created_at).toLocaleString() }}</p>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </AppCard>
</template>
