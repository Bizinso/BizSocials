import { get, post, del } from './client'
import type {
  SocialAccountData,
  OAuthUrlData,
  HealthStatusData,
  OAuthExchangeResult,
  OAuthConnectRequest,
} from '@/types/social'
import type { SocialPlatform } from '@/types/enums'

export const socialApi = {
  // Social accounts within workspace
  list(workspaceId: string) {
    return get<SocialAccountData[]>(`/workspaces/${workspaceId}/social-accounts`)
  },

  get(workspaceId: string, accountId: string) {
    return get<SocialAccountData>(`/workspaces/${workspaceId}/social-accounts/${accountId}`)
  },

  disconnect(workspaceId: string, accountId: string) {
    return del(`/workspaces/${workspaceId}/social-accounts/${accountId}`)
  },

  refresh(workspaceId: string, accountId: string) {
    return post<SocialAccountData>(`/workspaces/${workspaceId}/social-accounts/${accountId}/refresh`)
  },

  health(workspaceId: string) {
    return get<HealthStatusData>(`/workspaces/${workspaceId}/social-accounts/health`)
  },

  // OAuth flow
  getAuthorizationUrl(platform: SocialPlatform) {
    return get<OAuthUrlData>(`/oauth/${platform}/authorize`)
  },

  exchangeCode(platform: string, data: { code: string; state: string }) {
    return post<OAuthExchangeResult>(`/oauth/${platform}/exchange`, data)
  },

  connectOAuth(platform: string, data: OAuthConnectRequest) {
    return post<SocialAccountData>(`/oauth/${platform}/connect`, data)
  },
}
