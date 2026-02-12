import { get, post } from './client'
import type {
  SubmitOrganizationRequest,
  OrganizationSubmitResponse,
  SubmitWorkspaceRequest,
  WorkspaceSubmitResponse,
  OnboardingData,
} from '@/types/onboarding'

export const onboardingApi = {
  submitOrganization(data: SubmitOrganizationRequest) {
    return post<OrganizationSubmitResponse>('/onboarding/organization', data)
  },

  submitWorkspace(data: SubmitWorkspaceRequest) {
    return post<WorkspaceSubmitResponse>('/onboarding/workspace', data)
  },

  getStatus() {
    return get<OnboardingData>('/onboarding/status')
  },
}
