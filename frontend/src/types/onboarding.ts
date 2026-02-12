export interface SubmitOrganizationRequest {
  name: string
  timezone: string
  industry: string
  country: string
}

export interface OnboardingData {
  id: string
  tenant_id: string
  current_step: string
  steps_completed: string[]
  started_at: string
  completed_at: string | null
}

export interface OrganizationSubmitResponse {
  profile: Record<string, unknown>
  onboarding: OnboardingData
}

export interface SubmitWorkspaceRequest {
  name: string
  purpose: 'marketing' | 'support' | 'brand' | 'agency'
  approval_mode: 'auto' | 'manual'
}

export interface WorkspaceSubmitResponse {
  workspace: Record<string, unknown>
  onboarding: OnboardingData
}
