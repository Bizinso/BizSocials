<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { hashtagTrackingApi } from '@/api/analytics-extended'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { HashtagPerformanceData } from '@/types/analytics-extended'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const hashtags = ref<HashtagPerformanceData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const search = ref('')
const sortField = ref('usage_count')
const sortDir = ref<'desc' | 'asc'>('desc')

onMounted(() => fetchHashtags())

async function fetchHashtags(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page, sort: sortField.value, direction: sortDir.value }
    if (search.value) params.search = search.value
    const res = await hashtagTrackingApi.list(workspaceId.value, params)
    hashtags.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

function toggleSort(field: string) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc'
  } else {
    sortField.value = field
    sortDir.value = 'desc'
  }
  fetchHashtags()
}

function sortIcon(field: string): string {
  if (sortField.value !== field) return 'pi pi-sort-alt'
  return sortDir.value === 'desc' ? 'pi pi-sort-amount-down' : 'pi pi-sort-amount-up'
}

function handleSearch() {
  fetchHashtags()
}
</script>

<template>
  <AppPageHeader title="Hashtag Tracking" description="Track performance of hashtags across your social accounts" />

  <AppCard class="mb-4">
    <form class="flex items-center gap-3" @submit.prevent="handleSearch">
      <div class="relative flex-1">
        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400" />
        <input v-model="search" type="text" placeholder="Search hashtags..." class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm" />
      </div>
      <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">Search</button>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && hashtags.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="hashtags.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-hashtag mb-2 text-3xl" /><p class="text-sm">No tracked hashtags</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Hashtag</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Platform</th>
          <th class="cursor-pointer px-4 py-2 text-left text-xs font-medium text-gray-500 hover:text-gray-700" @click="toggleSort('usage_count')">
            Usage Count <i :class="sortIcon('usage_count')" class="ml-0.5 text-xs" />
          </th>
          <th class="cursor-pointer px-4 py-2 text-left text-xs font-medium text-gray-500 hover:text-gray-700" @click="toggleSort('avg_reach')">
            Avg Reach <i :class="sortIcon('avg_reach')" class="ml-0.5 text-xs" />
          </th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Avg Engagement</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Last Used</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ht in hashtags" :key="ht.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <span class="text-sm font-medium text-primary-700">#{{ ht.hashtag }}</span>
          </td>
          <td class="px-4 py-2.5">
            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ ht.platform }}</span>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ ht.usage_count.toLocaleString() }}</td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ ht.avg_reach.toLocaleString() }}</td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ ht.avg_engagement.toLocaleString() }}</td>
          <td class="px-4 py-2.5 text-xs text-gray-400">{{ ht.last_used_at ? new Date(ht.last_used_at).toLocaleDateString() : '-' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
      <p class="text-xs text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)</p>
      <div class="flex gap-1">
        <button
          :disabled="pagination.current_page <= 1"
          class="rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
          @click="fetchHashtags(pagination!.current_page - 1)"
        >Prev</button>
        <button
          :disabled="pagination.current_page >= pagination.last_page"
          class="rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
          @click="fetchHashtags(pagination!.current_page + 1)"
        >Next</button>
      </div>
    </div>
  </AppCard>
</template>
