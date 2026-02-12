<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { whatsappCampaignApi } from '@/api/whatsapp-marketing'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppCampaignData, CreateCampaignRequest, WhatsAppCampaignAudienceFilter } from '@/types/whatsapp-marketing'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import WhatsAppCampaignStats from '@/components/whatsapp/WhatsAppCampaignStats.vue'
import WhatsAppAudienceSelector from '@/components/whatsapp/WhatsAppAudienceSelector.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

const campaigns = ref<WhatsAppCampaignData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)
const selectedCampaign = ref<WhatsAppCampaignData | null>(null)

// Create form
const createForm = ref<CreateCampaignRequest>({
  whatsapp_phone_number_id: '',
  template_id: '',
  name: '',
  template_params_mapping: {},
  audience_filter: {},
})

const scheduleDate = ref('')

onMounted(() => fetchCampaigns())

async function fetchCampaigns(page = 1) {
  loading.value = true
  try {
    const res = await whatsappCampaignApi.list(workspaceId.value, { page })
    campaigns.value = res.data
    pagination.value = res.meta
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

async function createCampaign() {
  saving.value = true
  try {
    const campaign = await whatsappCampaignApi.create(workspaceId.value, createForm.value)
    toast.success('Campaign created')
    showCreate.value = false
    createForm.value = { whatsapp_phone_number_id: '', template_id: '', name: '', template_params_mapping: {}, audience_filter: {} }
    fetchCampaigns()
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

async function buildAudience(campaign: WhatsAppCampaignData) {
  try {
    const result = await whatsappCampaignApi.buildAudience(workspaceId.value, campaign.id)
    toast.success(`Audience built: ${result.recipients_count} recipients`)
    fetchCampaigns()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function scheduleCampaign(campaign: WhatsAppCampaignData) {
  if (!scheduleDate.value) {
    toast.error('Select a schedule date')
    return
  }
  try {
    await whatsappCampaignApi.schedule(workspaceId.value, campaign.id, scheduleDate.value)
    toast.success('Campaign scheduled')
    fetchCampaigns()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function sendCampaign(campaign: WhatsAppCampaignData) {
  if (!confirm('Send this campaign now?')) return
  try {
    await whatsappCampaignApi.send(workspaceId.value, campaign.id)
    toast.success('Campaign sending started')
    fetchCampaigns()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function cancelCampaign(campaign: WhatsAppCampaignData) {
  if (!confirm('Cancel this campaign?')) return
  try {
    await whatsappCampaignApi.cancel(workspaceId.value, campaign.id)
    toast.success('Campaign cancelled')
    fetchCampaigns()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

const statusBadge: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600',
  scheduled: 'bg-blue-100 text-blue-700',
  sending: 'bg-amber-100 text-amber-700',
  completed: 'bg-green-100 text-green-700',
  failed: 'bg-red-100 text-red-700',
  cancelled: 'bg-gray-200 text-gray-500',
}
</script>

<template>
  <AppPageHeader title="WhatsApp Campaigns" description="Send template-based marketing messages to opted-in contacts">
    <template #actions>
      <button class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" />
        New Campaign
      </button>
    </template>
  </AppPageHeader>

  <!-- Create form -->
  <AppCard v-if="showCreate" class="mb-4">
    <h3 class="mb-4 text-base font-semibold text-gray-900">Create Campaign</h3>
    <form class="space-y-3" @submit.prevent="createCampaign">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Campaign Name *</label>
        <input v-model="createForm.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Q1 Promo Campaign" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number ID *</label>
          <input v-model="createForm.whatsapp_phone_number_id" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="UUID" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Template ID *</label>
          <input v-model="createForm.template_id" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="UUID" />
        </div>
      </div>

      <WhatsAppAudienceSelector v-model="createForm.audience_filter!" />

      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
          <i v-if="saving" class="pi pi-spin pi-spinner mr-1" />
          Create
        </button>
      </div>
    </form>
  </AppCard>

  <!-- Stats dialog -->
  <Teleport to="body">
    <div v-if="selectedCampaign" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="selectedCampaign = null">
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">{{ selectedCampaign.name }}</h3>
          <button class="text-gray-400 hover:text-gray-600" @click="selectedCampaign = null"><i class="pi pi-times" /></button>
        </div>
        <WhatsAppCampaignStats :campaign="selectedCampaign" />
      </div>
    </div>
  </Teleport>

  <AppCard :padding="false">
    <div v-if="loading && campaigns.length === 0" class="flex items-center justify-center py-12">
      <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
    </div>

    <div v-else-if="campaigns.length === 0" class="py-12 text-center text-gray-400">
      <i class="pi pi-megaphone mb-2 text-3xl" />
      <p class="text-sm">No campaigns yet</p>
    </div>

    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Template</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Recipients</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Delivery</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in campaigns" :key="c.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <button class="text-sm font-medium text-primary-600 hover:underline" @click="selectedCampaign = c">{{ c.name }}</button>
            <p class="text-xs text-gray-400">{{ c.created_by_name }}</p>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ c.template_name || '-' }}</td>
          <td class="px-4 py-2.5">
            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge[c.status] || 'bg-gray-100'">{{ c.status }}</span>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ c.total_recipients }}</td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ c.delivery_rate }}%</td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button v-if="c.status === 'draft'" class="rounded p-1 text-sm text-blue-600 hover:bg-blue-50" title="Build Audience" @click="buildAudience(c)">
                <i class="pi pi-users" />
              </button>
              <button v-if="c.status === 'draft' && c.total_recipients > 0" class="rounded p-1 text-sm text-green-600 hover:bg-green-50" title="Send Now" @click="sendCampaign(c)">
                <i class="pi pi-send" />
              </button>
              <button v-if="c.status === 'draft' || c.status === 'scheduled'" class="rounded p-1 text-sm text-gray-400 hover:bg-red-50 hover:text-red-500" title="Cancel" @click="cancelCampaign(c)">
                <i class="pi pi-times-circle" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
