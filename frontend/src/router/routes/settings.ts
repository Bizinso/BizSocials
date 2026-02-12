import type { RouteRecordRaw } from 'vue-router'

const settingsRoutes: RouteRecordRaw[] = [
  {
    path: '/app/settings',
    meta: { requiresAuth: true, layout: 'app' },
    children: [
      {
        path: '',
        redirect: '/app/settings/profile',
      },
      {
        path: 'profile',
        name: 'settings-profile',
        component: () => import('@/views/settings/ProfileSettingsView.vue'),
        meta: { title: 'Profile Settings' },
      },
      {
        path: 'tenant',
        name: 'settings-tenant',
        component: () => import('@/views/settings/TenantSettingsView.vue'),
        meta: { title: 'Organization Settings' },
      },
      {
        path: 'team',
        name: 'settings-team',
        component: () => import('@/views/settings/TenantMembersView.vue'),
        meta: { title: 'Team Members' },
      },
      {
        path: 'notifications',
        name: 'settings-notifications',
        component: () => import('@/views/settings/NotificationSettingsView.vue'),
        meta: { title: 'Notification Settings' },
      },
      {
        path: 'security',
        name: 'settings-security',
        component: () => import('@/views/settings/SecuritySettingsView.vue'),
        meta: { title: 'Security' },
      },
      {
        path: 'privacy',
        name: 'settings-privacy',
        component: () => import('@/views/settings/PrivacySettingsView.vue'),
        meta: { title: 'Data Privacy' },
      },
      {
        path: 'audit',
        name: 'settings-audit',
        component: () => import('@/views/settings/AuditLogView.vue'),
        meta: { title: 'Audit Log' },
      },
    ],
  },
]

export default settingsRoutes
