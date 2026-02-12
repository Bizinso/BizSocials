<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { scheduledReportApi } from '@/api/analytics-extended'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { ScheduledReportData } from '@/types/analytics-extended'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const reports = ref<ScheduledReportData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref({
  name: '',
  report_type: 'overview',
  frequency: 'weekly',
  recipients: '',
})

const reportTypeOptions = [
  { value: 'overview', label: 'Overview' },
  { value: 'content', label: 'Content' },
  { value: 'engagement', label: 'Engagement' },
]

const frequencyOptions = [
  { value: 'weekly', label: 'Weekly' },
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
]

onMounted(() => fetchReports())

async function fetchReports(page = 1) {
  loading.value = true
  try {
    const res = await scheduledReportApi.list(workspaceId.value, { page })
    reports.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createReport() {
  saving.value = true
  try {
    const recipients = form.value.recipients.split(',').map(e => e.trim()).filter(Boolean)
    await scheduledReportApi.create(workspaceId.value, {
      name: form.value.name,
      report_type: form.value.report_type,
      frequency: form.value.frequency,
      recipients,
    })
    toast.success('Report scheduled')
    showCreate.value = false
    form.value = { name: '', report_type: 'overview', frequency: 'weekly', recipients: '' }
    fetchReports()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function toggleActive(report: ScheduledReportData) {
  try {
    await scheduledReportApi.update(workspaceId.value, report.id, { is_active: !report.is_active })
    toast.success(report.is_active ? 'Report deactivated' : 'Report activated')
    fetchReports()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteReport(report: ScheduledReportData) {
  if (!confirm(`Delete "${report.name}"?`)) return
  try {
    await scheduledReportApi.delete(workspaceId.value, report.id)
    toast.success('Deleted')
    fetchReports()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Scheduled Reports" description="Automate recurring report delivery to your team">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" /> New Report
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createReport">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Report Name *</label>
        <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Report Type</label>
          <select v-model="form.report_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option v-for="opt in reportTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Frequency</label>
          <select v-model="form.frequency" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option v-for="opt in frequencyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Recipients (comma-separated emails) *</label>
        <input v-model="form.recipients" type="text" required placeholder="alice@example.com, bob@example.com" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && reports.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="reports.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-clock mb-2 text-3xl" /><p class="text-sm">No scheduled reports</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Type</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Frequency</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Next Send</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="report in reports" :key="report.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <p class="text-sm font-medium text-gray-900">{{ report.name }}</p>
            <p class="text-xs text-gray-400">{{ report.recipients.join(', ') }}</p>
          </td>
          <td class="px-4 py-2.5">
            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs capitalize text-gray-600">{{ report.report_type }}</span>
          </td>
          <td class="px-4 py-2.5 text-sm capitalize text-gray-600">{{ report.frequency }}</td>
          <td class="px-4 py-2.5 text-xs text-gray-400">{{ report.next_send_at ? new Date(report.next_send_at).toLocaleString() : '-' }}</td>
          <td class="px-4 py-2.5">
            <span :class="report.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-2 py-0.5 text-xs">{{ report.is_active ? 'Active' : 'Paused' }}</span>
          </td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-primary-50 hover:text-primary-500" :title="report.is_active ? 'Pause' : 'Activate'" @click="toggleActive(report)">
                <i :class="report.is_active ? 'pi pi-pause' : 'pi pi-play'" class="text-sm" />
              </button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteReport(report)"><i class="pi pi-trash text-sm" /></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
