import type { RouteRecordRaw } from 'vue-router'

const billingRoutes: RouteRecordRaw[] = [
  {
    path: '/app/billing',
    meta: { requiresAuth: true, layout: 'app' },
    children: [
      {
        path: '',
        redirect: '/app/billing/overview',
      },
      {
        path: 'overview',
        name: 'billing-overview',
        component: () => import('@/views/billing/BillingOverviewView.vue'),
        meta: { title: 'Billing Overview' },
      },
      {
        path: 'plans',
        name: 'billing-plans',
        component: () => import('@/views/billing/BillingPlansView.vue'),
        meta: { title: 'Plans' },
      },
      {
        path: 'invoices',
        name: 'billing-invoices',
        component: () => import('@/views/billing/BillingInvoicesView.vue'),
        meta: { title: 'Invoices' },
      },
      {
        path: 'payment-methods',
        name: 'billing-payment-methods',
        component: () => import('@/views/billing/BillingPaymentMethodsView.vue'),
        meta: { title: 'Payment Methods' },
      },
    ],
  },
]

export default billingRoutes
