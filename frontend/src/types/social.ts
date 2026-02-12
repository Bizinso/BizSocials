import type { SocialPlatform, SocialAccountStatus } from './enums'

export interface SocialAccountData {
  id: string
  workspace_id: string
  platform: SocialPlatform
  platform_account_id: string
  account_name: string
  account_username: string | null
  profile_image_url: string | null
  status: SocialAccountStatus
  is_healthy: boolean
  can_publish: boolean
  requires_reconnect: boolean
  token_expires_at: string | null
  connected_at: string
  last_refreshed_at: string | null
}

export interface OAuthUrlData {
  url: string
  state: string
  platform: string
}

export interface HealthStatusData {
  total_accounts: number
  connected_count: number
  expired_count: number
  revoked_count: number
  disconnected_count: number
  by_platform: Record<string, number>
}

export interface FacebookPage {
  id: string
  name: string
}

export interface OAuthExchangeAccount {
  platform_account_id: string
  account_name: string
  account_username: string | null
  profile_image_url: string | null
}

export interface OAuthExchangeResult {
  session_key: string
  platform: string
  account: OAuthExchangeAccount
  pages?: FacebookPage[]
}

export interface OAuthConnectRequest {
  workspace_id: string
  session_key: string
  page_id?: string | null
}
