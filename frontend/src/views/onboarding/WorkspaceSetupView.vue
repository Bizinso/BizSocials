<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useOnboardingStore } from '@/stores/onboarding'
import { useWorkspaceStore } from '@/stores/workspace'
import AuthLayout from '@/layouts/AuthLayout.vue'
import type { SubmitWorkspaceRequest } from '@/types/onboarding'

const router = useRouter()
const onboardingStore = useOnboardingStore()
const workspaceStore = useWorkspaceStore()

const form = reactive<SubmitWorkspaceRequest>({
  name: '',
  purpose: '' as SubmitWorkspaceRequest['purpose'],
  approval_mode: 'auto',
})

const localErrors = ref<Record<string, string>>({})
const submitted = ref(false)

function validateField(field: string): boolean {
  const errors: Record<string, string> = {}

  if (field === 'name' || field === 'all') {
    if (!form.name.trim()) errors.name = 'Workspace name is required'
    else if (form.name.trim().length < 2) errors.name = 'Name must be at least 2 characters'
    else if (form.name.trim().length > 100) errors.name = 'Name must be 100 characters or less'
  }

  if (field === 'purpose' || field === 'all') {
    if (!form.purpose) errors.purpose = 'Purpose is required'
  }

  if (field === 'approval_mode' || field === 'all') {
    if (!form.approval_mode) errors.approval_mode = 'Approval mode is required'
  }

  if (field === 'all') {
    localErrors.value = errors
  } else {
    localErrors.value = { ...localErrors.value, ...errors }
    if (!errors[field]) {
      delete localErrors.value[field]
    }
  }

  return Object.keys(errors).length === 0
}

function getFieldError(field: string): string | undefined {
  return localErrors.value[field] || onboardingStore.fieldErrors[field]?.[0]
}

async function handleSubmit() {
  submitted.value = true

  if (!validateField('all')) return

  try {
    const response = await onboardingStore.submitWorkspace({
      name: form.name.trim(),
      purpose: form.purpose,
      approval_mode: form.approval_mode,
    })

    // Add workspace to store and set as current
    if (response.workspace) {
      const ws = response.workspace as { id: string }
      workspaceStore.setCurrentWorkspace(ws.id)
      await workspaceStore.fetchWorkspaces()
    }

    router.push({ name: 'dashboard' })
  } catch {
    // Error is handled by the store
  }
}
</script>

<template>
  <AuthLayout>
    <div class="mx-auto max-w-lg">
      <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
          <h2 class="text-2xl font-bold text-gray-900">Create your workspace</h2>
          <p class="mt-2 text-sm text-gray-600">
            A workspace is where your team collaborates on social media content.
          </p>
        </div>

        <div
          v-if="onboardingStore.error && !Object.keys(onboardingStore.fieldErrors).length"
          class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
          role="alert"
        >
          {{ onboardingStore.error }}
        </div>

        <form @submit.prevent="handleSubmit" novalidate>
          <div class="space-y-4">
            <!-- Workspace Name -->
            <div>
              <label for="workspace-name" class="mb-1 block text-sm font-medium text-gray-700">
                Workspace name
              </label>
              <input
                id="workspace-name"
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="getFieldError('name') ? 'border-red-300' : 'border-gray-300'"
                placeholder="e.g. Marketing Team"
                @blur="validateField('name')"
              />
              <p v-if="getFieldError('name')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('name') }}
              </p>
            </div>

            <!-- Purpose -->
            <div>
              <label for="purpose" class="mb-1 block text-sm font-medium text-gray-700">
                What will this workspace be used for?
              </label>
              <select
                id="purpose"
                v-model="form.purpose"
                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="getFieldError('purpose') ? 'border-red-300' : 'border-gray-300'"
                @blur="validateField('purpose')"
              >
                <option value="" disabled>Select purpose</option>
                <option value="marketing">Marketing</option>
                <option value="support">Customer Support</option>
                <option value="brand">Brand Management</option>
                <option value="agency">Agency / Multi-client</option>
              </select>
              <p v-if="getFieldError('purpose')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('purpose') }}
              </p>
            </div>

            <!-- Approval Mode -->
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Content approval mode
              </label>
              <div class="space-y-2">
                <label
                  class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                  :class="form.approval_mode === 'auto' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                >
                  <input
                    v-model="form.approval_mode"
                    type="radio"
                    name="approval_mode"
                    value="auto"
                    class="mt-0.5"
                  />
                  <div>
                    <span class="text-sm font-medium text-gray-900">Auto-approve</span>
                    <p class="text-xs text-gray-500">Posts are published without review</p>
                  </div>
                </label>
                <label
                  class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                  :class="form.approval_mode === 'manual' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                >
                  <input
                    v-model="form.approval_mode"
                    type="radio"
                    name="approval_mode"
                    value="manual"
                    class="mt-0.5"
                  />
                  <div>
                    <span class="text-sm font-medium text-gray-900">Manual approval</span>
                    <p class="text-xs text-gray-500">Posts require admin approval before publishing</p>
                  </div>
                </label>
              </div>
              <p v-if="getFieldError('approval_mode')" class="mt-1 text-xs text-red-600">
                {{ getFieldError('approval_mode') }}
              </p>
            </div>
          </div>

          <button
            type="submit"
            class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="onboardingStore.loading"
          >
            <span v-if="onboardingStore.loading">Creating workspace...</span>
            <span v-else>Create workspace</span>
          </button>
        </form>
      </div>
    </div>
  </AuthLayout>
</template>
