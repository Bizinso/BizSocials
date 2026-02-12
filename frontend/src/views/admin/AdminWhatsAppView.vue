<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { whatsappAdminApi } from '@/api/whatsapp-admin'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type {
  WhatsAppAdminAccountData,
  WhatsAppAdminAccountDetailData,
  AccountRiskAlertData,
  ConsentLogsData,
} from '@/types/whatsapp-governance'
import type { PaginationMeta } from '@/types/api'

const toast = useToast()

const activeTab = ref<'accounts' | 'alerts'>('accounts')

// Accounts
const accounts = ref<WhatsAppAdminAccountData[]>([])
const accountsPagination = ref<PaginationMeta | null>(null)
const loadingAccounts = ref(false)

// Alerts
const alerts = ref<AccountRiskAlertData[]>([])
const alertsPagination = ref<PaginationMeta | null>(null)
const loadingAlerts = ref(false)
const alertSeverityFilter = ref('')

// Detail panel
const selectedAccount = ref<WhatsAppAdminAccountDetailData | null>(null)
const consentLogs = ref<ConsentLogsData | null>(null)
const showDetail = ref(false)
const loadingDetail = ref(false)

// Suspend dialog
const showSuspend = ref(false)
const suspendReason = ref('')
const suspendAccountId = ref('')

// Rate limit dialog
const showRateLimit = ref(false)
const rateLimitPhoneId = ref('')
const rateLimitValue = ref(1000)

onMounted(() => {
  fetchAccounts()
  fetchAlerts()
})

async function fetchAccounts(page = 1) {
  loadingAccounts.value = true
  try {
    const res = await whatsappAdminApi.listAccounts({ page, per_page: 20 })
    accounts.value = res.data
    accountsPagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loadingAccounts.value = false }
}

async function fetchAlerts(page = 1) {
  loadingAlerts.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 20, resolved: false }
    if (alertSeverityFilter.value) params.severity = alertSeverityFilter.value
    const res = await whatsappAdminApi.listAlerts(params)
    alerts.value = res.data
    alertsPagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loadingAlerts.value = false }
}

async function viewAccountDetail(account: WhatsAppAdminAccountData) {
  showDetail.value = true
  loadingDetail.value = true
  try {
    const [detail, consent] = await Promise.all([
      whatsappAdminApi.getAccountDetail(account.id),
      whatsappAdminApi.getConsentLogs(account.id),
    ])
    selectedAccount.value = detail
    consentLogs.value = consent
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loadingDetail.value = false }
}

function openSuspendDialog(accountId: string) {
  suspendAccountId.value = accountId
  suspendReason.value = ''
  showSuspend.value = true
}

async function confirmSuspend() {
  try {
    await whatsappAdminApi.suspendAccount(suspendAccountId.value, suspendReason.value)
    toast.success('Account suspended')
    showSuspend.value = false
    fetchAccounts()
    if (selectedAccount.value?.account.id === suspendAccountId.value) showDetail.value = false
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function reactivateAccount(accountId: string) {
  try {
    await whatsappAdminApi.reactivateAccount(accountId)
    toast.success('Account reactivated')
    fetchAccounts()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function disableMarketing(accountId: string) {
  try {
    await whatsappAdminApi.disableMarketing(accountId)
    toast.success('Marketing disabled')
    fetchAccounts()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function enableMarketing(accountId: string) {
  try {
    await whatsappAdminApi.enableMarketing(accountId)
    toast.success('Marketing enabled')
    fetchAccounts()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function openRateLimitDialog(phoneId: string, currentLimit: number) {
  rateLimitPhoneId.value = phoneId
  rateLimitValue.value = currentLimit
  showRateLimit.value = true
}

async function confirmRateLimit() {
  try {
    await whatsappAdminApi.overrideRateLimit(rateLimitPhoneId.value, rateLimitValue.value)
    toast.success('Rate limit updated')
    showRateLimit.value = false
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function acknowledgeAlert(alertId: string) {
  try {
    await whatsappAdminApi.acknowledgeAlert(alertId)
    toast.success('Alert acknowledged')
    fetchAlerts()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function qualityColor(rating: string): string {
  if (rating === 'green') return 'text-green-600 bg-green-50'
  if (rating === 'yellow') return 'text-amber-600 bg-amber-50'
  if (rating === 'red') return 'text-red-600 bg-red-50'
  return 'text-gray-600 bg-gray-50'
}

function severityColor(severity: string): string {
  if (severity === 'critical') return 'text-red-700 bg-red-50 border-red-200'
  if (severity === 'warning') return 'text-amber-700 bg-amber-50 border-amber-200'
  return 'text-blue-700 bg-blue-50 border-blue-200'
}

function statusColor(status: string): string {
  if (status === 'verified') return 'text-green-700 bg-green-50'
  if (status === 'suspended' || status === 'banned') return 'text-red-700 bg-red-50'
  return 'text-amber-700 bg-amber-50'
}
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">WhatsApp Administration</h1>

    <!-- Tabs -->
    <div class="mb-4 flex gap-1 rounded-lg bg-gray-100 p-1">
      <button
        v-for="tab in (['accounts', 'alerts'] as const)"
        :key="tab"
        class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium capitalize transition-colors"
        :class="activeTab === tab ? 'bg-white text-green-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
        @click="activeTab = tab"
      >
        {{ tab }}
        <span v-if="tab === 'alerts' && alerts.length" class="ml-1 rounded-full bg-red-100 px-1.5 text-xs text-red-700">{{ alerts.length }}</span>
      </button>
    </div>

    <!-- Accounts Tab -->
    <div v-if="activeTab === 'accounts'">
      <div v-if="loadingAccounts" class="flex items-center justify-center py-12">
        <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
      </div>
      <div v-else-if="accounts.length === 0" class="rounded-lg border border-gray-200 py-12 text-center text-gray-400">
        <i class="pi pi-whatsapp mb-2 text-3xl" />
        <p class="text-sm">No WhatsApp Business Accounts</p>
      </div>
      <div v-else class="overflow-hidden rounded-lg border border-gray-200">
        <table class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Account</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tenant</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quality</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Marketing</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Alerts</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="account in accounts" :key="account.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-2.5">
                <button class="text-sm font-medium text-blue-600 hover:underline" @click="viewAccountDetail(account)">{{ account.name }}</button>
                <p class="text-xs text-gray-400">{{ account.waba_id }}</p>
              </td>
              <td class="px-4 py-2.5 text-sm text-gray-600">{{ account.tenant?.name || '-' }}</td>
              <td class="px-4 py-2.5">
                <span class="rounded px-2 py-0.5 text-xs font-medium" :class="statusColor(account.status)">{{ account.status }}</span>
              </td>
              <td class="px-4 py-2.5">
                <span class="rounded px-2 py-0.5 text-xs font-medium" :class="qualityColor(account.quality_rating)">{{ account.quality_rating }}</span>
              </td>
              <td class="px-4 py-2.5">
                <span v-if="account.is_marketing_enabled" class="text-green-600"><i class="pi pi-check text-sm" /></span>
                <span v-else class="text-gray-400"><i class="pi pi-times text-sm" /></span>
              </td>
              <td class="px-4 py-2.5">
                <span v-if="account.unresolved_alerts_count" class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ account.unresolved_alerts_count }}</span>
                <span v-else class="text-xs text-gray-400">0</span>
              </td>
              <td class="px-4 py-2.5 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button v-if="account.status !== 'suspended'" class="rounded p-1 text-xs text-gray-500 hover:bg-red-50 hover:text-red-600" title="Suspend" @click="openSuspendDialog(account.id)">
                    <i class="pi pi-ban text-sm" />
                  </button>
                  <button v-else class="rounded p-1 text-xs text-gray-500 hover:bg-green-50 hover:text-green-600" title="Reactivate" @click="reactivateAccount(account.id)">
                    <i class="pi pi-check-circle text-sm" />
                  </button>
                  <button v-if="account.is_marketing_enabled" class="rounded p-1 text-xs text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Disable Marketing" @click="disableMarketing(account.id)">
                    <i class="pi pi-pause text-sm" />
                  </button>
                  <button v-else class="rounded p-1 text-xs text-gray-500 hover:bg-green-50 hover:text-green-600" title="Enable Marketing" @click="enableMarketing(account.id)">
                    <i class="pi pi-play text-sm" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Alerts Tab -->
    <div v-if="activeTab === 'alerts'">
      <div class="mb-3 flex gap-2">
        <button v-for="sev in ['', 'critical', 'warning', 'info']" :key="sev" class="rounded-lg px-3 py-1 text-xs font-medium transition-colors" :class="alertSeverityFilter === sev ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" @click="alertSeverityFilter = sev; fetchAlerts()">
          {{ sev || 'All' }}
        </button>
      </div>
      <div v-if="loadingAlerts" class="flex items-center justify-center py-12">
        <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
      </div>
      <div v-else-if="alerts.length === 0" class="rounded-lg border border-gray-200 py-12 text-center text-gray-400">
        <i class="pi pi-check-circle mb-2 text-3xl" />
        <p class="text-sm">No unresolved alerts</p>
      </div>
      <div v-else class="space-y-2">
        <div v-for="alert in alerts" :key="alert.id" class="rounded-lg border p-3" :class="severityColor(alert.severity)">
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center gap-2">
                <span class="rounded px-1.5 py-0.5 text-xs font-bold uppercase" :class="severityColor(alert.severity)">{{ alert.severity }}</span>
                <h3 class="text-sm font-medium">{{ alert.title }}</h3>
              </div>
              <p class="mt-1 text-xs opacity-80">{{ alert.description }}</p>
              <p v-if="alert.recommended_action" class="mt-1 text-xs font-medium opacity-90">Recommended: {{ alert.recommended_action }}</p>
              <p v-if="alert.auto_action_taken" class="mt-1 text-xs italic opacity-70">Auto-action: {{ alert.auto_action_taken }}</p>
            </div>
            <button v-if="!alert.acknowledged_at" class="rounded bg-white/50 px-2 py-1 text-xs font-medium hover:bg-white" @click="acknowledgeAlert(alert.id)">Acknowledge</button>
            <span v-else class="text-xs opacity-60">Acknowledged</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Panel (modal overlay) -->
    <div v-if="showDetail" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showDetail = false">
      <div class="max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-900">Account Detail</h2>
          <button class="text-gray-400 hover:text-gray-600" @click="showDetail = false"><i class="pi pi-times" /></button>
        </div>
        <div v-if="loadingDetail" class="flex items-center justify-center py-8"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
        <div v-else-if="selectedAccount">
          <div class="mb-4 grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Status</p>
              <span class="rounded px-2 py-0.5 text-sm font-medium" :class="statusColor(selectedAccount.account.status)">{{ selectedAccount.account.status }}</span>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Quality</p>
              <span class="rounded px-2 py-0.5 text-sm font-medium" :class="qualityColor(selectedAccount.account.quality_rating)">{{ selectedAccount.account.quality_rating }}</span>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Conversations</p>
              <p class="text-lg font-bold text-gray-900">{{ selectedAccount.stats.active_conversations }} <span class="text-xs font-normal text-gray-400">/ {{ selectedAccount.stats.total_conversations }}</span></p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Templates</p>
              <p class="text-lg font-bold text-gray-900">{{ selectedAccount.stats.approved_templates }} <span class="text-xs font-normal text-gray-400">/ {{ selectedAccount.stats.total_templates }}</span></p>
            </div>
          </div>

          <!-- Phone Numbers -->
          <div v-if="selectedAccount.account.phone_numbers?.length" class="mb-4">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">Phone Numbers</h3>
            <div v-for="phone in selectedAccount.account.phone_numbers" :key="phone.id" class="flex items-center justify-between rounded-lg border border-gray-200 p-2">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ phone.phone_number }}</p>
                <p class="text-xs text-gray-400">{{ phone.display_name }} &middot; {{ phone.daily_send_count }}/{{ phone.daily_send_limit }} sent today</p>
              </div>
              <button class="rounded px-2 py-1 text-xs text-blue-600 hover:bg-blue-50" @click="openRateLimitDialog(phone.id, phone.daily_send_limit)">Set Limit</button>
            </div>
          </div>

          <!-- Consent Logs -->
          <div v-if="consentLogs" class="mb-4">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">Compliance</h3>
            <div class="rounded-lg bg-gray-50 p-3 text-sm">
              <p><span class="text-gray-500">Accepted:</span> {{ consentLogs.compliance_accepted_at || 'Not yet' }}</p>
              <p><span class="text-gray-500">By:</span> {{ consentLogs.compliance_accepted_by || '-' }}</p>
              <p><span class="text-gray-500">Marketing:</span> {{ consentLogs.marketing_enabled ? 'Enabled' : 'Disabled' }}</p>
            </div>
          </div>

          <!-- Recent Alerts -->
          <div v-if="selectedAccount.alerts.length">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">Recent Alerts</h3>
            <div v-for="alert in selectedAccount.alerts.slice(0, 5)" :key="alert.id" class="mb-1 rounded border p-2 text-xs" :class="severityColor(alert.severity)">
              <span class="font-bold uppercase">{{ alert.severity }}</span> {{ alert.title }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Suspend Dialog -->
    <div v-if="showSuspend" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showSuspend = false">
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-3 text-base font-semibold text-gray-900">Suspend Account</h3>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Reason *</label>
          <textarea v-model="suspendReason" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Reason for suspension..." />
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showSuspend = false">Cancel</button>
          <button :disabled="!suspendReason.trim()" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50" @click="confirmSuspend">Suspend</button>
        </div>
      </div>
    </div>

    <!-- Rate Limit Dialog -->
    <div v-if="showRateLimit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showRateLimit = false">
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-3 text-base font-semibold text-gray-900">Override Rate Limit</h3>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Daily Send Limit</label>
          <input v-model.number="rateLimitValue" type="number" min="100" max="100000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showRateLimit = false">Cancel</button>
          <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="confirmRateLimit">Update</button>
        </div>
      </div>
    </div>
  </div>
</template>
