<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { evergreenApi } from '@/api/content-engine'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { EvergreenRuleData } from '@/types/content-engine'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const rules = ref<EvergreenRuleData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref({
  name: '',
  repost_interval_days: 30,
  max_reposts: 3,
  social_account_ids: [] as string[],
  content_variation: false,
})

onMounted(() => fetchRules())

async function fetchRules(page = 1) {
  loading.value = true
  try {
    const res = await evergreenApi.list(workspaceId.value, { page })
    rules.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createRule() {
  saving.value = true
  try {
    await evergreenApi.create(workspaceId.value, form.value)
    toast.success('Evergreen rule created')
    showCreate.value = false
    form.value = { name: '', repost_interval_days: 30, max_reposts: 3, social_account_ids: [], content_variation: false }
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function buildPool(rule: EvergreenRuleData) {
  try {
    const res = await evergreenApi.buildPool(workspaceId.value, rule.id)
    toast.success(`Pool built: ${res.count} posts added`)
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteRule(rule: EvergreenRuleData) {
  if (!confirm(`Delete "${rule.name}"?`)) return
  try {
    await evergreenApi.delete(workspaceId.value, rule.id)
    toast.success('Deleted')
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Evergreen Content" description="Automatically repost high-performing content">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate"><i class="pi pi-plus mr-1" /> New Rule</button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createRule">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Rule Name *</label>
        <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Repost Interval (days)</label>
          <input v-model.number="form.repost_interval_days" type="number" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Max Reposts</label>
          <input v-model.number="form.max_reposts" type="number" min="1" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>
      <label class="flex items-center gap-2 text-sm text-gray-700">
        <input v-model="form.content_variation" type="checkbox" class="rounded border-gray-300" />
        Enable content variation (AI rewording)
      </label>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && rules.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="rules.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-replay mb-2 text-3xl" /><p class="text-sm">No evergreen rules</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Rule</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Interval</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Max Reposts</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Active</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="rule in rules" :key="rule.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <p class="text-sm font-medium text-gray-900">{{ rule.name }}</p>
            <p v-if="rule.last_reposted_at" class="text-xs text-gray-400">Last: {{ new Date(rule.last_reposted_at).toLocaleDateString() }}</p>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ rule.repost_interval_days }}d</td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ rule.max_reposts }}</td>
          <td class="px-4 py-2.5">
            <span :class="rule.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-2 py-0.5 text-xs">{{ rule.is_active ? 'Active' : 'Inactive' }}</span>
          </td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-blue-50 hover:text-blue-500" title="Build Pool" @click="buildPool(rule)"><i class="pi pi-refresh text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteRule(rule)"><i class="pi pi-trash text-sm" /></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
