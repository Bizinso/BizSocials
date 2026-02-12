<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { whatsappAutomationApi } from '@/api/whatsapp-automation'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { WhatsAppAutomationRuleData, CreateAutomationRuleRequest } from '@/types/whatsapp-automation'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const rules = ref<WhatsAppAutomationRuleData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref<CreateAutomationRuleRequest>({
  name: '',
  trigger_type: 'new_conversation',
  trigger_conditions: {},
  action_type: 'auto_reply',
  action_params: {},
  priority: 0,
})

onMounted(() => fetchRules())

async function fetchRules(page = 1) {
  loading.value = true
  try {
    const res = await whatsappAutomationApi.listRules(workspaceId.value, { page })
    rules.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

async function createRule() {
  saving.value = true
  try {
    await whatsappAutomationApi.createRule(workspaceId.value, form.value)
    toast.success('Rule created')
    showCreate.value = false
    form.value = { name: '', trigger_type: 'new_conversation', trigger_conditions: {}, action_type: 'auto_reply', action_params: {}, priority: 0 }
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function toggleRule(rule: WhatsAppAutomationRuleData) {
  try {
    await whatsappAutomationApi.updateRule(workspaceId.value, rule.id, { is_active: !rule.is_active })
    toast.success(rule.is_active ? 'Rule disabled' : 'Rule enabled')
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteRule(rule: WhatsAppAutomationRuleData) {
  if (!confirm(`Delete rule "${rule.name}"?`)) return
  try {
    await whatsappAutomationApi.deleteRule(workspaceId.value, rule.id)
    toast.success('Rule deleted')
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
}

const triggerLabels: Record<string, string> = {
  new_conversation: 'New Conversation',
  keyword_match: 'Keyword Match',
  outside_business_hours: 'Outside Business Hours',
  no_response_timeout: 'No Response Timeout',
}

const actionLabels: Record<string, string> = {
  auto_reply: 'Auto Reply',
  assign_user: 'Assign User',
  assign_team: 'Assign Team',
  add_tag: 'Add Tag',
  send_template: 'Send Template',
}
</script>

<template>
  <AppPageHeader title="WhatsApp Automation" description="Automate conversation handling with rules">
    <template #actions>
      <button class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" />
        New Rule
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <h3 class="mb-4 text-base font-semibold text-gray-900">Create Automation Rule</h3>
    <form class="space-y-3" @submit.prevent="createRule">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Rule Name *</label>
        <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Trigger</label>
          <select v-model="form.trigger_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option v-for="(label, key) in triggerLabels" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Action</label>
          <select v-model="form.action_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option v-for="(label, key) in actionLabels" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
        <input v-model.number="form.priority" type="number" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && rules.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="rules.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-bolt mb-2 text-3xl" /><p class="text-sm">No automation rules</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Trigger</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Action</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Runs</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Active</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="rule in rules" :key="rule.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ rule.name }}</td>
          <td class="px-4 py-2.5"><span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ triggerLabels[rule.trigger_type] }}</span></td>
          <td class="px-4 py-2.5"><span class="rounded bg-purple-50 px-2 py-0.5 text-xs text-purple-700">{{ actionLabels[rule.action_type] }}</span></td>
          <td class="px-4 py-2.5 text-sm text-gray-500">{{ rule.execution_count }}</td>
          <td class="px-4 py-2.5">
            <button class="relative h-5 w-9 rounded-full transition-colors" :class="rule.is_active ? 'bg-green-500' : 'bg-gray-300'" @click="toggleRule(rule)">
              <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform" :class="rule.is_active ? 'left-[18px]' : 'left-0.5'" />
            </button>
          </td>
          <td class="px-4 py-2.5 text-right">
            <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteRule(rule)"><i class="pi pi-trash text-sm" /></button>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
