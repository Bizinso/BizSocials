import { post, get } from './client'
import type {
  AuthResponse,
  LoginRequest,
  RegisterRequest,
  ForgotPasswordRequest,
  ResetPasswordRequest,
  ChangePasswordRequest,
  RefreshTokenResponse,
} from '@/types/auth'

export const authApi = {
  login(data: LoginRequest) {
    return post<AuthResponse>('/auth/login', data)
  },

  register(data: RegisterRequest) {
    return post<AuthResponse>('/auth/register', data)
  },

  logout() {
    return post<void>('/auth/logout')
  },

  refresh() {
    return post<RefreshTokenResponse>('/auth/refresh')
  },

  forgotPassword(data: ForgotPasswordRequest) {
    return post<void>('/auth/forgot-password', data)
  },

  resetPassword(data: ResetPasswordRequest) {
    return post<void>('/auth/reset-password', data)
  },

  changePassword(data: ChangePasswordRequest) {
    return post<void>('/auth/change-password', data)
  },

  resendVerification() {
    return post<void>('/auth/resend-verification')
  },

  verifyEmail(id: string, hash: string) {
    return get<void>(`/auth/verify-email/${id}/${hash}`)
  },
}
