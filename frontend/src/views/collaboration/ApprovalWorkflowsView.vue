<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { approvalWorkflowApi } from '@/api/collaboration'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import type { ApprovalWorkflowData, ApprovalWorkflowStep } from '@/types/collaboration'
import type { PaginationMeta } from '@/types/api'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)
const workflows = ref<ApprovalWorkflowData[]>([])
const pagination = ref<PaginationMeta | null>(null)
const loading = ref(false)
const saving = ref(false)
const showCreate = ref(false)

const form = ref({
  name: '',
  steps: [{ order: 1, approver_user_ids: '', require_all: false }] as { order: number; approver_user_ids: string; require_all: boolean }[],
})

onMounted(() => fetchWorkflows())

async function fetchWorkflows(page = 1) {
  loading.value = true
  try {
    const res = await approvalWorkflowApi.list(workspaceId.value, { page })
    workflows.value = res.data
    pagination.value = res.meta
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { loading.value = false }
}

function addStep() {
  form.value.steps.push({
    order: form.value.steps.length + 1,
    approver_user_ids: '',
    require_all: false,
  })
}

function removeStep(index: number) {
  form.value.steps.splice(index, 1)
  form.value.steps.forEach((s, i) => { s.order = i + 1 })
}

async function createWorkflow() {
  saving.value = true
  try {
    const steps: ApprovalWorkflowStep[] = form.value.steps.map(s => ({
      order: s.order,
      approver_user_ids: s.approver_user_ids.split(',').map(id => id.trim()).filter(Boolean),
      require_all: s.require_all,
    }))
    await approvalWorkflowApi.create(workspaceId.value, { name: form.value.name, steps })
    toast.success('Workflow created')
    showCreate.value = false
    form.value = { name: '', steps: [{ order: 1, approver_user_ids: '', require_all: false }] }
    fetchWorkflows()
  } catch (e) { toast.error(parseApiError(e).message) }
  finally { saving.value = false }
}

async function setDefault(workflow: ApprovalWorkflowData) {
  try {
    await approvalWorkflowApi.setDefault(workspaceId.value, workflow.id)
    toast.success(`"${workflow.name}" set as default`)
    fetchWorkflows()
  } catch (e) { toast.error(parseApiError(e).message) }
}

async function deleteWorkflow(workflow: ApprovalWorkflowData) {
  if (!confirm(`Delete "${workflow.name}"?`)) return
  try {
    await approvalWorkflowApi.delete(workspaceId.value, workflow.id)
    toast.success('Deleted')
    fetchWorkflows()
  } catch (e) { toast.error(parseApiError(e).message) }
}
</script>

<template>
  <AppPageHeader title="Approval Workflows" description="Configure multi-step content approval processes">
    <template #actions>
      <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700" @click="showCreate = !showCreate">
        <i class="pi pi-plus mr-1" /> New Workflow
      </button>
    </template>
  </AppPageHeader>

  <AppCard v-if="showCreate" class="mb-4">
    <form class="space-y-3" @submit.prevent="createWorkflow">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Workflow Name *</label>
        <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <div class="mb-2 flex items-center justify-between">
          <label class="text-sm font-medium text-gray-700">Approval Steps</label>
          <button type="button" class="text-sm text-primary-600 hover:text-primary-700" @click="addStep"><i class="pi pi-plus mr-1" />Add Step</button>
        </div>
        <div v-for="(step, idx) in form.steps" :key="idx" class="mb-2 flex items-center gap-2 rounded-lg border border-gray-200 p-2">
          <span class="shrink-0 text-xs font-medium text-gray-500">Step {{ step.order }}</span>
          <input v-model="step.approver_user_ids" type="text" placeholder="Approver User IDs (comma-separated)" class="flex-1 rounded border border-gray-300 px-2 py-1.5 text-sm" />
          <label class="flex shrink-0 items-center gap-1 text-xs text-gray-600">
            <input v-model="step.require_all" type="checkbox" class="rounded border-gray-300" />
            Require All
          </label>
          <button v-if="form.steps.length > 1" type="button" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="removeStep(idx)"><i class="pi pi-times text-xs" /></button>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="showCreate = false">Cancel</button>
        <button type="submit" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppCard>

  <AppCard :padding="false">
    <div v-if="loading && workflows.length === 0" class="flex items-center justify-center py-12"><i class="pi pi-spin pi-spinner text-xl text-gray-400" /></div>
    <div v-else-if="workflows.length === 0" class="py-12 text-center text-gray-400"><i class="pi pi-sitemap mb-2 text-3xl" /><p class="text-sm">No approval workflows</p></div>
    <table v-else class="w-full">
      <thead class="border-b border-gray-200 bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Workflow</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Steps</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Default</th>
          <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="wf in workflows" :key="wf.id" class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-4 py-2.5">
            <p class="text-sm font-medium text-gray-900">{{ wf.name }}</p>
            <p class="text-xs text-gray-400">Created {{ new Date(wf.created_at).toLocaleDateString() }}</p>
          </td>
          <td class="px-4 py-2.5 text-sm text-gray-600">{{ wf.steps.length }} step{{ wf.steps.length !== 1 ? 's' : '' }}</td>
          <td class="px-4 py-2.5">
            <span :class="wf.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'" class="rounded px-2 py-0.5 text-xs">{{ wf.is_active ? 'Active' : 'Inactive' }}</span>
          </td>
          <td class="px-4 py-2.5">
            <span v-if="wf.is_default" class="rounded bg-primary-50 px-2 py-0.5 text-xs text-primary-700">Default</span>
          </td>
          <td class="px-4 py-2.5 text-right">
            <div class="flex items-center justify-end gap-1">
              <button v-if="!wf.is_default" class="rounded p-1 text-gray-400 hover:bg-primary-50 hover:text-primary-500" title="Set as Default" @click="setDefault(wf)"><i class="pi pi-star text-sm" /></button>
              <button class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500" @click="deleteWorkflow(wf)"><i class="pi pi-trash text-sm" /></button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </AppCard>
</template>
