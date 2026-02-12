import type { Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminAuthStore } from '@/stores/admin-auth'
import { useWorkspaceStore } from '@/stores/workspace'
import { useTenantStore } from '@/stores/tenant'

export function setupGuards(router: Router) {
  router.beforeEach(async (to, _from, next) => {
    // Super admin routes use a separate auth flow
    if (to.meta.requiresSuperAdmin) {
      const adminAuthStore = useAdminAuthStore()

      // Fetch admin profile if we have a token but no admin data yet
      if (adminAuthStore.token && !adminAuthStore.admin) {
        await adminAuthStore.fetchAdmin()
      }

      if (!adminAuthStore.isAuthenticated) {
        return next({ name: 'admin-login', query: { redirect: to.fullPath } })
      }

      return next()
    }

    const authStore = useAuthStore()

    // Fetch user if we have a token but no user data yet
    if (authStore.token && !authStore.user) {
      await authStore.fetchUser()
    }

    // Route requires authentication
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return next({ name: 'login', query: { redirect: to.fullPath } })
    }

    // Route is guest-only (login, register) — redirect if already logged in
    if (to.meta.guest && authStore.isAuthenticated) {
      return next({ name: 'dashboard' })
    }

    // Ensure workspace and tenant data are loaded for authenticated users
    if (authStore.user) {
      const workspaceStore = useWorkspaceStore()
      const tenantStore = useTenantStore()
      await Promise.all([
        workspaceStore.workspaces.length === 0 ? workspaceStore.fetchWorkspaces() : Promise.resolve(),
        !tenantStore.tenant ? tenantStore.fetchTenant() : Promise.resolve(),
      ])
    }

    // Onboarding guard: if route requires onboarding, ensure user is verified
    if (to.meta.requiresOnboarding) {
      if (!authStore.isEmailVerified) {
        return next({ name: 'login' })
      }
    }

    next()
  })

  // Set page title
  router.afterEach((to) => {
    const title = to.meta.title as string | undefined
    document.title = title ? `${title} — BizSocials` : 'BizSocials'
  })
}
