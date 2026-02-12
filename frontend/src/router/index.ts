import { createRouter, createWebHistory } from 'vue-router'
import authRoutes from './routes/auth'
import onboardingRoutes from './routes/onboarding'
import appRoutes from './routes/app'
import workspaceRoutes from './routes/workspace'
import settingsRoutes from './routes/settings'
import billingRoutes from './routes/billing'
import supportRoutes from './routes/support'
import publicRoutes from './routes/public'
import adminRoutes from './routes/admin'
import { setupGuards } from './guards'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: () => {
        const token = localStorage.getItem('auth_token')
        return token ? '/app/dashboard' : '/login'
      },
    },
    ...authRoutes,
    ...onboardingRoutes,
    ...appRoutes,
    ...workspaceRoutes,
    ...settingsRoutes,
    ...billingRoutes,
    ...supportRoutes,
    ...publicRoutes,
    ...adminRoutes,
    {
      path: '/forbidden',
      name: 'forbidden',
      component: () => import('@/views/errors/ForbiddenView.vue'),
      meta: { layout: 'blank', title: 'Forbidden' },
    },
    {
      path: '/error',
      name: 'server-error',
      component: () => import('@/views/errors/ServerErrorView.vue'),
      meta: { layout: 'blank', title: 'Server Error' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/errors/NotFoundView.vue'),
      meta: { layout: 'blank', title: 'Not Found' },
    },
  ],
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition || { top: 0 }
  },
})

setupGuards(router)

export default router
