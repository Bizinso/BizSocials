import type { RouteRecordRaw } from 'vue-router'

const supportRoutes: RouteRecordRaw[] = [
  {
    path: '/app/support',
    meta: { requiresAuth: true, layout: 'app' },
    children: [
      {
        path: '',
        name: 'support-tickets',
        component: () => import('@/views/support/TicketListView.vue'),
        meta: { title: 'Support Tickets' },
      },
      {
        path: 'new',
        name: 'support-new-ticket',
        component: () => import('@/views/support/TicketCreateView.vue'),
        meta: { title: 'New Support Ticket' },
      },
      {
        path: ':ticketId',
        name: 'support-ticket-detail',
        component: () => import('@/views/support/TicketDetailView.vue'),
        meta: { title: 'Ticket Detail' },
      },
    ],
  },
]

export default supportRoutes
