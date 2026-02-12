<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { audienceDemographicsApi } from '@/api/analytics-extended'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { AudienceDemographicData } from '@/types/analytics-extended'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const demographics = ref<AudienceDemographicData | null>(null)
const loading = ref(false)
const refreshing = ref(false)

onMounted(() => fetchLatest())

async function fetchLatest() {
  loading.value = true
  try {
    demographics.value = await audienceDemographicsApi.latest(workspaceId.value)
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function refreshDemographics() {
  refreshing.value = true
  try {
    await audienceDemographicsApi.fetch(workspaceId.value)
    toast.success('Demographics refresh started')
    setTimeout(() => fetchLatest(), 2000)
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { refreshing.value = false }
}

function maxValue(obj: Record<string, number> | undefined): number {
  if (!obj) return 1
  const vals = Object.values(obj)
  return vals.length ? Math.max(...vals) : 1
}
</script>

<template>
  <AppPageHeader title="Audience Demographics" description="Understand your audience composition across social accounts">
    <template #actions>
      <button
        class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
        :disabled="refreshing"
        @click="refreshDemographics"
      >
        <i class="pi pi-refresh mr-1" :class="{ 'pi-spin': refreshing }" /> Refresh Demographics
      </button>
    </template>
  </AppPageHeader>

  <div v-if="loading" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>

  <div v-else-if="!demographics" class="py-12 text-center">
    <AppCard>
      <div class="py-8 text-center text-gray-400">
        <i class="pi pi-users mb-2 text-3xl" />
        <p class="text-sm">No demographic data available. Click "Refresh Demographics" to fetch data.</p>
      </div>
    </AppCard>
  </div>

  <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <!-- Follower Count -->
    <AppCard class="md:col-span-2">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-500">Total Followers</p>
          <p class="text-3xl font-bold text-gray-900">{{ demographics.follower_count.toLocaleString() }}</p>
        </div>
        <p class="text-xs text-gray-400">Snapshot: {{ new Date(demographics.snapshot_date).toLocaleDateString() }}</p>
      </div>
    </AppCard>

    <!-- Age Ranges -->
    <AppCard>
      <h3 class="mb-3 text-sm font-semibold text-gray-700">Age Distribution</h3>
      <div class="space-y-2">
        <div v-for="(count, range) in demographics.age_ranges" :key="range" class="flex items-center gap-2">
          <span class="w-16 shrink-0 text-xs text-gray-600">{{ range }}</span>
          <div class="flex-1 overflow-hidden rounded-full bg-gray-100">
            <div class="h-4 rounded-full bg-primary-500" :style="{ width: `${(count / maxValue(demographics.age_ranges)) * 100}%` }" />
          </div>
          <span class="w-12 shrink-0 text-right text-xs font-medium text-gray-700">{{ count.toLocaleString() }}</span>
        </div>
      </div>
    </AppCard>

    <!-- Gender Split -->
    <AppCard>
      <h3 class="mb-3 text-sm font-semibold text-gray-700">Gender Split</h3>
      <div class="space-y-2">
        <div v-for="(count, gender) in demographics.gender_split" :key="gender" class="flex items-center gap-2">
          <span class="w-20 shrink-0 text-xs capitalize text-gray-600">{{ gender }}</span>
          <div class="flex-1 overflow-hidden rounded-full bg-gray-100">
            <div class="h-4 rounded-full bg-indigo-500" :style="{ width: `${(count / maxValue(demographics.gender_split)) * 100}%` }" />
          </div>
          <span class="w-12 shrink-0 text-right text-xs font-medium text-gray-700">{{ count.toLocaleString() }}</span>
        </div>
      </div>
    </AppCard>

    <!-- Top Countries -->
    <AppCard>
      <h3 class="mb-3 text-sm font-semibold text-gray-700">Top Countries</h3>
      <div v-if="demographics.top_countries && demographics.top_countries.length" class="space-y-1.5">
        <div v-for="(entry, idx) in demographics.top_countries" :key="idx" class="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-gray-50">
          <span class="text-gray-700">{{ Object.keys(entry)[0] }}</span>
          <span class="font-medium text-gray-900">{{ Object.values(entry)[0]?.toLocaleString() }}</span>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">No country data</p>
    </AppCard>

    <!-- Top Cities -->
    <AppCard>
      <h3 class="mb-3 text-sm font-semibold text-gray-700">Top Cities</h3>
      <div v-if="demographics.top_cities && demographics.top_cities.length" class="space-y-1.5">
        <div v-for="(entry, idx) in demographics.top_cities" :key="idx" class="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-gray-50">
          <span class="text-gray-700">{{ Object.keys(entry)[0] }}</span>
          <span class="font-medium text-gray-900">{{ Object.values(entry)[0]?.toLocaleString() }}</span>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">No city data</p>
    </AppCard>
  </div>
</template>
