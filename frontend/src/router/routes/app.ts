import type { RouteRecordRaw } from 'vue-router'

const appRoutes: RouteRecordRaw[] = [
  {
    path: '/app',
    meta: { requiresAuth: true, layout: 'app' },
    children: [
      {
        path: '',
        redirect: '/app/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { title: 'Dashboard' },
      },
      {
        path: 'oauth/callback',
        name: 'oauth-callback',
        component: () => import('@/views/social/OAuthCallbackView.vue'),
        meta: { title: 'Connecting Account', layout: 'blank' },
      },
    ],
  },
]

export default appRoutes
