<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { webhookApi } from '@/api/webhooks'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WebhookEndpointData, WebhookDeliveryData } from '@/types/webhooks'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const endpoints = ref<WebhookEndpointData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const expandedEndpointId = ref<string | null>(null)
const deliveries = ref<WebhookDeliveryData[]>([])
const deliveriesLoading = ref(false)

const eventOptions = [
  'post.published',
  'post.failed',
  'inbox.new_item',
  'payment.received',
  'subscription.created',
]

const form = ref({
  url: '',
  events: [] as string[],
  is_active: true,
})

onMounted(() => fetchEndpoints())

async function fetchEndpoints(page = 1) {
  loading.value = true
  try {
    const res = await webhookApi.list(workspaceId.value, { page })
    endpoints.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createEndpoint() {
  saving.value = true
  try {
    await webhookApi.create(workspaceId.value, {
      url: form.value.url,
      events: form.value.events,
      is_active: form.value.is_active,
    })
    toast.success('Webhook endpoint created')
    showCreate.value = false
    form.value = { url: '', events: [], is_active: true }
    fetchEndpoints()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function toggleExpand(endpoint: WebhookEndpointData) {
  if (expandedEndpointId.value === endpoint.id) {
    expandedEndpointId.value = null
    deliveries.value = []
    return
  }
  expandedEndpointId.value = endpoint.id
  deliveriesLoading.value = true
  try {
    const res = await webhookApi.deliveries(workspaceId.value, endpoint.id, { per_page: 10 })
    deliveries.value = res.data
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { deliveriesLoading.value = false }
}

async function toggleActive(endpoint: WebhookEndpointData) {
  try {
    await webhookApi.update(workspaceId.value, endpoint.id, { is_active: !endpoint.is_active })
    toast.success(endpoint.is_active ? 'Webhook deactivated' : 'Webhook activated')
    fetchEndpoints()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function testEndpoint(endpoint: WebhookEndpointData) {
  try {
    await webhookApi.test(workspaceId.value, endpoint.id)
    toast.success('Test webhook sent')
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteEndpoint(endpoint: WebhookEndpointData) {
  if (!confirm('Delete this webhook endpoint?')) return
  try {
    await webhookApi.delete(workspaceId.value, endpoint.id)
    toast.success('Deleted')
    if (expandedEndpointId.value === endpoint.id) {
      expandedEndpointId.value = null
      deliveries.value = []
    }
    fetchEndpoints()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function responseCodeClass(code: number | null): string {
  if (!code) return 'bg-gray-100 text-gray-500'
  if (code >= 200 && code < 300) return 'bg-green-50 text-green-700'
  if (code >= 400) return 'bg-red-50 text-red-700'
  return 'bg-yellow-50 text-yellow-700'
}
</script>

<template>
  <AppPageHeader title="Webhooks" description="Receive real-time event notifications via HTTP callbacks">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" /> New Endpoint
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createEndpoint">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Endpoint URL *</label>
        <input v-model="form.url" type="url" required placeholder="https://your-server.com/webhook" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-700">Events *</label>
        <div class="flex flex-wrap gap-3">
          <label v-for="event in eventOptions" :key="event" class="flex items-center gap-1.5 text-sm text-gray-700">
            <input v-model="form.events" :value="event" type="checkbox" class="rounded border-gray-300" />
            {{ event }}
          </label>
        </div>
      </div>
      <label class="flex items-center gap-2 text-sm text-gray-700">
        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
        Active immediately
      </label>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving || form.events.length === 0" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && endpoints.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="endpoints.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-arrows-h mb-2 text-3xl" /><p class="text-sm">No webhook endpoints</p></div>
    <div v-else>
      <table class="w-full">
        <thead class="border-b border-gray-200 bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Endpoint</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Events</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Failures</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Last Triggered</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="ep in endpoints" :key="ep.id">
            <tr class="cursor-pointer border-b border-gray-100 hover:bg-gray-50" @click="toggleExpand(ep)">
              <td class="px-4 py-2.5">
                <div class="flex items-center gap-1.5">
                  <i :class="expandedEndpointId === ep.id ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" class="text-xs text-gray-400" />
                  <span class="text-sm font-medium text-gray-900 truncate max-w-xs" :title="ep.url">{{ ep.url }}</span>
                </div>
              </td>
              <td class="px-4 py-2.5">
                <div class="flex flex-wrap gap-1">
                  <span v-for="ev in ep.events" :key="ev" class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{{ ev }}</span>
                </div>
              </td>
              <td class="px-4 py-2.5">
                <span :class="ep.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-2 py-0.5 text-xs">{{ ep.is_active ? 'Active' : 'Inactive' }}</span>
              </td>
              <td class="px-4 py-2.5">
                <span :class="ep.failure_count > 0 ? 'text-red-600' : 'text-gray-500'" class="text-sm">{{ ep.failure_count }}</span>
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-400">{{ ep.last_triggered_at ? new Date(ep.last_triggered_at).toLocaleString() : 'Never' }}</td>
              <td class="px-4 py-2.5 text-right" @click.stop>
                <div class="flex items-center justify-end gap-1">
                  <button class="rounded p-1 text-gray-400 hover:bg-blue-50 hover:text-blue-500" title="Send Test" @click="testEndpoint(ep)"><i class="pi pi-send text-sm" /></button>
                  <button class="rounded p-1 text-gray-400 hover:bg-primary-50 hover:text-primary-500" :title="ep.is_active ? 'Deactivate' : 'Activate'" @click="toggleActive(ep)">
                    <i :class="ep.is_active ? 'pi pi-pause' : 'pi pi-play'" class="text-sm" />
                  </button>
                  <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteEndpoint(ep)"><i class="pi pi-trash text-sm" /></button>
                </div>
              </td>
            </tr>
            <!-- Expanded Deliveries -->
            <tr v-if="expandedEndpointId === ep.id">
              <td colspan="6" class="bg-gray-50 px-6 py-3">
                <div v-if="deliveriesLoading" class="flex items-center justify-center py-4"><i class="pi pi-spin pi-spinner text-gray-400" /></div>
                <div v-else-if="deliveries.length === 0" class="py-4 text-center text-xs text-gray-400">No deliveries recorded</div>
                <div v-else>
                  <p class="mb-2 text-xs font-medium text-gray-500">Delivery Log</p>
                  <table class="w-full rounded-lg border border-gray-200 bg-white">
                    <thead class="bg-gray-100">
                      <tr>
                        <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500">Event</th>
                        <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500">Response Code</th>
                        <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500">Duration</th>
                        <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500">Timestamp</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="d in deliveries" :key="d.id" class="border-t border-gray-100">
                        <td class="px-3 py-1.5 text-xs text-gray-700">{{ d.event }}</td>
                        <td class="px-3 py-1.5">
                          <span :class="responseCodeClass(d.response_code)" class="rounded px-1.5 py-0.5 text-xs">{{ d.response_code ?? '-' }}</span>
                        </td>
                        <td class="px-3 py-1.5 text-xs text-gray-500">{{ d.duration_ms ? `${d.duration_ms}ms` : '-' }}</td>
                        <td class="px-3 py-1.5 text-xs text-gray-400">{{ d.delivered_at ? new Date(d.delivered_at).toLocaleString() : '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </AppCard>
</template>
