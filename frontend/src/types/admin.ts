export interface AdminTenantData {
  id: string
  name: string
  slug: string
  type: string
  type_label: string
  status: string
  status_label: string
  plan_id: string | null
  plan_name: string | null
  user_count: number
  workspace_count: number
  trial_ends_at: string | null
  suspended_at: string | null
  suspension_reason: string | null
  onboarding_completed: boolean
  created_at: string
  updated_at: string
}

export interface UpdateTenantAdminRequest {
  name?: string
  plan_id?: string
  settings?: Record<string, unknown>
  metadata?: Record<string, unknown>
}

export interface AdminUserData {
  id: string
  name: string
  email: string
  status: string
  status_label: string
  role_in_tenant: string
  role_label: string
  tenant_id: string | null
  tenant_name: string | null
  avatar_url: string | null
  phone: string | null
  timezone: string | null
  language: string
  mfa_enabled: boolean
  email_verified_at: string | null
  last_login_at: string | null
  last_active_at: string | null
  created_at: string
  updated_at: string
}

export interface UpdateUserAdminRequest {
  name?: string
  role_in_tenant?: string
  timezone?: string
  language?: string
  mfa_enabled?: boolean
  settings?: Record<string, unknown>
}

export interface AdminPlanData {
  id: string
  code: string
  name: string
  description: string | null
  is_active: boolean
  is_public: boolean
  sort_order: number
  price_inr_monthly: string
  price_inr_yearly: string
  price_usd_monthly: string
  price_usd_yearly: string
  trial_days: number
  limits: Record<string, number>
  features: string[]
  metadata: Record<string, unknown> | null
  razorpay_plan_id_inr: string | null
  razorpay_plan_id_usd: string | null
  subscription_count: number
  created_at: string
  updated_at: string
}

export interface CreatePlanRequest {
  code: string
  name: string
  description?: string
  is_active?: boolean
  is_public?: boolean
  price_inr_monthly: number
  price_inr_yearly: number
  price_usd_monthly: number
  price_usd_yearly: number
  trial_days?: number
  sort_order?: number
  limits?: Record<string, number>
  features?: string[]
  metadata?: Record<string, unknown>
  razorpay_plan_id_inr?: string
  razorpay_plan_id_usd?: string
}

export interface UpdatePlanRequest {
  name?: string
  description?: string
  is_active?: boolean
  is_public?: boolean
  price_inr_monthly?: number
  price_inr_yearly?: number
  price_usd_monthly?: number
  price_usd_yearly?: number
  trial_days?: number
  sort_order?: number
  features?: string[]
  metadata?: Record<string, unknown>
  razorpay_plan_id_inr?: string
  razorpay_plan_id_usd?: string
}

export interface FeatureFlagData {
  id: string
  key: string
  name: string
  description: string | null
  is_enabled: boolean
  rollout_percentage: number
  allowed_plans: string[] | null
  allowed_tenants: string[] | null
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export interface CreateFeatureFlagRequest {
  key: string
  name: string
  description?: string
  is_enabled?: boolean
  rollout_percentage?: number
  allowed_plans?: string[]
  allowed_tenants?: string[]
  metadata?: Record<string, unknown>
}

export interface UpdateFeatureFlagRequest {
  name?: string
  description?: string
  is_enabled?: boolean
  rollout_percentage?: number
  allowed_plans?: string[]
  allowed_tenants?: string[]
  metadata?: Record<string, unknown>
}

export interface PlatformConfigData {
  id: string
  key: string
  value: unknown
  category: string
  category_label: string
  description: string | null
  is_sensitive: boolean
  updated_by: string | null
  updated_by_name: string | null
  created_at: string
  updated_at: string
}

export interface PlatformStatsData {
  total_tenants: number
  active_tenants: number
  suspended_tenants: number
  total_users: number
  active_users: number
  total_workspaces: number
  total_subscriptions: number
  active_subscriptions: number
  trial_subscriptions: number
  tenants_by_status: Record<string, number>
  tenants_by_plan: Record<string, number>
  users_by_status: Record<string, number>
  signups_by_month: Record<string, number>
  subscriptions_by_status: Record<string, number>
  generated_at: string
}

export interface SuspendRequest {
  reason: string
}

// ─── Integration types ────────────────────────────────

export interface IntegrationAccountStats {
  connected: number
  expiring: number
  expired: number
  revoked: number
}

export interface IntegrationListItem {
  id: string
  provider: string
  display_name: string
  platforms: string[]
  is_enabled: boolean
  status: string
  api_version: string
  has_credentials: boolean
  last_verified_at: string | null
  last_rotated_at: string | null
  account_stats: Record<string, IntegrationAccountStats>
  updated_at: string
}

export interface IntegrationDetail {
  id: string
  provider: string
  display_name: string
  platforms: string[]
  app_id_masked: string
  has_secret: boolean
  redirect_uris: Record<string, string>
  api_version: string
  scopes: Record<string, string[]>
  is_enabled: boolean
  status: string
  environment: string
  last_verified_at: string | null
  last_rotated_at: string | null
  updated_by: { id: string; name: string } | null
  created_at: string
  updated_at: string
}

export interface UpdateIntegrationRequest {
  app_id?: string
  app_secret?: string
  api_version?: string
  scopes?: Record<string, string[]>
  redirect_uris?: string[]
}

export interface IntegrationHealthSummary {
  total: number
  connected: number
  expiring: number
  expired: number
  revoked: number
  disconnected: number
}

export interface IntegrationHealthAccount {
  id: string
  tenant_id: string | null
  tenant_name: string
  workspace_id: string
  workspace_name: string
  platform: string
  account_name: string
  status: string
  token_expires_at: string | null
  connected_at: string | null
  last_refreshed_at: string | null
}

export interface ForceReauthResult {
  accounts_revoked: number
  tenants_affected: number
  tenants_notified: number
  platforms: string[]
}
