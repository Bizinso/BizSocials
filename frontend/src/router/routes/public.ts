import type { RouteRecordRaw } from 'vue-router'

const publicRoutes: RouteRecordRaw[] = [
  // ─── Knowledge Base ─────────────────────────────────
  {
    path: '/kb',
    meta: { layout: 'public' },
    children: [
      {
        path: '',
        name: 'kb-home',
        component: () => import('@/views/kb/KBHomeView.vue'),
        meta: { title: 'Knowledge Base' },
      },
      {
        path: 'search',
        name: 'kb-search',
        component: () => import('@/views/kb/KBSearchView.vue'),
        meta: { title: 'Search Knowledge Base' },
      },
      {
        path: 'category/:slug',
        name: 'kb-category',
        component: () => import('@/views/kb/KBCategoryView.vue'),
        meta: { title: 'Category' },
      },
      {
        path: ':slug',
        name: 'kb-article',
        component: () => import('@/views/kb/KBArticleView.vue'),
        meta: { title: 'Article' },
      },
    ],
  },

  // ─── Feedback ───────────────────────────────────────
  {
    path: '/feedback',
    meta: { layout: 'public' },
    children: [
      {
        path: '',
        name: 'feedback-list',
        component: () => import('@/views/feedback/FeedbackListView.vue'),
        meta: { title: 'Feedback' },
      },
      {
        path: 'submit',
        name: 'feedback-submit',
        component: () => import('@/views/feedback/FeedbackSubmitView.vue'),
        meta: { title: 'Submit Feedback' },
      },
      {
        path: ':feedbackId',
        name: 'feedback-detail',
        component: () => import('@/views/feedback/FeedbackDetailView.vue'),
        meta: { title: 'Feedback Detail' },
      },
    ],
  },

  // ─── Roadmap ────────────────────────────────────────
  {
    path: '/roadmap',
    name: 'roadmap',
    component: () => import('@/views/feedback/RoadmapView.vue'),
    meta: { layout: 'public', title: 'Roadmap' },
  },

  // ─── Changelog ──────────────────────────────────────
  {
    path: '/changelog',
    meta: { layout: 'public' },
    children: [
      {
        path: '',
        name: 'changelog-list',
        component: () => import('@/views/feedback/ChangelogListView.vue'),
        meta: { title: 'Changelog' },
      },
      {
        path: ':slug',
        name: 'changelog-detail',
        component: () => import('@/views/feedback/ChangelogDetailView.vue'),
        meta: { title: 'Release' },
      },
    ],
  },
]

export default publicRoutes
