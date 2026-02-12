// ─── User ───────────────────────────────────────────
export enum UserStatus {
  Pending = 'pending',
  Active = 'active',
  Suspended = 'suspended',
  Deactivated = 'deactivated',
}

export enum TenantRole {
  Owner = 'owner',
  Admin = 'admin',
  Member = 'member',
}

export enum InvitationStatus {
  Pending = 'pending',
  Accepted = 'accepted',
  Expired = 'expired',
  Revoked = 'revoked',
}

export enum DeviceType {
  Desktop = 'desktop',
  Mobile = 'mobile',
  Tablet = 'tablet',
  Api = 'api',
}

// ─── Tenant ─────────────────────────────────────────
export enum TenantStatus {
  Pending = 'pending',
  Active = 'active',
  Suspended = 'suspended',
  Terminated = 'terminated',
}

export enum TenantType {
  B2bEnterprise = 'b2b_enterprise',
  B2bSmb = 'b2b_smb',
  B2cBrand = 'b2c_brand',
  Individual = 'individual',
  Influencer = 'influencer',
  NonProfit = 'non_profit',
}

export enum CompanySize {
  Solo = 'solo',
  Small = 'small',
  Medium = 'medium',
  Large = 'large',
  Enterprise = 'enterprise',
}

export enum VerificationStatus {
  Pending = 'pending',
  Verified = 'verified',
  Failed = 'failed',
}

// ─── Workspace ──────────────────────────────────────
export enum WorkspaceRole {
  Owner = 'owner',
  Admin = 'admin',
  Editor = 'editor',
  Viewer = 'viewer',
}

export enum WorkspaceStatus {
  Active = 'active',
  Suspended = 'suspended',
  Deleted = 'deleted',
}

// ─── Social ─────────────────────────────────────────
export enum SocialPlatform {
  Linkedin = 'linkedin',
  Facebook = 'facebook',
  Instagram = 'instagram',
  Twitter = 'twitter',
  Whatsapp = 'whatsapp',
}

export enum SocialAccountStatus {
  Connected = 'connected',
  TokenExpired = 'token_expired',
  Revoked = 'revoked',
  Disconnected = 'disconnected',
}

// ─── Content ────────────────────────────────────────
export enum PostStatus {
  Draft = 'draft',
  Submitted = 'submitted',
  Approved = 'approved',
  Rejected = 'rejected',
  Scheduled = 'scheduled',
  Publishing = 'publishing',
  Published = 'published',
  Failed = 'failed',
  Cancelled = 'cancelled',
}

export enum PostType {
  Standard = 'standard',
  Reel = 'reel',
  Story = 'story',
  Thread = 'thread',
  Article = 'article',
}

export enum PostTargetStatus {
  Pending = 'pending',
  Publishing = 'publishing',
  Published = 'published',
  Failed = 'failed',
}

export enum MediaType {
  Image = 'image',
  Video = 'video',
  Gif = 'gif',
  Document = 'document',
}

export enum MediaProcessingStatus {
  Pending = 'pending',
  Processing = 'processing',
  Completed = 'completed',
  Failed = 'failed',
}

export enum ApprovalDecisionType {
  Approved = 'approved',
  Rejected = 'rejected',
}

// ─── Inbox ──────────────────────────────────────────
export enum InboxItemStatus {
  Unread = 'unread',
  Read = 'read',
  Resolved = 'resolved',
  Archived = 'archived',
}

export enum InboxItemType {
  Comment = 'comment',
  Mention = 'mention',
  Dm = 'dm',
  WhatsappMessage = 'whatsapp_message',
  Review = 'review',
}

// ─── WhatsApp ────────────────────────────────────────
export enum WhatsAppAccountStatus {
  PendingVerification = 'pending_verification',
  Verified = 'verified',
  Suspended = 'suspended',
  Banned = 'banned',
}

export enum WhatsAppQualityRating {
  Green = 'green',
  Yellow = 'yellow',
  Red = 'red',
  Unknown = 'unknown',
}

export enum WhatsAppMessagingTier {
  Tier1k = 'tier_1k',
  Tier10k = 'tier_10k',
  Tier100k = 'tier_100k',
  Unlimited = 'unlimited',
}

export enum WhatsAppMessageType {
  Text = 'text',
  Image = 'image',
  Video = 'video',
  Document = 'document',
  Audio = 'audio',
  Location = 'location',
  Contact = 'contact',
  InteractiveButtons = 'interactive_buttons',
  InteractiveList = 'interactive_list',
  Template = 'template',
  Sticker = 'sticker',
  Reaction = 'reaction',
  Unknown = 'unknown',
}

export enum WhatsAppMessageDirection {
  Inbound = 'inbound',
  Outbound = 'outbound',
}

export enum WhatsAppMessageStatus {
  Pending = 'pending',
  Sent = 'sent',
  Delivered = 'delivered',
  Read = 'read',
  Failed = 'failed',
}

export enum WhatsAppConversationStatus {
  Active = 'active',
  Pending = 'pending',
  Resolved = 'resolved',
  Archived = 'archived',
}

export enum WhatsAppConversationPriority {
  Low = 'low',
  Normal = 'normal',
  High = 'high',
  Urgent = 'urgent',
}

export enum WhatsAppOptInSource {
  Manual = 'manual',
  Import = 'import',
  WebsiteForm = 'website_form',
  Api = 'api',
  Conversation = 'conversation',
}

// ─── Analytics ──────────────────────────────────────
export enum ReportStatus {
  Pending = 'pending',
  Processing = 'processing',
  Completed = 'completed',
  Failed = 'failed',
  Expired = 'expired',
}

export enum ReportType {
  Performance = 'performance',
  Engagement = 'engagement',
  Growth = 'growth',
  Content = 'content',
  Audience = 'audience',
  Custom = 'custom',
}

export enum PeriodType {
  Daily = 'daily',
  Weekly = 'weekly',
  Monthly = 'monthly',
}

// ─── Billing ────────────────────────────────────────
export enum BillingCycle {
  Monthly = 'monthly',
  Yearly = 'yearly',
}

export enum Currency {
  INR = 'INR',
  USD = 'USD',
}

export enum InvoiceStatus {
  Draft = 'draft',
  Issued = 'issued',
  Paid = 'paid',
  Cancelled = 'cancelled',
  Expired = 'expired',
}

export enum PaymentMethodType {
  Card = 'card',
  Upi = 'upi',
  Netbanking = 'netbanking',
  Wallet = 'wallet',
  Emandate = 'emandate',
}

export enum SubscriptionStatus {
  Created = 'created',
  Authenticated = 'authenticated',
  Active = 'active',
  Pending = 'pending',
  Halted = 'halted',
  Cancelled = 'cancelled',
  Completed = 'completed',
  Expired = 'expired',
}

export enum PlanCode {
  Free = 'FREE',
  Starter = 'STARTER',
  Professional = 'PROFESSIONAL',
  Business = 'BUSINESS',
  Enterprise = 'ENTERPRISE',
}

// ─── Notification ───────────────────────────────────
export enum NotificationChannel {
  InApp = 'in_app',
  Email = 'email',
  Push = 'push',
  Sms = 'sms',
}

export enum NotificationType {
  PostSubmitted = 'post_submitted',
  PostApproved = 'post_approved',
  PostRejected = 'post_rejected',
  PostPublished = 'post_published',
  PostFailed = 'post_failed',
  PostScheduled = 'post_scheduled',
  NewComment = 'new_comment',
  NewMention = 'new_mention',
  InboxAssigned = 'inbox_assigned',
  InvitationReceived = 'invitation_received',
  InvitationAccepted = 'invitation_accepted',
  MemberAdded = 'member_added',
  MemberRemoved = 'member_removed',
  RoleChanged = 'role_changed',
  SubscriptionCreated = 'subscription_created',
  SubscriptionRenewed = 'subscription_renewed',
  SubscriptionCancelled = 'subscription_cancelled',
  PaymentFailed = 'payment_failed',
  TrialEnding = 'trial_ending',
  TrialEnded = 'trial_ended',
  AccountConnected = 'account_connected',
  AccountDisconnected = 'account_disconnected',
  AccountTokenExpiring = 'account_token_expiring',
  AccountTokenExpired = 'account_token_expired',
  TicketCreated = 'ticket_created',
  TicketReplied = 'ticket_replied',
  TicketResolved = 'ticket_resolved',
  DataExportReady = 'data_export_ready',
  DataDeletionScheduled = 'data_deletion_scheduled',
  DataDeletionCompleted = 'data_deletion_completed',
  ReportReady = 'report_ready',
  ReportFailed = 'report_failed',
  SystemAnnouncement = 'system_announcement',
  MaintenanceScheduled = 'maintenance_scheduled',
}

// ─── Audit ──────────────────────────────────────────
export enum AuditAction {
  Create = 'create',
  Update = 'update',
  Delete = 'delete',
  Restore = 'restore',
  View = 'view',
  Export = 'export',
  Import = 'import',
  Login = 'login',
  Logout = 'logout',
  PermissionChange = 'permission_change',
  SettingsChange = 'settings_change',
  SubscriptionChange = 'subscription_change',
}

export enum SecuritySeverity {
  Info = 'info',
  Low = 'low',
  Medium = 'medium',
  High = 'high',
  Critical = 'critical',
}

export enum SecurityEventType {
  LoginSuccess = 'login_success',
  LoginFailure = 'login_failure',
  Logout = 'logout',
  PasswordChange = 'password_change',
  PasswordResetRequest = 'password_reset_request',
  PasswordResetComplete = 'password_reset_complete',
  MfaEnabled = 'mfa_enabled',
  MfaDisabled = 'mfa_disabled',
  MfaChallengeSuccess = 'mfa_challenge_success',
  MfaChallengeFailure = 'mfa_challenge_failure',
  SuspiciousActivity = 'suspicious_activity',
  AccountLocked = 'account_locked',
  AccountUnlocked = 'account_unlocked',
  SessionInvalidated = 'session_invalidated',
  ApiKeyCreated = 'api_key_created',
  ApiKeyRevoked = 'api_key_revoked',
  IpBlocked = 'ip_blocked',
  IpWhitelisted = 'ip_whitelisted',
}

export enum SessionStatus {
  Active = 'active',
  Expired = 'expired',
  Revoked = 'revoked',
  LoggedOut = 'logged_out',
}

export enum DataRequestStatus {
  Pending = 'pending',
  Processing = 'processing',
  Completed = 'completed',
  Failed = 'failed',
  Cancelled = 'cancelled',
}

export enum DataRequestType {
  Export = 'export',
  Deletion = 'deletion',
  Rectification = 'rectification',
  Access = 'access',
}

// ─── Support ────────────────────────────────────────
export enum SupportTicketStatus {
  New = 'new',
  Open = 'open',
  InProgress = 'in_progress',
  WaitingCustomer = 'waiting_customer',
  WaitingInternal = 'waiting_internal',
  Resolved = 'resolved',
  Closed = 'closed',
  Reopened = 'reopened',
}

export enum SupportTicketPriority {
  Low = 'low',
  Medium = 'medium',
  High = 'high',
  Urgent = 'urgent',
}

export enum SupportTicketType {
  Question = 'question',
  Problem = 'problem',
  FeatureRequest = 'feature_request',
  BugReport = 'bug_report',
  Billing = 'billing',
  Account = 'account',
  Other = 'other',
}

export enum SupportCommentType {
  Reply = 'reply',
  Note = 'note',
  StatusChange = 'status_change',
  Assignment = 'assignment',
  System = 'system',
}

// ─── Feedback ───────────────────────────────────────
export enum FeedbackStatus {
  New = 'new',
  UnderReview = 'under_review',
  Planned = 'planned',
  InProgress = 'in_progress',
  Shipped = 'shipped',
  Declined = 'declined',
  Duplicate = 'duplicate',
  Archived = 'archived',
}

export enum FeedbackType {
  FeatureRequest = 'feature_request',
  Improvement = 'improvement',
  BugReport = 'bug_report',
  IntegrationRequest = 'integration_request',
  UxFeedback = 'ux_feedback',
  Documentation = 'documentation',
  PricingFeedback = 'pricing_feedback',
  Other = 'other',
}

export enum FeedbackCategory {
  Publishing = 'publishing',
  Scheduling = 'scheduling',
  Analytics = 'analytics',
  Inbox = 'inbox',
  TeamCollaboration = 'team_collaboration',
  Integrations = 'integrations',
  MobileApp = 'mobile_app',
  Api = 'api',
  Billing = 'billing',
  Onboarding = 'onboarding',
  General = 'general',
}

export enum RoadmapStatus {
  Considering = 'considering',
  Planned = 'planned',
  InProgress = 'in_progress',
  Beta = 'beta',
  Shipped = 'shipped',
  Cancelled = 'cancelled',
}

export enum ReleaseNoteStatus {
  Draft = 'draft',
  Scheduled = 'scheduled',
  Published = 'published',
}

export enum ReleaseType {
  Major = 'major',
  Minor = 'minor',
  Patch = 'patch',
  Hotfix = 'hotfix',
  Beta = 'beta',
  Alpha = 'alpha',
}

export enum SupportChannel {
  Web = 'web',
  Email = 'email',
  Chat = 'chat',
  Phone = 'phone',
  Api = 'api',
}

// ─── Feedback ─────────────────────────────────────── (extra enums)
export enum VoteType {
  Upvote = 'upvote',
  Downvote = 'downvote',
}

export enum RoadmapCategory {
  Platform = 'platform',
  Features = 'features',
  Performance = 'performance',
  Security = 'security',
  Integrations = 'integrations',
}

export enum ChangeType {
  Feature = 'feature',
  Improvement = 'improvement',
  BugFix = 'bug_fix',
  BreakingChange = 'breaking_change',
  Deprecation = 'deprecation',
}

// ─── Knowledge Base ─────────────────────────────────
export enum KBArticleStatus {
  Draft = 'draft',
  Published = 'published',
  Archived = 'archived',
}

export enum KBArticleType {
  GettingStarted = 'getting_started',
  HowTo = 'how_to',
  Tutorial = 'tutorial',
  Reference = 'reference',
  Troubleshooting = 'troubleshooting',
  Faq = 'faq',
  BestPractice = 'best_practice',
  ReleaseNote = 'release_note',
  ApiDocumentation = 'api_documentation',
}

export enum KBDifficultyLevel {
  Beginner = 'beginner',
  Intermediate = 'intermediate',
  Advanced = 'advanced',
  Expert = 'expert',
}

export enum KBFeedbackCategory {
  Unclear = 'unclear',
  Outdated = 'outdated',
  Incomplete = 'incomplete',
  Incorrect = 'incorrect',
  Other = 'other',
}

// ─── Platform Admin ─────────────────────────────────
export enum ConfigCategory {
  General = 'general',
  Features = 'features',
  Integrations = 'integrations',
  Limits = 'limits',
  Notifications = 'notifications',
  Security = 'security',
}
