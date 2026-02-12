import { get, put, del } from './client'
import type { UserData, UpdateProfileRequest, UpdateSettingsRequest } from '@/types/user'

export const userApi = {
  getProfile() {
    return get<UserData>('/user')
  },

  updateProfile(data: UpdateProfileRequest) {
    return put<UserData>('/user', data)
  },

  updateSettings(data: UpdateSettingsRequest) {
    return put<void>('/user/settings', data)
  },

  deleteAccount() {
    return del<void>('/user')
  },
}
