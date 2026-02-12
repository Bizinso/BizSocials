import type { RouteRecordRaw } from 'vue-router'

const adminRoutes: RouteRecordRaw[] = [
  {
    path: '/admin/login',
    name: 'admin-login',
    component: () => import('@/views/admin/AdminLoginView.vue'),
    meta: { layout: 'auth', guest: true },
  },
  {
    path: '/admin',
    meta: { requiresAuth: true, requiresSuperAdmin: true, layout: 'admin' },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/AdminDashboardView.vue'),
        meta: { title: 'Admin Dashboard' },
      },
      // ─── Tenants ────────────────────────────────────
      {
        path: 'tenants',
        name: 'admin-tenants',
        component: () => import('@/views/admin/AdminTenantsView.vue'),
        meta: { title: 'Manage Tenants' },
      },
      {
        path: 'tenants/:tenantId',
        name: 'admin-tenant-detail',
        component: () => import('@/views/admin/AdminTenantDetailView.vue'),
        meta: { title: 'Tenant Detail' },
      },
      // ─── Users ──────────────────────────────────────
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('@/views/admin/AdminUsersView.vue'),
        meta: { title: 'Manage Users' },
      },
      {
        path: 'users/:userId',
        name: 'admin-user-detail',
        component: () => import('@/views/admin/AdminUserDetailView.vue'),
        meta: { title: 'User Detail' },
      },
      // ─── Plans ──────────────────────────────────────
      {
        path: 'plans',
        name: 'admin-plans',
        component: () => import('@/views/admin/AdminPlansView.vue'),
        meta: { title: 'Manage Plans' },
      },
      // ─── Feature Flags ──────────────────────────────
      {
        path: 'feature-flags',
        name: 'admin-feature-flags',
        component: () => import('@/views/admin/AdminFeatureFlagsView.vue'),
        meta: { title: 'Feature Flags' },
      },
      // ─── Integrations ───────────────────────────────
      {
        path: 'integrations',
        name: 'admin-integrations',
        component: () => import('@/views/admin/AdminIntegrationsView.vue'),
        meta: { title: 'Integrations' },
      },
      {
        path: 'integrations/:provider',
        name: 'admin-integration-detail',
        component: () => import('@/views/admin/AdminIntegrationDetailView.vue'),
        meta: { title: 'Integration Detail' },
      },
      // ─── Config ─────────────────────────────────────
      {
        path: 'config',
        name: 'admin-config',
        component: () => import('@/views/admin/AdminConfigView.vue'),
        meta: { title: 'Platform Config' },
      },
      // ─── KB Admin ───────────────────────────────────
      {
        path: 'kb',
        name: 'admin-kb',
        component: () => import('@/views/admin/AdminKBView.vue'),
        meta: { title: 'KB Management' },
      },
      // ─── Feedback Admin ─────────────────────────────
      {
        path: 'feedback',
        name: 'admin-feedback',
        component: () => import('@/views/admin/AdminFeedbackView.vue'),
        meta: { title: 'Feedback Management' },
      },
      {
        path: 'roadmap',
        name: 'admin-roadmap',
        component: () => import('@/views/admin/AdminRoadmapView.vue'),
        meta: { title: 'Roadmap Management' },
      },
      {
        path: 'release-notes',
        name: 'admin-release-notes',
        component: () => import('@/views/admin/AdminReleaseNotesView.vue'),
        meta: { title: 'Release Notes' },
      },
      // ─── Support Admin ──────────────────────────────
      {
        path: 'support',
        name: 'admin-support',
        component: () => import('@/views/admin/AdminSupportView.vue'),
        meta: { title: 'Support Management' },
      },
      // ─── Privacy Admin ──────────────────────────────
      {
        path: 'privacy',
        name: 'admin-privacy',
        component: () => import('@/views/admin/AdminPrivacyView.vue'),
        meta: { title: 'Data Privacy' },
      },
      // ─── WhatsApp Admin ───────────────────────────────
      {
        path: 'whatsapp',
        name: 'admin-whatsapp',
        component: () => import('@/views/admin/AdminWhatsAppView.vue'),
        meta: { title: 'WhatsApp Administration' },
      },
    ],
  },
]

export default adminRoutes
