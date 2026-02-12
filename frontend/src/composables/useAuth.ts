import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useAuth() {
  const authStore = useAuthStore()

  const user = computed(() => authStore.user)
  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const isEmailVerified = computed(() => authStore.isEmailVerified)
  const loading = computed(() => authStore.loading)

  return {
    user,
    isAuthenticated,
    isEmailVerified,
    loading,
    login: authStore.login,
    register: authStore.register,
    logout: authStore.logout,
    fetchUser: authStore.fetchUser,
  }
}
