import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { onboardingApi } from '@/api/onboarding'
import type { OnboardingData, SubmitOrganizationRequest, SubmitWorkspaceRequest } from '@/types/onboarding'

export const useOnboardingStore = defineStore('onboarding', () => {
  const onboarding = ref<OnboardingData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string[]>>({})

  const currentStep = computed(() => onboarding.value?.current_step ?? null)
  const stepsCompleted = computed(() => onboarding.value?.steps_completed ?? [])

  function isStepCompleted(step: string): boolean {
    return stepsCompleted.value.includes(step)
  }

  function setOnboarding(data: OnboardingData) {
    onboarding.value = data
  }

  async function submitOrganization(data: SubmitOrganizationRequest) {
    loading.value = true
    error.value = null
    fieldErrors.value = {}
    try {
      const response = await onboardingApi.submitOrganization(data)
      onboarding.value = response.onboarding
      return response
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosError.response?.data?.errors) {
        fieldErrors.value = axiosError.response.data.errors
      }
      error.value = axiosError.response?.data?.message ?? 'Failed to submit organization details'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function submitWorkspace(data: SubmitWorkspaceRequest) {
    loading.value = true
    error.value = null
    fieldErrors.value = {}
    try {
      const response = await onboardingApi.submitWorkspace(data)
      onboarding.value = response.onboarding
      return response
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosError.response?.data?.errors) {
        fieldErrors.value = axiosError.response.data.errors
      }
      error.value = axiosError.response?.data?.message ?? 'Failed to create workspace'
      throw err
    } finally {
      loading.value = false
    }
  }

  function clear() {
    onboarding.value = null
    error.value = null
    fieldErrors.value = {}
  }

  return {
    onboarding,
    loading,
    error,
    fieldErrors,
    currentStep,
    stepsCompleted,
    isStepCompleted,
    setOnboarding,
    submitOrganization,
    submitWorkspace,
    clear,
  }
})
