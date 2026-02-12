<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { parseApiError } from '@/utils/error-handler'
import { approvalWorkflowApi } from '@/api/collaboration'
import type { ApprovalWorkflowData, ApprovalWorkflowStep } from '@/types/collaboration'
import AppPageHeader from '@/components/shared/AppPageHeader.vue'
import AppCard from '@/components/shared/AppCard.vue'

const route = useRoute()
const toast = useToast()

const workspaceId = computed(() => route.params.workspaceId as string)

const workflows = ref<ApprovalWorkflowData[]>([])
const loading = ref(false)
const showForm = ref(false)
const editingId = ref<string | null>(null)

// Form state
const formName = ref('')
const formIsActive = ref(true)
const formSteps = ref<ApprovalWorkflowStep[]>([{ order: 1, approver_user_ids: [''], require_all: false }])

onMounted(() => fetchWorkflows())

async function fetchWorkflows() {
  loading.value = true
  try {
    const response = await approvalWorkflowApi.list(workspaceId.value)
    workflows.value = response.data
  } catch (e) {
    toast.error(parseApiError(e).message)
  } finally {
    loading.value = false
  }
}

function openCreateForm() {
  editingId.value = null
  formName.value = ''
  formIsActive.value = true
  formSteps.value = [{ order: 1, approver_user_ids: [''], require_all: false }]
  showForm.value = true
}

function openEditForm(workflow: ApprovalWorkflowData) {
  editingId.value = workflow.id
  formName.value = workflow.name
  formIsActive.value = workflow.is_active
  formSteps.value = workflow.steps.map((s) => ({
    ...s,
    approver_user_ids: [...s.approver_user_ids],
  }))
  showForm.value = true
}

function addStep() {
  formSteps.value.push({
    order: formSteps.value.length + 1,
    approver_user_ids: [''],
    require_all: false,
  })
}

function removeStep(index: number) {
  formSteps.value.splice(index, 1)
  formSteps.value.forEach((step, i) => {
    step.order = i + 1
  })
}

function addApproverToStep(stepIndex: number) {
  formSteps.value[stepIndex].approver_user_ids.push('')
}

function removeApproverFromStep(stepIndex: number, approverIndex: number) {
  formSteps.value[stepIndex].approver_user_ids.splice(approverIndex, 1)
}

async function saveWorkflow() {
  const cleanedSteps = formSteps.value.map((step) => ({
    ...step,
    approver_user_ids: step.approver_user_ids.filter((id) => id.trim() !== ''),
  }))

  const payload = {
    name: formName.value,
    is_active: formIsActive.value,
    steps: cleanedSteps,
  }

  try {
    if (editingId.value) {
      await approvalWorkflowApi.update(workspaceId.value, editingId.value, payload)
      toast.success('Workflow updated')
    } else {
      await approvalWorkflowApi.create(workspaceId.value, payload)
      toast.success('Workflow created')
    }
    showForm.value = false
    await fetchWorkflows()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function deleteWorkflow(workflow: ApprovalWorkflowData) {
  if (!confirm(`Delete workflow "${workflow.name}"?`)) return
  try {
    await approvalWorkflowApi.delete(workspaceId.value, workflow.id)
    toast.success('Workflow deleted')
    await fetchWorkflows()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}

async function setDefault(workflow: ApprovalWorkflowData) {
  try {
    await approvalWorkflowApi.setDefault(workspaceId.value, workflow.id)
    toast.success('Default workflow updated')
    await fetchWorkflows()
  } catch (e) {
    toast.error(parseApiError(e).message)
  }
}
</script>

<template>
  <AppPageHeader title="Approval Workflows" description="Configure multi-step approval workflows for content">
    <template #actions>
      <button
        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        @click="openCreateForm"
      >
        <i class="pi pi-plus text-sm"></i>
        New Workflow
      </button>
    </template>
  </AppPageHeader>

  <!-- Workflow Form -->
  <AppCard v-if="showForm" class="mb-6">
    <div class="space-y-4">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ editingId ? 'Edit Workflow' : 'Create Workflow' }}
      </h3>

      <div>
        <label class="block text-sm font-medium text-gray-700">Workflow Name</label>
        <input
          v-model="formName"
          type="text"
          placeholder="e.g. Marketing Content Review"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>

      <div class="flex items-center gap-2">
        <input
          v-model="formIsActive"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <label class="text-sm text-gray-700">Active</label>
      </div>

      <div>
        <div class="mb-2 flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700">Approval Steps</label>
          <button
            class="text-sm text-indigo-600 hover:text-indigo-800"
            @click="addStep"
          >
            + Add Step
          </button>
        </div>

        <div
          v-for="(step, stepIndex) in formSteps"
          :key="stepIndex"
          class="mb-4 rounded-lg border border-gray-200 p-4"
        >
          <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-900">Step {{ step.order }}</span>
            <button
              v-if="formSteps.length > 1"
              class="text-sm text-red-600 hover:text-red-800"
              @click="removeStep(stepIndex)"
            >
              Remove
            </button>
          </div>

          <div class="mb-2">
            <label class="text-xs text-gray-500">Approver User IDs</label>
            <div
              v-for="(_, approverIndex) in step.approver_user_ids"
              :key="approverIndex"
              class="mt-1 flex items-center gap-2"
            >
              <input
                v-model="step.approver_user_ids[approverIndex]"
                type="text"
                placeholder="User UUID"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
              <button
                v-if="step.approver_user_ids.length > 1"
                class="text-red-500 hover:text-red-700"
                @click="removeApproverFromStep(stepIndex, approverIndex)"
              >
                <i class="pi pi-times text-sm"></i>
              </button>
            </div>
            <button
              class="mt-1 text-xs text-indigo-600 hover:text-indigo-800"
              @click="addApproverToStep(stepIndex)"
            >
              + Add Approver
            </button>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="step.require_all"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <label class="text-xs text-gray-600">Require all approvers</label>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
          @click="saveWorkflow"
        >
          {{ editingId ? 'Update' : 'Create' }}
        </button>
        <button
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="showForm = false"
        >
          Cancel
        </button>
      </div>
    </div>
  </AppCard>

  <!-- Workflows List -->
  <AppCard>
    <div v-if="loading" class="py-8 text-center text-gray-500">Loading workflows...</div>

    <div v-else-if="workflows.length === 0" class="py-8 text-center text-gray-500">
      No approval workflows yet. Create one to get started.
    </div>

    <div v-else class="divide-y divide-gray-200">
      <div
        v-for="workflow in workflows"
        :key="workflow.id"
        class="flex items-start justify-between py-4"
      >
        <div>
          <div class="flex items-center gap-2">
            <h4 class="text-sm font-medium text-gray-900">{{ workflow.name }}</h4>
            <span
              v-if="workflow.is_default"
              class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800"
            >
              Default
            </span>
            <span
              :class="workflow.is_active
                ? 'bg-green-100 text-green-800'
                : 'bg-gray-100 text-gray-800'"
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
            >
              {{ workflow.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <div class="mt-1 text-sm text-gray-500">
            {{ workflow.steps.length }} step{{ workflow.steps.length !== 1 ? 's' : '' }}
            <span v-for="(step, i) in workflow.steps" :key="i" class="ml-2 text-xs text-gray-400">
              Step {{ step.order }}: {{ step.approver_user_ids.length }} approver{{ step.approver_user_ids.length !== 1 ? 's' : '' }}
              {{ step.require_all ? '(all required)' : '(any one)' }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-if="!workflow.is_default"
            class="text-sm text-indigo-600 hover:text-indigo-800"
            @click="setDefault(workflow)"
          >
            Set Default
          </button>
          <button
            class="text-sm text-gray-600 hover:text-gray-800"
            @click="openEditForm(workflow)"
          >
            <i class="pi pi-pencil"></i>
          </button>
          <button
            class="text-sm text-red-600 hover:text-red-800"
            @click="deleteWorkflow(workflow)"
          >
            <i class="pi pi-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </AppCard>
</template>
