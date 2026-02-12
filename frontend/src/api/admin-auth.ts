import { post, get } from './client'

export interface AdminLoginRequest {
  email: string
  password: string
}

export interface AdminLoginResponse {
  admin: {
    id: string
    name: string
    email: string
    role: string
  }
  token: string
  expires_in: number
}

export interface AdminProfile {
  id: string
  name: string
  email: string
  role: string
  last_login_at: string | null
}

export const adminAuthApi = {
  login(data: AdminLoginRequest) {
    return post<AdminLoginResponse>('/admin/auth/login', data)
  },

  logout() {
    return post<null>('/admin/auth/logout')
  },

  me() {
    return get<AdminProfile>('/admin/auth/me')
  },
}
