import type { RouteRecordRaw } from 'vue-router'

const onboardingRoutes: RouteRecordRaw[] = [
  {
    path: '/onboarding/org',
    name: 'onboarding-org',
    component: () => import('@/views/onboarding/OrganizationSetupView.vue'),
    meta: { layout: 'auth', requiresAuth: true, requiresOnboarding: true, title: 'Organization Setup' },
  },
  {
    path: '/onboarding/workspace',
    name: 'onboarding-workspace',
    component: () => import('@/views/onboarding/WorkspaceSetupView.vue'),
    meta: { layout: 'auth', requiresAuth: true, requiresOnboarding: true, title: 'Create Workspace' },
  },
]

export default onboardingRoutes
