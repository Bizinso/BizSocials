import type { UserStatus } from './enums'

export interface UserData {
  id: string
  name: string
  email: string
  avatar_url: string | null
  timezone: string | null
  status: UserStatus
  role_in_tenant: string | null
  email_verified_at: string | null
  created_at: string
}

export interface UpdateProfileRequest {
  name?: string
  timezone?: string | null
  phone?: string | null
  job_title?: string | null
}

export interface UpdateSettingsRequest {
  [key: string]: unknown
}
