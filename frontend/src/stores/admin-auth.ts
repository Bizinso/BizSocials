import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { adminAuthApi, type AdminLoginRequest, type AdminProfile } from '@/api/admin-auth'
import { ADMIN_TOKEN_KEY } from '@/utils/constants'

export const useAdminAuthStore = defineStore('admin-auth', () => {
  const admin = ref<AdminProfile | null>(null)
  const token = ref<string | null>(localStorage.getItem(ADMIN_TOKEN_KEY))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  function setToken(newToken: string | null) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem(ADMIN_TOKEN_KEY, newToken)
    } else {
      localStorage.removeItem(ADMIN_TOKEN_KEY)
    }
  }

  async function login(data: AdminLoginRequest) {
    loading.value = true
    try {
      const response = await adminAuthApi.login(data)
      setToken(response.token)
      admin.value = {
        id: response.admin.id,
        name: response.admin.name,
        email: response.admin.email,
        role: response.admin.role,
        last_login_at: null,
      }
      return response
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await adminAuthApi.logout()
    } finally {
      admin.value = null
      setToken(null)
    }
  }

  async function fetchAdmin() {
    if (!token.value) return
    loading.value = true
    try {
      admin.value = await adminAuthApi.me()
    } catch {
      admin.value = null
      setToken(null)
    } finally {
      loading.value = false
    }
  }

  return {
    admin,
    token,
    loading,
    isAuthenticated,
    login,
    logout,
    fetchAdmin,
    setToken,
  }
})
