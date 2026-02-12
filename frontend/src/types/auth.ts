import type { UserData } from './user'

export interface AuthResponse {
  user: UserData
  token: string
  token_type: string
  expires_in: number | null
}

export interface LoginRequest {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterRequest {
  name: string
  email: string
  password: string
  password_confirmation: string
  tenant_id?: string | null
}

export interface ForgotPasswordRequest {
  email: string
}

export interface ResetPasswordRequest {
  email: string
  token: string
  password: string
  password_confirmation: string
}

export interface ChangePasswordRequest {
  current_password: string
  password: string
  password_confirmation: string
}

export interface RefreshTokenResponse {
  token: string
  token_type: string
  expires_in: number
}
