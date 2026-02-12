import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import { userApi } from '@/api/user'
import type { UserData } from '@/types/user'
import type { LoginRequest, RegisterRequest } from '@/types/auth'
import { AUTH_TOKEN_KEY } from '@/utils/constants'

export const useAuthStore = defineStore(
  'auth',
  () => {
    const user = ref<UserData | null>(null)
    const token = ref<string | null>(localStorage.getItem(AUTH_TOKEN_KEY))
    const loading = ref(false)

    // Impersonation state
    const originalToken = ref<string | null>(null)
    const impersonatedTenantName = ref<string | null>(null)

    const isAuthenticated = computed(() => !!token.value)
    const isEmailVerified = computed(() => !!user.value?.email_verified_at)
    const isImpersonating = computed(() => !!originalToken.value)

    function setToken(newToken: string | null) {
      token.value = newToken
      if (newToken) {
        localStorage.setItem(AUTH_TOKEN_KEY, newToken)
      } else {
        localStorage.removeItem(AUTH_TOKEN_KEY)
      }
    }

    async function login(data: LoginRequest) {
      loading.value = true
      try {
        const response = await authApi.login(data)
        setToken(response.token)
        user.value = response.user
        return response
      } finally {
        loading.value = false
      }
    }

    async function register(data: RegisterRequest) {
      loading.value = true
      try {
        const response = await authApi.register(data)
        setToken(response.token)
        user.value = response.user
        return response
      } finally {
        loading.value = false
      }
    }

    async function logout() {
      try {
        await authApi.logout()
      } finally {
        user.value = null
        setToken(null)
      }
    }

    async function fetchUser() {
      if (!token.value) return
      loading.value = true
      try {
        user.value = await userApi.getProfile()
      } catch {
        user.value = null
        setToken(null)
      } finally {
        loading.value = false
      }
    }

    async function refreshToken() {
      try {
        const response = await authApi.refresh()
        setToken(response.token)
      } catch {
        user.value = null
        setToken(null)
      }
    }

    /** Start impersonating a tenant (admin only) */
    function startImpersonation(impersonationToken: string, tenantName: string) {
      originalToken.value = token.value
      impersonatedTenantName.value = tenantName
      setToken(impersonationToken)
      // Re-fetch user to get impersonated user data
      fetchUser()
    }

    /** Exit impersonation and restore admin session */
    function exitImpersonation() {
      if (originalToken.value) {
        setToken(originalToken.value)
        originalToken.value = null
        impersonatedTenantName.value = null
        fetchUser()
      }
    }

    return {
      user,
      token,
      loading,
      isAuthenticated,
      isEmailVerified,
      isImpersonating,
      impersonatedTenantName,
      login,
      register,
      logout,
      fetchUser,
      refreshToken,
      startImpersonation,
      exitImpersonation,
    }
  },
  {
    persist: {
      paths: ['token'],
    },
  },
)
