<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { whatsappTemplateApi } from '@/api/whatsapp-marketing'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppTemplateData, CreateTemplateRequest } from '@/types/whatsapp-marketing'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'
import WhatsAppTemplateEditor from '@/components/whatsapp/WhatsAppTemplateEditor.vue'
import WhatsAppTemplatePreview from '@/components/whatsapp/WhatsAppTemplatePreview.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

const templates = ref<WhatsAppTemplateData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const filterStatus = ref('')
const showEditor = ref(false)
const selectedTemplate = ref<WhatsAppTemplateData | null>(null)
const phoneNumberId = ref('')

onMounted(() => fetchTemplates())

watch(filterStatus, () => fetchTemplates())

async function fetchTemplates(page = 1) {
  loading.value = true
  try {
    const res = await whatsappTemplateApi.list(workspaceId.value, {
      page,
      status: filterStatus.value || undefined,
    })
    templates.value = res.data
    pagination.value = res.meta
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

async function saveTemplate(data: CreateTemplateRequest) {
  saving.value = true
  try {
    await whatsappTemplateApi.create(workspaceId.value, data)
    toast.success('Template created')
    showEditor.value = false
    fetchTemplates()
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    saving.value = false
  }
}

async function submitTemplate(template: WhatsAppTemplateData) {
  try {
    await whatsappTemplateApi.submit(workspaceId.value, template.id)
    toast.success('Template submitted for approval')
    fetchTemplates()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function syncTemplate(template: WhatsAppTemplateData) {
  try {
    await whatsappTemplateApi.sync(workspaceId.value, template.id)
    toast.success('Template status synced')
    fetchTemplates()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function deleteTemplate(template: WhatsAppTemplateData) {
  if (!confirm(`Delete template "${template.name}"?`)) return
  try {
    await whatsappTemplateApi.delete(workspaceId.value, template.id)
    toast.success('Template deleted')
    fetchTemplates()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

const statusBadge: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600',
  pending_approval: 'bg-amber-100 text-amber-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  disabled: 'bg-gray-200 text-gray-500',
  paused: 'bg-blue-100 text-blue-700',
}
</script>

<template>
  <AppPageHeader title="WhatsApp Templates" description="Create and manage message templates for WhatsApp campaigns">
    <template #actions>
      <button class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700" @click="showEditor = !showEditor">
        <i class="pi pi-plus mr-1" />
        New Template
      </button>
    </template>
  </AppPageHeader>

  <!-- Editor -->
  <AppCard v-if="showEditor" class="mb-4">
    <h3 class="mb-4 text-base font-semibold text-gray-900">Create Template</h3>
    <WhatsAppTemplateEditor :phone-number-id="phoneNumberId" :saving="saving" @save="saveTemplate" />
  </AppCard>

  <!-- Preview panel -->
  <Teleport to="body">
    <div
      v-if="selectedTemplate"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="selectedTemplate = null"
    >
      <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">{{ selectedTemplate.name }}</h3>
          <button class="text-gray-400 hover:text-gray-600" @click="selectedTemplate = null">
            <i class="pi pi-times" />
          </button>
        </div>
        <WhatsAppTemplatePreview :template="selectedTemplate" />
      </div>
    </div>
  </Teleport>

  <AppCard :padding="false">
    <!-- Filters -->
    <div class="border-b border-gray-200 px-4 py-3">
      <div class="flex gap-1">
        <button
          v-for="status in ['', 'draft', 'pending_approval', 'approved', 'rejected']"
          :key="status"
          class="rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors"
          :class="filterStatus === status ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:bg-gray-100'"
          @click="filterStatus = status"
        >
          {{ status ? status.replace('_', ' ') : 'All' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading && templates.length === 0" class="flex items-center justify-center py-12">
      <i class="pi pi-spin pi-spinner text-xl text-gray-400" />
    </div>

    <!-- Empty -->
    <div v-else-if="templates.length === 0" class="py-12 text-center text-gray-400">
      <i class="pi pi-file-edit mb-2 text-3xl" />
      <p class="text-sm">No templates found</p>
    </div>

    <!-- Table -->
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Category</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Language</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uses</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="tpl in templates" :key="tpl.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <button class="text-sm font-medium text-primary-600 hover:underline" @click="selectedTemplate = tpl">
              {{ tpl.name }}
            </button>
          </td>
          <td class="px-4 py-2.5 text-sm capitalize text-gray-600">{{ tpl.category }}</td>
          <td class="px-4 py-2.5">
            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge[tpl.status] || 'bg-gray-100'">
              {{ tpl.status.replace('_', ' ') }}
            </span>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-500">{{ tpl.language }}</td>
          <td class="px-4 py-2.5 text-sm text-gray-500">{{ tpl.usage_count }}</td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button
                v-if="tpl.status === 'draft' || tpl.status === 'rejected'"
                class="rounded p-1 text-sm text-green-600 hover:bg-green-50"
                title="Submit for Approval"
                @click="submitTemplate(tpl)"
              >
                <i class="pi pi-send" />
              </button>
              <button
                v-if="tpl.status === 'pending_approval'"
                class="rounded p-1 text-sm text-blue-600 hover:bg-blue-50"
                title="Sync Status"
                @click="syncTemplate(tpl)"
              >
                <i class="pi pi-refresh" />
              </button>
              <button class="rounded p-1 text-sm text-gray-400 hover:bg-red-50 hover:text-red-500" title="Delete" @click="deleteTemplate(tpl)">
                <i class="pi pi-trash" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
