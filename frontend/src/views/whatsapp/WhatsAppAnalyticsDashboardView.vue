<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { whatsappAnalyticsApi } from '@/api/whatsapp-automation'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxHealthData, MarketingPerformanceData, ComplianceHealthData, AgentProductivityData } from '@/types/whatsapp-automation'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const activeTab = ref<'inbox' | 'marketing' | 'compliance' | 'agents'>('inbox')
const loading = ref(false)

const inboxHealth = ref<InboxHealthData | null>(null)
const marketingPerf = ref<MarketingPerformanceData | null>(null)
const compliance = ref<ComplianceHealthData | null>(null)
const agentStats = ref<AgentProductivityData[]>([])

onMounted(() => fetchAll())

async function fetchAll() {
  loading.value = true
  try {
    const [ih, mp, ch, ap] = await Promise.all([
      whatsappAnalyticsApi.inboxHealth(workspaceId.value),
      whatsappAnalyticsApi.marketingPerformance(workspaceId.value),
      whatsappAnalyticsApi.complianceHealth(workspaceId.value),
      whatsappAnalyticsApi.agentProductivity(workspaceId.value),
    ])
    inboxHealth.value = ih
    marketingPerf.value = mp
    compliance.value = ch
    agentStats.value = ap
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

function formatSeconds(seconds: number | null): string {
  if (seconds === null) return '-'
  if (seconds < 60) return `${seconds}s`
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}m ${secs}s`
}
</script>

<template>
  <AppPageHeader title="WhatsApp Analytics" description="Monitor inbox health, marketing performance, and compliance" />

  <!-- Tabs -->
  <div class="mb-4 flex gap-1 rounded-lg bg-gray-100 p-1">
    <button
      v-for="tab in (['inbox', 'marketing', 'compliance', 'agents'] as const)"
      :key="tab"
      class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium capitalize transition-colors"
      :class="activeTab === tab ? 'bg-white text-green-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
      @click="activeTab = tab"
    >
      {{ tab }}
    </button>
  </div>

  <div v-if="loading" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>

  <!-- Inbox Health -->
  <div v-else-if="activeTab === 'inbox' && inboxHealth" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <AppCard>
      <p class="text-2xl font-bold text-gray-900">{{ inboxHealth.conversations_open }}</p>
      <p class="text-sm text-gray-500">Open Conversations</p>
    </AppCard>
    <AppCard>
      <p class="text-2xl font-bold text-gray-900">{{ inboxHealth.conversations_pending }}</p>
      <p class="text-sm text-gray-500">Pending</p>
    </AppCard>
    <AppCard>
      <p class="text-2xl font-bold text-gray-900">{{ formatSeconds(inboxHealth.avg_response_time) }}</p>
      <p class="text-sm text-gray-500">Avg Response Time</p>
    </AppCard>
    <AppCard>
      <p class="text-2xl font-bold text-gray-900">{{ inboxHealth.unassigned }}</p>
      <p class="text-sm text-gray-500">Unassigned</p>
    </AppCard>
  </div>

  <!-- Marketing Performance -->
  <div v-else-if="activeTab === 'marketing' && marketingPerf" class="space-y-4">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <AppCard>
        <p class="text-2xl font-bold text-blue-600">{{ marketingPerf.total_sent }}</p>
        <p class="text-sm text-gray-500">Messages Sent</p>
      </AppCard>
      <AppCard>
        <p class="text-2xl font-bold text-green-600">{{ marketingPerf.total_delivered }}</p>
        <p class="text-sm text-gray-500">Delivered</p>
      </AppCard>
      <AppCard>
        <p class="text-2xl font-bold text-indigo-600">{{ marketingPerf.total_read }}</p>
        <p class="text-sm text-gray-500">Read</p>
      </AppCard>
      <AppCard>
        <p class="text-2xl font-bold text-red-600">{{ marketingPerf.total_failed }}</p>
        <p class="text-sm text-gray-500">Failed</p>
      </AppCard>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <AppCard>
        <p class="text-3xl font-bold text-green-600">{{ marketingPerf.delivery_rate }}%</p>
        <p class="text-sm text-gray-500">Delivery Rate</p>
      </AppCard>
      <AppCard>
        <p class="text-3xl font-bold text-indigo-600">{{ marketingPerf.read_rate }}%</p>
        <p class="text-sm text-gray-500">Read Rate</p>
      </AppCard>
    </div>
  </div>

  <!-- Compliance Health -->
  <div v-else-if="activeTab === 'compliance' && compliance">
    <AppCard>
      <h3 class="mb-2 text-sm font-semibold text-gray-900">Last 30 Days</h3>
      <div class="flex items-center gap-3">
        <p class="text-3xl font-bold" :class="compliance.block_count > 10 ? 'text-red-600' : 'text-green-600'">{{ compliance.block_count }}</p>
        <p class="text-sm text-gray-500">Customer Blocks</p>
      </div>
      <p v-if="compliance.block_count > 10" class="mt-2 text-xs text-red-600">
        High block rate detected. Review your messaging strategy and template content.
      </p>
    </AppCard>
  </div>

  <!-- Agent Productivity -->
  <div v-else-if="activeTab === 'agents'">
    <AppCard :padding="false">
      <div v-if="agentStats.length === 0" class="py-8 text-center text-gray-400"><p class="text-sm">No agent data</p></div>
      <table v-else class="w-full">
        <thead class="border-b border-gray-200 bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Agent</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Conversations Handled</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="agent in agentStats" :key="agent.user_id" class="border-b border-gray-100">
            <td class="px-4 py-2.5 text-sm text-gray-700">{{ agent.user_id }}</td>
            <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ agent.conversations_handled }}</td>
          </tr>
        </tbody>
      </table>
    </AppCard>
  </div>
</template>
