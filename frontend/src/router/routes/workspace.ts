import type { RouteRecordRaw } from 'vue-router'

const workspaceRoutes: RouteRecordRaw[] = [
  {
    path: '/app/workspaces',
    name: 'workspaces',
    component: () => import('@/views/workspace/WorkspaceListView.vue'),
    meta: { requiresAuth: true, layout: 'app', title: 'Workspaces' },
  },
  {
    path: '/app/workspaces/:workspaceId',
    name: 'workspace-detail',
    component: () => import('@/views/workspace/WorkspaceDetailView.vue'),
    meta: { requiresAuth: true, layout: 'app', title: 'Workspace Settings' },
  },
  {
    path: '/app/w/:workspaceId',
    meta: { requiresAuth: true, layout: 'app' },
    children: [
      {
        path: '',
        name: 'workspace-dashboard',
        component: () => import('@/views/workspace/WorkspaceDashboardView.vue'),
        meta: { title: 'Workspace' },
      },
      // Social Accounts
      {
        path: 'social-accounts',
        name: 'social-accounts',
        component: () => import('@/views/social/SocialAccountsView.vue'),
        meta: { title: 'Social Accounts' },
      },

      // Posts
      {
        path: 'posts',
        name: 'posts',
        component: () => import('@/views/content/PostListView.vue'),
        meta: { title: 'Posts' },
      },
      {
        path: 'posts/create',
        name: 'post-create',
        component: () => import('@/views/content/PostCreateView.vue'),
        meta: { title: 'Create Post' },
      },
      {
        path: 'posts/:postId/edit',
        name: 'post-edit',
        component: () => import('@/views/content/PostEditView.vue'),
        meta: { title: 'Edit Post' },
      },

      // Calendar
      {
        path: 'calendar',
        name: 'calendar',
        component: () => import('@/views/content/PostCalendarView.vue'),
        meta: { title: 'Content Calendar' },
      },

      // Approvals
      {
        path: 'approvals',
        name: 'approvals',
        component: () => import('@/views/content/ApprovalQueueView.vue'),
        meta: { title: 'Approval Queue' },
      },

      // Inbox
      {
        path: 'inbox',
        name: 'inbox',
        component: () => import('@/views/inbox/InboxView.vue'),
        meta: { title: 'Inbox' },
      },
      {
        path: 'inbox/:itemId',
        name: 'inbox-detail',
        component: () => import('@/views/inbox/InboxDetailView.vue'),
        meta: { title: 'Inbox Item' },
      },

      // Analytics
      {
        path: 'analytics',
        name: 'analytics',
        component: () => import('@/views/analytics/AnalyticsDashboardView.vue'),
        meta: { title: 'Analytics' },
      },
      {
        path: 'analytics/content',
        name: 'analytics-content',
        component: () => import('@/views/analytics/AnalyticsContentView.vue'),
        meta: { title: 'Content Analytics' },
      },

      // Reports
      {
        path: 'reports',
        name: 'reports',
        component: () => import('@/views/analytics/ReportListView.vue'),
        meta: { title: 'Reports' },
      },

      // Media Library
      {
        path: 'media-library',
        name: 'media-library',
        component: () => import('@/views/content/MediaLibraryView.vue'),
        meta: { title: 'Media Library' },
      },

      // Content Organization
      {
        path: 'categories',
        name: 'content-categories',
        component: () => import('@/views/content/ContentCategoriesView.vue'),
        meta: { title: 'Categories & Hashtags' },
      },

      // Short Links
      {
        path: 'short-links',
        name: 'short-links',
        component: () => import('@/views/content/ShortLinksView.vue'),
        meta: { title: 'Short Links' },
      },

      // RSS Feeds
      {
        path: 'rss-feeds',
        name: 'rss-feeds',
        component: () => import('@/views/content/RssFeedsView.vue'),
        meta: { title: 'RSS Feeds' },
      },

      // Evergreen
      {
        path: 'evergreen',
        name: 'evergreen',
        component: () => import('@/views/content/EvergreenView.vue'),
        meta: { title: 'Evergreen Content' },
      },

      // WhatsApp
      {
        path: 'whatsapp/setup',
        name: 'whatsapp-setup',
        component: () => import('@/views/whatsapp/WhatsAppOnboardingView.vue'),
        meta: { title: 'WhatsApp Setup' },
      },
      {
        path: 'whatsapp/inbox',
        name: 'whatsapp-inbox',
        component: () => import('@/views/whatsapp/WhatsAppInboxView.vue'),
        meta: { title: 'WhatsApp Inbox' },
      },
      {
        path: 'whatsapp/contacts',
        name: 'whatsapp-contacts',
        component: () => import('@/views/whatsapp/WhatsAppContactsView.vue'),
        meta: { title: 'WhatsApp Contacts' },
      },
      {
        path: 'whatsapp/templates',
        name: 'whatsapp-templates',
        component: () => import('@/views/whatsapp/WhatsAppTemplatesView.vue'),
        meta: { title: 'WhatsApp Templates' },
      },
      {
        path: 'whatsapp/campaigns',
        name: 'whatsapp-campaigns',
        component: () => import('@/views/whatsapp/WhatsAppCampaignsView.vue'),
        meta: { title: 'WhatsApp Campaigns' },
      },
      {
        path: 'whatsapp/automation',
        name: 'whatsapp-automation',
        component: () => import('@/views/whatsapp/WhatsAppAutomationView.vue'),
        meta: { title: 'WhatsApp Automation' },
      },
      {
        path: 'whatsapp/quick-replies',
        name: 'whatsapp-quick-replies',
        component: () => import('@/views/whatsapp/WhatsAppQuickRepliesView.vue'),
        meta: { title: 'WhatsApp Quick Replies' },
      },
      {
        path: 'whatsapp/analytics',
        name: 'whatsapp-analytics',
        component: () => import('@/views/whatsapp/WhatsAppAnalyticsDashboardView.vue'),
        meta: { title: 'WhatsApp Analytics' },
      },

      // Saved Replies
      {
        path: 'saved-replies',
        name: 'saved-replies',
        component: () => import('@/views/inbox/SavedRepliesView.vue'),
        meta: { title: 'Saved Replies' },
      },
      // Inbox Contacts (Social CRM)
      {
        path: 'inbox-contacts',
        name: 'inbox-contacts',
        component: () => import('@/views/inbox/InboxContactsView.vue'),
        meta: { title: 'Inbox Contacts' },
      },
      // Inbox Automation
      {
        path: 'inbox-automation',
        name: 'inbox-automation',
        component: () => import('@/views/inbox/InboxAutomationView.vue'),
        meta: { title: 'Inbox Automation' },
      },
      // Tasks
      {
        path: 'tasks',
        name: 'tasks',
        component: () => import('@/views/collaboration/TaskBoardView.vue'),
        meta: { title: 'Tasks' },
      },
      // Approval Workflows
      {
        path: 'approval-workflows',
        name: 'approval-workflows',
        component: () => import('@/views/collaboration/ApprovalWorkflowsView.vue'),
        meta: { title: 'Approval Workflows' },
      },
      // Audience Demographics
      {
        path: 'analytics/demographics',
        name: 'analytics-demographics',
        component: () => import('@/views/analytics/AudienceDemographicsView.vue'),
        meta: { title: 'Audience Demographics' },
      },
      // Hashtag Tracking
      {
        path: 'analytics/hashtag-tracking',
        name: 'analytics-hashtag-tracking',
        component: () => import('@/views/analytics/HashtagTrackingView.vue'),
        meta: { title: 'Hashtag Tracking' },
      },
      // Scheduled Reports
      {
        path: 'scheduled-reports',
        name: 'scheduled-reports',
        component: () => import('@/views/analytics/ScheduledReportsView.vue'),
        meta: { title: 'Scheduled Reports' },
      },
      // Social Listening
      {
        path: 'listening',
        name: 'keyword-monitoring',
        component: () => import('@/views/listening/KeywordMonitoringView.vue'),
        meta: { title: 'Social Listening' },
      },
      // Webhooks
      {
        path: 'webhooks',
        name: 'webhooks',
        component: () => import('@/views/integration/WebhooksView.vue'),
        meta: { title: 'Webhooks' },
      },
      // Image Editor
      {
        path: 'image-editor',
        name: 'image-editor',
        component: () => import('@/views/content/ImageEditorView.vue'),
        meta: { title: 'Image Editor' },
      },

      // Teams
      {
        path: 'teams',
        name: 'teams-management',
        component: () => import('@/views/workspace/TeamsView.vue'),
        meta: { title: 'Teams' },
      },

      // Members
      {
        path: 'members',
        name: 'workspace-members',
        component: () => import('@/views/workspace/WorkspaceMembersView.vue'),
        meta: { title: 'Members' },
      },
    ],
  },
]

export default workspaceRoutes
