<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { inboxAutomationApi } from '@/api/inbox-extended'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { InboxAutomationRuleData, CreateAutomationRuleRequest } from '@/types/inbox-extended'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const rules = ref<InboxAutomationRuleData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)
const editingId = ref<string | null>(null)

const triggerTypes = [
  { value: 'new_item', label: 'New Item' },
  { value: 'keyword_match', label: 'Keyword Match' },
  { value: 'platform_match', label: 'Platform Match' },
]

const actionTypes = [
  { value: 'assign', label: 'Assign' },
  { value: 'tag', label: 'Tag' },
  { value: 'auto_reply', label: 'Auto Reply' },
  { value: 'archive', label: 'Archive' },
]

const form = ref({
  name: '',
  trigger_type: 'new_item',
  action_type: 'assign',
  priority: 0,
  trigger_keywords: '',
  trigger_platforms: '',
  action_user_id: '',
  action_tag_id: '',
  action_reply_content: '',
})

onMounted(() => fetchRules())

async function fetchRules(page = 1) {
  loading.value = true
  try {
    const res = await inboxAutomationApi.list(workspaceId.value, { page, per_page: 20 })
    rules.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

function resetForm() {
  form.value = { name: '', trigger_type: 'new_item', action_type: 'assign', priority: 0, trigger_keywords: '', trigger_platforms: '', action_user_id: '', action_tag_id: '', action_reply_content: '' }
  editingId.value = null
}

function startEdit(rule: InboxAutomationRuleData) {
  form.value = {
    name: rule.name,
    trigger_type: rule.trigger_type,
    action_type: rule.action_type,
    priority: rule.priority,
    trigger_keywords: (rule.trigger_conditions?.keywords as string[] || []).join(', '),
    trigger_platforms: (rule.trigger_conditions?.platforms as string[] || []).join(', '),
    action_user_id: (rule.action_params?.user_id as string) || '',
    action_tag_id: (rule.action_params?.tag_id as string) || '',
    action_reply_content: (rule.action_params?.content as string) || '',
  }
  editingId.value = rule.id
  showCreate.value = true
}

function buildTriggerConditions(): Record<string, unknown> | undefined {
  if (form.value.trigger_type === 'keyword_match' && form.value.trigger_keywords) {
    return { keywords: form.value.trigger_keywords.split(',').map(k => k.trim()).filter(Boolean) }
  }
  if (form.value.trigger_type === 'platform_match' && form.value.trigger_platforms) {
    return { platforms: form.value.trigger_platforms.split(',').map(p => p.trim()).filter(Boolean) }
  }
  return undefined
}

function buildActionParams(): Record<string, unknown> | undefined {
  if (form.value.action_type === 'assign' && form.value.action_user_id) {
    return { user_id: form.value.action_user_id }
  }
  if (form.value.action_type === 'tag' && form.value.action_tag_id) {
    return { tag_id: form.value.action_tag_id }
  }
  if (form.value.action_type === 'auto_reply' && form.value.action_reply_content) {
    return { content: form.value.action_reply_content }
  }
  return undefined
}

async function saveRule() {
  saving.value = true
  try {
    const data: CreateAutomationRuleRequest = {
      name: form.value.name,
      trigger_type: form.value.trigger_type,
      action_type: form.value.action_type,
      priority: form.value.priority,
    }
    const conditions = buildTriggerConditions()
    if (conditions) data.trigger_conditions = conditions
    const params = buildActionParams()
    if (params) data.action_params = params

    if (editingId.value) {
      await inboxAutomationApi.update(workspaceId.value, editingId.value, data)
      toast.success('Automation rule updated')
    } else {
      await inboxAutomationApi.create(workspaceId.value, data)
      toast.success('Automation rule created')
    }
    showCreate.value = false
    resetForm()
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function toggleActive(rule: InboxAutomationRuleData) {
  try {
    await inboxAutomationApi.update(workspaceId.value, rule.id, { is_active: !rule.is_active })
    toast.success(rule.is_active ? 'Rule deactivated' : 'Rule activated')
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteRule(rule: InboxAutomationRuleData) {
  if (!confirm(`Delete "${rule.name}"?`)) return
  try {
    await inboxAutomationApi.delete(workspaceId.value, rule.id)
    toast.success('Deleted')
    fetchRules()
  } catch (e) { toast.error(parseApiError(e).message) }
}

function getTriggerLabel(val: string) {
  return triggerTypes.find(t => t.value === val)?.label || val
}

function getActionLabel(val: string) {
  return actionTypes.find(a => a.value === val)?.label || val
}
</script>

<template>
  <AppPageHeader title="Inbox Automation" description="Set up rules to automatically process incoming inbox items">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate; resetForm()"><i class="pi pi-plus mr-1" /> New Rule</button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="saveRule">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Rule Name *</label>
        <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Auto-assign complaints" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Trigger *</label>
          <select v-model="form.trigger_type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option v-for="t in triggerTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Action *</label>
          <select v-model="form.action_type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option v-for="a in actionTypes" :key="a.value" :value="a.value">{{ a.label }}</option>
          </select>
        </div>
      </div>

      <!-- Trigger conditions -->
      <div v-if="form.trigger_type === 'keyword_match'">
        <label class="mb-1 block text-sm font-medium text-gray-700">Keywords (comma separated)</label>
        <input v-model="form.trigger_keywords" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="urgent, complaint, help" />
      </div>
      <div v-if="form.trigger_type === 'platform_match'">
        <label class="mb-1 block text-sm font-medium text-gray-700">Platforms (comma separated)</label>
        <input v-model="form.trigger_platforms" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="facebook, instagram" />
      </div>

      <!-- Action params -->
      <div v-if="form.action_type === 'assign'">
        <label class="mb-1 block text-sm font-medium text-gray-700">Assign to User ID</label>
        <input v-model="form.action_user_id" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="User UUID" />
      </div>
      <div v-if="form.action_type === 'tag'">
        <label class="mb-1 block text-sm font-medium text-gray-700">Tag ID</label>
        <input v-model="form.action_tag_id" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Tag UUID" />
      </div>
      <div v-if="form.action_type === 'auto_reply'">
        <label class="mb-1 block text-sm font-medium text-gray-700">Reply Content</label>
        <textarea v-model="form.action_reply_content" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Thank you for reaching out..." />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
        <input v-model.number="form.priority" type="number" min="0" class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false; resetForm()">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">{{ editingId ? 'Update' : 'Create' }}</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && rules.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="rules.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-bolt mb-2 text-3xl" /><p class="text-sm">No automation rules</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Rule</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Trigger</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Action</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Priority</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Executions</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Active</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="rule in rules" :key="rule.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <p class="text-sm font-medium text-gray-900">{{ rule.name }}</p>
          </td>
          <td class="px-4 py-2.5">
            <span class="rounded bg-purple-50 px-2 py-0.5 text-xs text-purple-700">{{ getTriggerLabel(rule.trigger_type) }}</span>
          </td>
          <td class="px-4 py-2.5">
            <span class="rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">{{ getActionLabel(rule.action_type) }}</span>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ rule.priority }}</td>
          <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ rule.execution_count }}</td>
          <td class="px-4 py-2.5">
            <button @click="toggleActive(rule)" :class="rule.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-2 py-0.5 text-xs">{{ rule.is_active ? 'Active' : 'Inactive' }}</button>
          </td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button class="rounded p-1 text-gray-400 hover:bg-blue-50 hover:text-blue-500" title="Edit" @click="startEdit(rule)"><i class="pi pi-pencil text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" title="Delete" @click="deleteRule(rule)"><i class="pi pi-trash text-sm" /></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
