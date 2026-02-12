<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\Audit\AdminDataPrivacyController;
use App\Http\Controllers\Api\V1\Admin\Feedback\AdminFeedbackController;
use App\Http\Controllers\Api\V1\Admin\Feedback\AdminReleaseNoteController;
use App\Http\Controllers\Api\V1\Admin\Feedback\AdminRoadmapController;
use App\Http\Controllers\Api\V1\Admin\KB\AdminKBArticleController;
use App\Http\Controllers\Api\V1\Admin\KB\AdminKBCategoryController;
use App\Http\Controllers\Api\V1\Admin\KB\AdminKBFeedbackController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminConfigController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminIntegrationController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminFeatureFlagController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminPlanController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminTenantController;
use App\Http\Controllers\Api\V1\Admin\Platform\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\Support\AdminSupportCategoryController;
use App\Http\Controllers\Api\V1\Admin\WhatsApp\WhatsAppAdminController;
use App\Http\Controllers\Api\V1\Admin\Support\AdminSupportCommentController;
use App\Http\Controllers\Api\V1\Admin\Support\AdminSupportTicketController;
use App\Http\Controllers\Api\V1\Analytics\AnalyticsController;
use App\Http\Controllers\Api\V1\Analytics\AudienceDemographicsController;
use App\Http\Controllers\Api\V1\Analytics\ContentAnalyticsController;
use App\Http\Controllers\Api\V1\Analytics\HashtagTrackingController;
use App\Http\Controllers\Api\V1\Analytics\ReportController;
use App\Http\Controllers\Api\V1\Analytics\ScheduledReportController;
use App\Http\Controllers\Api\V1\Audit\AuditLogController;
use App\Http\Controllers\Api\V1\Audit\DataPrivacyController;
use App\Http\Controllers\Api\V1\Audit\SecurityController;
use App\Http\Controllers\Api\V1\Audit\SessionController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\SuperAdminAuthController;
use App\Http\Controllers\Api\V1\Billing\BillingController;
use App\Http\Controllers\Api\V1\Billing\CheckoutController;
use App\Http\Controllers\Api\V1\Billing\InvoiceController;
use App\Http\Controllers\Api\V1\Billing\PaymentMethodController;
use App\Http\Controllers\Api\V1\Billing\SubscriptionController;
use App\Http\Controllers\Api\V1\Content\ApprovalController;
use App\Http\Controllers\Api\V1\Content\ApprovalWorkflowController;
use App\Http\Controllers\Api\V1\Content\BulkPostController;
use App\Http\Controllers\Api\V1\Content\PostController;
use App\Http\Controllers\Api\V1\Content\PostMediaController;
use App\Http\Controllers\Api\V1\Content\PostNoteController;
use App\Http\Controllers\Api\V1\Content\PostRevisionController;
use App\Http\Controllers\Api\V1\Content\PostTargetController;
use App\Http\Controllers\Api\V1\Content\AIAssistController;
use App\Http\Controllers\Api\V1\Content\MediaLibraryController;
use App\Http\Controllers\Api\V1\Content\ContentCategoryController;
use App\Http\Controllers\Api\V1\Content\HashtagGroupController;
use App\Http\Controllers\Api\V1\Content\LinkShortenerController;
use App\Http\Controllers\Api\V1\Content\RssFeedController;
use App\Http\Controllers\Api\V1\Content\EvergreenController;
use App\Http\Controllers\Api\V1\Content\WorkspaceTaskController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\Api\V1\Feedback\FeedbackController;
use App\Http\Controllers\Api\V1\Feedback\ReleaseNoteController;
use App\Http\Controllers\Api\V1\Feedback\RoadmapController;
use App\Http\Controllers\Api\V1\Onboarding\OnboardingController;
use App\Http\Controllers\Api\V1\Inbox\InboxAutomationController;
use App\Http\Controllers\Api\V1\Inbox\InboxContactController;
use App\Http\Controllers\Api\V1\Inbox\InboxController;
use App\Http\Controllers\Api\V1\Inbox\InboxNoteController;
use App\Http\Controllers\Api\V1\Inbox\InboxReplyController;
use App\Http\Controllers\Api\V1\Inbox\InboxTagController;
use App\Http\Controllers\Api\V1\Inbox\SavedReplyController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\KB\KBArticleController;
use App\Http\Controllers\Api\V1\KB\KBCategoryController;
use App\Http\Controllers\Api\V1\KB\KBFeedbackController;
use App\Http\Controllers\Api\V1\KB\KBSearchController;
use App\Http\Controllers\Api\V1\Social\OAuthController;
use App\Http\Controllers\Api\V1\Social\SocialAccountController;
use App\Http\Controllers\Api\V1\Support\SupportCategoryController;
use App\Http\Controllers\Api\V1\Support\SupportCommentController;
use App\Http\Controllers\Api\V1\Support\SupportTicketController;
use App\Http\Controllers\Api\V1\Tenant\InvitationController;
use App\Http\Controllers\Api\V1\Tenant\TenantController;
use App\Http\Controllers\Api\V1\Tenant\TenantMemberController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\Workspace\TeamController;
use App\Http\Controllers\Api\V1\Workspace\WorkspaceController;
use App\Http\Controllers\Api\V1\Workspace\WorkspaceDashboardController;
use App\Http\Controllers\Api\V1\Workspace\WorkspaceMemberController;
use App\Http\Controllers\Api\V1\Listening\KeywordMonitoringController;
use App\Http\Controllers\Api\V1\Integration\WebhookEndpointController;
use App\Http\Controllers\Api\V1\Content\ImageEditorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for version 1 of the BizSocials API.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/

// Health check endpoint (public)
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'version' => 'v1',
    'timestamp' => now()->toIso8601String(),
]));

// Short link redirect (public, no auth)
Route::get('/s/{code}', [RedirectController::class, 'resolve'])->where('code', '[a-zA-Z0-9-]+');

// Razorpay webhook (no auth - verified by signature)
Route::post('/webhooks/razorpay', [\App\Http\Controllers\Api\V1\Billing\WebhookController::class, 'handle']);

// Social platform webhooks (no auth - verified by platform-specific mechanisms)
Route::prefix('webhooks')->group(function () {
    Route::get('/facebook', [\App\Http\Controllers\Api\V1\Social\PlatformWebhookController::class, 'facebookVerify']);
    Route::post('/facebook', [\App\Http\Controllers\Api\V1\Social\PlatformWebhookController::class, 'facebookHandle']);
    Route::get('/twitter', [\App\Http\Controllers\Api\V1\Social\PlatformWebhookController::class, 'twitterCrc']);
    Route::post('/twitter', [\App\Http\Controllers\Api\V1\Social\PlatformWebhookController::class, 'twitterHandle']);
    Route::post('/linkedin', [\App\Http\Controllers\Api\V1\Social\PlatformWebhookController::class, 'linkedinHandle']);
    Route::get('/whatsapp', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppWebhookController::class, 'verify']);
    Route::post('/whatsapp', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppWebhookController::class, 'handle']);
});

// Public authentication routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])
        ->middleware('throttle:auth');
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');
});

// MFA verification during login (requires mfa-pending token)
Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
    Route::post('/mfa/verify-login', [AuthController::class, 'mfaVerifyLogin'])
        ->middleware('throttle:auth');
});

// Protected auth routes (require authentication)
Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('/change-password', [PasswordController::class, 'changePassword']);

    // MFA management
    Route::post('/mfa/setup', [AuthController::class, 'mfaSetup']);
    Route::post('/mfa/verify-setup', [AuthController::class, 'mfaVerifySetup']);
    Route::post('/mfa/disable', [AuthController::class, 'mfaDisable']);
    Route::get('/mfa/status', [AuthController::class, 'mfaStatus']);
});

// Onboarding routes (require authentication + tenant)
Route::middleware(['auth:sanctum', 'tenant'])->prefix('onboarding')->group(function () {
    Route::post('/organization', [OnboardingController::class, 'submitOrganization']);
    Route::post('/workspace', [OnboardingController::class, 'submitWorkspace']);
});

// User routes (require authentication)
Route::middleware(['auth:sanctum', 'tenant'])->prefix('user')->group(function () {
    Route::get('/', [UserController::class, 'show']);
    Route::put('/', [UserController::class, 'update']);
    Route::put('/settings', [UserController::class, 'updateSettings']);
    Route::delete('/', [UserController::class, 'destroy']);
});

// Public invitation routes
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->middleware('auth:sanctum');
Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline']);

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Tenant routes
    Route::prefix('tenants/current')->group(function () {
        Route::get('/', [TenantController::class, 'show']);
        Route::put('/', [TenantController::class, 'update']);
        Route::put('/settings', [TenantController::class, 'updateSettings']);
        Route::get('/stats', [TenantController::class, 'stats']);

        // Tenant members
        Route::get('/members', [TenantMemberController::class, 'index']);
        Route::put('/members/{userId}', [TenantMemberController::class, 'update']);
        Route::delete('/members/{userId}', [TenantMemberController::class, 'destroy']);

        // Tenant invitations
        Route::get('/invitations', [InvitationController::class, 'index']);
        Route::post('/invitations', [InvitationController::class, 'store']);
        Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend']);
        Route::delete('/invitations/{id}', [InvitationController::class, 'destroy']);
    });

    // Workspace routes
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('/workspaces/{workspace}/archive', [WorkspaceController::class, 'archive']);
    Route::post('/workspaces/{workspace}/restore', [WorkspaceController::class, 'restore']);
    Route::put('/workspaces/{workspace}/settings', [WorkspaceController::class, 'updateSettings']);

    // Workspace members
    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store']);
    Route::put('/workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'update']);
    Route::delete('/workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'destroy']);

    // Workspace teams
    Route::get('/workspaces/{workspace}/teams', [TeamController::class, 'index']);
    Route::post('/workspaces/{workspace}/teams', [TeamController::class, 'store']);
    Route::get('/workspaces/{workspace}/teams/{team}', [TeamController::class, 'show']);
    Route::put('/workspaces/{workspace}/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/workspaces/{workspace}/teams/{team}', [TeamController::class, 'destroy']);
    Route::post('/workspaces/{workspace}/teams/{team}/members', [TeamController::class, 'addMember']);
    Route::delete('/workspaces/{workspace}/teams/{team}/members/{userId}', [TeamController::class, 'removeMember']);

    // WhatsApp account routes
    Route::prefix('whatsapp')->group(function () {
        Route::post('/onboard', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController::class, 'onboard']);
        Route::get('/accounts', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController::class, 'index']);
        Route::get('/accounts/{account}', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController::class, 'show']);
        Route::put('/accounts/{account}/profile', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController::class, 'updateProfile']);
        Route::post('/accounts/{account}/accept-compliance', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController::class, 'acceptCompliance']);
        Route::get('/accounts/{account}/phone-numbers', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppPhoneNumberController::class, 'index']);
    });

    // OAuth routes
    Route::prefix('oauth')->group(function () {
        Route::get('/{platform}/authorize', [OAuthController::class, 'getAuthorizationUrl'])
            ->where('platform', 'linkedin|facebook|instagram|twitter');
        Route::get('/{platform}/callback', [OAuthController::class, 'callback'])
            ->where('platform', 'linkedin|facebook|instagram|twitter')
            ->withoutMiddleware(['auth:sanctum', 'tenant']);
        Route::post('/{platform}/exchange', [OAuthController::class, 'exchange'])
            ->where('platform', 'linkedin|facebook|instagram|twitter');
        Route::post('/{platform}/connect', [OAuthController::class, 'connect'])
            ->where('platform', 'linkedin|facebook|instagram|twitter');
    });

    // Social accounts within workspace context
    Route::prefix('workspaces/{workspace}/social-accounts')->middleware('workspace.member')->group(function () {
        Route::get('/', [SocialAccountController::class, 'index']);
        Route::get('/health', [SocialAccountController::class, 'health']);
        Route::post('/', [SocialAccountController::class, 'store']);
        Route::get('/{socialAccount}', [SocialAccountController::class, 'show']);
        Route::delete('/{socialAccount}', [SocialAccountController::class, 'destroy']);
        Route::post('/{socialAccount}/refresh', [SocialAccountController::class, 'refresh']);
    });

    // Content/Post routes within workspace context
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        // Dashboard
        Route::get('/dashboard', [WorkspaceDashboardController::class, 'index']);

        // Posts
        Route::apiResource('posts', PostController::class);
        Route::post('/posts/{post}/submit', [PostController::class, 'submit']);
        Route::post('/posts/{post}/schedule', [PostController::class, 'schedule']);
        Route::put('/posts/{post}/schedule', [PostController::class, 'reschedule']);
        Route::post('/posts/{post}/publish', [PostController::class, 'publish']);
        Route::post('/posts/{post}/cancel', [PostController::class, 'cancel']);
        Route::post('/posts/{post}/duplicate', [PostController::class, 'duplicate']);

        // Bulk Post Operations
        Route::post('/posts/bulk-delete', [BulkPostController::class, 'bulkDelete']);
        Route::post('/posts/bulk-submit', [BulkPostController::class, 'bulkSubmit']);
        Route::post('/posts/bulk-schedule', [BulkPostController::class, 'bulkSchedule']);

        // Post Media
        Route::get('/posts/{post}/media', [PostMediaController::class, 'index']);
        Route::post('/posts/{post}/media', [PostMediaController::class, 'store']);
        Route::put('/posts/{post}/media/order', [PostMediaController::class, 'updateOrder']);
        Route::delete('/posts/{post}/media/{media}', [PostMediaController::class, 'destroy']);

        // Post Targets
        Route::get('/posts/{post}/targets', [PostTargetController::class, 'index']);
        Route::put('/posts/{post}/targets', [PostTargetController::class, 'update']);
        Route::delete('/posts/{post}/targets/{target}', [PostTargetController::class, 'destroy']);

        // Approvals
        Route::get('/approvals', [ApprovalController::class, 'index']);
        Route::post('/posts/{post}/approve', [ApprovalController::class, 'approve']);
        Route::post('/posts/{post}/reject', [ApprovalController::class, 'reject']);
        Route::get('/posts/{post}/approval-history', [ApprovalController::class, 'history']);

        // AI Assist
        Route::prefix('ai-assist')->group(function () {
            Route::post('/caption', [AIAssistController::class, 'generateCaption']);
            Route::post('/hashtags', [AIAssistController::class, 'suggestHashtags']);
            Route::post('/improve', [AIAssistController::class, 'improveContent']);
            Route::post('/ideas', [AIAssistController::class, 'generateIdeas']);
        });

        // Media Library
        Route::apiResource('media-library', MediaLibraryController::class);
        Route::get('/media-library-folders', [MediaLibraryController::class, 'folders']);
        Route::post('/media-library-folders', [MediaLibraryController::class, 'createFolder']);
        Route::post('/media-library/move', [MediaLibraryController::class, 'moveItems']);

        // Content Categories
        Route::apiResource('content-categories', ContentCategoryController::class);
        Route::post('/content-categories/reorder', [ContentCategoryController::class, 'reorder']);

        // Hashtag Groups
        Route::apiResource('hashtag-groups', HashtagGroupController::class);

        // Link Shortener
        Route::apiResource('short-links', LinkShortenerController::class)->except(['update']);
        Route::get('/short-links/{shortLink}/stats', [LinkShortenerController::class, 'stats']);

        // RSS Feeds
        Route::apiResource('rss-feeds', RssFeedController::class)->except(['update']);
        Route::get('/rss-feeds/{rssFeed}/items', [RssFeedController::class, 'items']);
        Route::post('/rss-feeds/{rssFeed}/fetch', [RssFeedController::class, 'fetch']);

        // Evergreen
        Route::apiResource('evergreen-rules', EvergreenController::class);
        Route::post('/evergreen-rules/{evergreenRule}/build-pool', [EvergreenController::class, 'buildPool']);
        Route::get('/evergreen-rules/{evergreenRule}/pool', [EvergreenController::class, 'pool']);

        // Approval Workflows
        Route::apiResource('approval-workflows', ApprovalWorkflowController::class);
        Route::post('/approval-workflows/{workflow}/set-default', [ApprovalWorkflowController::class, 'setDefault']);

        // Workspace Tasks
        Route::apiResource('tasks', WorkspaceTaskController::class);
        Route::post('/tasks/{task}/complete', [WorkspaceTaskController::class, 'complete']);

        // Post Notes
        Route::get('/posts/{post}/notes', [PostNoteController::class, 'index']);
        Route::post('/posts/{post}/notes', [PostNoteController::class, 'store']);
        Route::delete('/post-notes/{note}', [PostNoteController::class, 'destroy']);

        // Post Revisions
        Route::get('/posts/{post}/revisions', [PostRevisionController::class, 'index']);
        Route::get('/post-revisions/{revision}', [PostRevisionController::class, 'show']);
        Route::post('/post-revisions/{revision}/restore', [PostRevisionController::class, 'restore']);
    });

    // WhatsApp conversation routes within workspace context
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::get('/conversations', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppConversationController::class, 'index']);
        Route::get('/conversations/{conversation}', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppConversationController::class, 'show']);
        Route::post('/conversations/{conversation}/assign', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppConversationController::class, 'assign']);
        Route::post('/conversations/{conversation}/resolve', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppConversationController::class, 'resolve']);
        Route::post('/conversations/{conversation}/reopen', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppConversationController::class, 'reopen']);
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppMessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppMessageController::class, 'send']);
        Route::post('/conversations/{conversation}/messages/media', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppMessageController::class, 'sendMedia']);

        // WhatsApp Opt-in contacts
        Route::apiResource('whatsapp-contacts', \App\Http\Controllers\Api\V1\WhatsApp\WhatsAppOptInController::class);
        Route::post('/whatsapp-contacts/import', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppOptInController::class, 'import']);

        // WhatsApp Templates
        Route::apiResource('whatsapp-templates', \App\Http\Controllers\Api\V1\WhatsApp\WhatsAppTemplateController::class);
        Route::post('/whatsapp-templates/{template}/submit', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppTemplateController::class, 'submit']);
        Route::post('/whatsapp-templates/{template}/sync', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppTemplateController::class, 'sync']);

        // WhatsApp Campaigns
        Route::apiResource('whatsapp-campaigns', \App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class);
        Route::post('/whatsapp-campaigns/{campaign}/build-audience', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class, 'buildAudience']);
        Route::post('/whatsapp-campaigns/{campaign}/schedule', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class, 'schedule']);
        Route::post('/whatsapp-campaigns/{campaign}/send', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class, 'send']);
        Route::post('/whatsapp-campaigns/{campaign}/cancel', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class, 'cancel']);
        Route::get('/whatsapp-campaigns/{campaign}/stats', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class, 'stats']);
        Route::get('/whatsapp-campaigns/{campaign}/validate', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppCampaignController::class, 'validate']);

        // WhatsApp Automation Rules
        Route::apiResource('whatsapp-automation-rules', \App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAutomationController::class)
            ->parameters(['whatsapp-automation-rules' => 'automationRule']);

        // WhatsApp Quick Replies
        Route::apiResource('whatsapp-quick-replies', \App\Http\Controllers\Api\V1\WhatsApp\WhatsAppQuickReplyController::class)
            ->parameters(['whatsapp-quick-replies' => 'quickReply']);

        // WhatsApp Analytics
        Route::prefix('whatsapp-analytics')->group(function () {
            Route::get('/inbox-health', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAnalyticsController::class, 'inboxHealth']);
            Route::get('/marketing-performance', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAnalyticsController::class, 'marketingPerformance']);
            Route::get('/compliance-health', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAnalyticsController::class, 'complianceHealth']);
            Route::get('/agent-productivity', [\App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAnalyticsController::class, 'agentProductivity']);
        });
    });

    // Inbox routes within workspace context
    Route::prefix('workspaces/{workspace}/inbox')->middleware('workspace.member')->group(function () {
        Route::get('/', [InboxController::class, 'index']);
        Route::get('/stats', [InboxController::class, 'stats']);
        Route::post('/bulk-read', [InboxController::class, 'bulkRead']);
        Route::post('/bulk-resolve', [InboxController::class, 'bulkResolve']);
        Route::get('/{inboxItem}', [InboxController::class, 'show']);
        Route::post('/{inboxItem}/read', [InboxController::class, 'markRead']);
        Route::post('/{inboxItem}/unread', [InboxController::class, 'markUnread']);
        Route::post('/{inboxItem}/resolve', [InboxController::class, 'resolve']);
        Route::post('/{inboxItem}/unresolve', [InboxController::class, 'unresolve']);
        Route::post('/{inboxItem}/archive', [InboxController::class, 'archive']);
        Route::post('/{inboxItem}/assign', [InboxController::class, 'assign']);
        Route::post('/{inboxItem}/unassign', [InboxController::class, 'unassign']);

        // Replies
        Route::get('/{inboxItem}/replies', [InboxReplyController::class, 'index']);
        Route::post('/{inboxItem}/replies', [InboxReplyController::class, 'store']);

        // Internal Notes
        Route::get('/{inboxItem}/notes', [InboxNoteController::class, 'index']);
        Route::post('/{inboxItem}/notes', [InboxNoteController::class, 'store']);
        Route::delete('/notes/{note}', [InboxNoteController::class, 'destroy']);
    });

    // Inbox Tags within workspace context
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::apiResource('inbox-tags', InboxTagController::class);
        Route::post('/inbox/{inboxItem}/tags/{tag}', [InboxTagController::class, 'attach']);
        Route::delete('/inbox/{inboxItem}/tags/{tag}', [InboxTagController::class, 'detach']);

        // Saved Replies
        Route::apiResource('saved-replies', SavedReplyController::class);

        // Inbox Contacts (Social CRM)
        Route::apiResource('inbox-contacts', InboxContactController::class);

        // Inbox Automation
        Route::apiResource('inbox-automation-rules', InboxAutomationController::class)
            ->parameters(['inbox-automation-rules' => 'automationRule']);
    });

    // Analytics routes within workspace context
    Route::prefix('workspaces/{workspace}/analytics')->middleware('workspace.member')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/metrics', [AnalyticsController::class, 'metrics']);
        Route::get('/trends', [AnalyticsController::class, 'trends']);
        Route::get('/platforms', [AnalyticsController::class, 'platforms']);

        // Content Analytics
        Route::get('/content/overview', [ContentAnalyticsController::class, 'overview']);
        Route::get('/content/top-posts', [ContentAnalyticsController::class, 'topPosts']);
        Route::get('/content/by-type', [ContentAnalyticsController::class, 'byContentType']);
        Route::get('/content/best-times', [ContentAnalyticsController::class, 'bestTimes']);
    });

    // Audience Demographics
    Route::prefix('workspaces/{workspace}/analytics')->middleware('workspace.member')->group(function () {
        Route::get('/demographics', [AudienceDemographicsController::class, 'index']);
        Route::get('/demographics/latest', [AudienceDemographicsController::class, 'latest']);
        Route::post('/demographics/fetch', [AudienceDemographicsController::class, 'fetch']);
    });

    // Hashtag Tracking
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::get('/hashtag-tracking', [HashtagTrackingController::class, 'index']);
        Route::get('/hashtag-tracking/{hashtag}', [HashtagTrackingController::class, 'show']);
    });

    // Scheduled Reports
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::apiResource('scheduled-reports', ScheduledReportController::class);
    });

    // Social Listening
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::apiResource('monitored-keywords', KeywordMonitoringController::class);
        Route::get('/monitored-keywords/{keyword}/mentions', [KeywordMonitoringController::class, 'mentions']);
    });

    // Webhook Endpoints
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::apiResource('webhook-endpoints', WebhookEndpointController::class);
        Route::get('/webhook-endpoints/{webhookEndpoint}/deliveries', [WebhookEndpointController::class, 'deliveries']);
        Route::post('/webhook-endpoints/{webhookEndpoint}/test', [WebhookEndpointController::class, 'test']);
    });

    // Image Editor
    Route::prefix('workspaces/{workspace}')->middleware('workspace.member')->group(function () {
        Route::post('/image-editor/crop', [ImageEditorController::class, 'crop']);
        Route::post('/image-editor/resize', [ImageEditorController::class, 'resize']);
        Route::post('/image-editor/text', [ImageEditorController::class, 'addText']);
        Route::post('/image-editor/rotate', [ImageEditorController::class, 'rotate']);
        Route::post('/image-editor/flip', [ImageEditorController::class, 'flip']);
        Route::post('/image-editor/filter', [ImageEditorController::class, 'filter']);
        Route::post('/image-editor/watermark', [ImageEditorController::class, 'watermark']);
        Route::get('/watermark-presets', [ImageEditorController::class, 'watermarkPresets']);
        Route::post('/watermark-presets', [ImageEditorController::class, 'createPreset']);
        Route::delete('/watermark-presets/{preset}', [ImageEditorController::class, 'deletePreset']);
    });

    // Report routes within workspace context
    Route::prefix('workspaces/{workspace}/reports')->middleware('workspace.member')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::post('/', [ReportController::class, 'store']);
        Route::get('/{report}', [ReportController::class, 'show']);
        Route::get('/{report}/download', [ReportController::class, 'download']);
        Route::delete('/{report}', [ReportController::class, 'destroy']);
    });

    // Billing routes
    Route::prefix('billing')->group(function () {
        // Summary and usage
        Route::get('/summary', [BillingController::class, 'summary']);
        Route::get('/usage', [BillingController::class, 'usage']);
        Route::get('/plans', [BillingController::class, 'plans']);

        // Subscription
        Route::get('/subscription', [SubscriptionController::class, 'show']);
        Route::post('/subscription', [SubscriptionController::class, 'store']);
        Route::put('/subscription/plan', [SubscriptionController::class, 'changePlan']);
        Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/subscription/reactivate', [SubscriptionController::class, 'reactivate']);

        // Checkout
        Route::post('/checkout/initiate', [CheckoutController::class, 'initiate']);
        Route::post('/checkout/verify', [CheckoutController::class, 'verify']);

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->name('api.v1.billing.invoices.show');
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])
            ->name('api.v1.billing.invoices.download');

        // Payment methods
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::put('/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault']);
        Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
    });

    // Support routes
    Route::prefix('support')->group(function () {
        // Categories (for ticket creation form)
        Route::get('/categories', [SupportCategoryController::class, 'index']);

        // Tickets
        Route::get('/tickets', [SupportTicketController::class, 'index']);
        Route::post('/tickets', [SupportTicketController::class, 'store']);
        Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show']);
        Route::put('/tickets/{ticket}', [SupportTicketController::class, 'update']);
        Route::post('/tickets/{ticket}/close', [SupportTicketController::class, 'close']);
        Route::post('/tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen']);

        // Comments
        Route::get('/tickets/{ticket}/comments', [SupportCommentController::class, 'index']);
        Route::post('/tickets/{ticket}/comments', [SupportCommentController::class, 'store']);
    });

    // Audit log routes
    Route::prefix('audit')->group(function () {
        Route::get('/logs', [AuditLogController::class, 'index']);
        Route::get('/logs/{auditableType}/{auditableId}', [AuditLogController::class, 'forAuditable']);
    });

    // Security routes
    Route::prefix('security')->group(function () {
        Route::get('/events', [SecurityController::class, 'index']);
        Route::get('/stats', [SecurityController::class, 'stats']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::get('/login-history', [SessionController::class, 'loginHistory']);
        Route::delete('/sessions/{session}', [SessionController::class, 'terminate']);
        Route::post('/sessions/terminate-all', [SessionController::class, 'terminateAll']);
    });

    // Data privacy routes
    Route::prefix('privacy')->group(function () {
        Route::get('/export-requests', [DataPrivacyController::class, 'exportRequests']);
        Route::post('/export-requests', [DataPrivacyController::class, 'requestExport']);
        Route::get('/export-requests/{exportRequest}/download', [DataPrivacyController::class, 'downloadExport']);
        Route::get('/deletion-requests', [DataPrivacyController::class, 'deletionRequests']);
        Route::post('/deletion-requests', [DataPrivacyController::class, 'requestDeletion']);
        Route::delete('/deletion-requests/{deletionRequest}', [DataPrivacyController::class, 'cancelDeletion']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/recent', [NotificationController::class, 'recent']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/read-multiple', [NotificationController::class, 'markMultipleAsRead']);
        Route::get('/preferences', [NotificationController::class, 'preferences']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
    });
});

// Public Knowledge Base routes (no authentication required)
Route::prefix('kb')->group(function () {
    // Articles
    Route::get('/articles', [KBArticleController::class, 'index']);
    Route::get('/articles/featured', [KBArticleController::class, 'featured']);
    Route::get('/articles/popular', [KBArticleController::class, 'popular']);
    Route::get('/articles/{slug}', [KBArticleController::class, 'show']);

    // Feedback on articles
    Route::post('/articles/{article}/feedback', [KBFeedbackController::class, 'store']);

    // Categories
    Route::get('/categories', [KBCategoryController::class, 'index']);
    Route::get('/categories/tree', [KBCategoryController::class, 'tree']);
    Route::get('/categories/{slug}', [KBCategoryController::class, 'show']);

    // Search
    Route::get('/search', [KBSearchController::class, 'search']);
    Route::get('/search/suggest', [KBSearchController::class, 'suggest']);
    Route::get('/search/popular', [KBSearchController::class, 'popular']);
});

// Public Feedback routes (mostly no auth, voting requires auth)
Route::prefix('feedback')->group(function () {
    Route::get('/', [FeedbackController::class, 'index']);
    Route::post('/', [FeedbackController::class, 'store']);
    Route::get('/popular', [FeedbackController::class, 'popular']);
    Route::get('/{feedback}', [FeedbackController::class, 'show']);
    Route::get('/{feedback}/comments', [FeedbackController::class, 'comments']);
    Route::post('/{feedback}/comments', [FeedbackController::class, 'addComment']);

    // Authenticated actions (voting)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{feedback}/vote', [FeedbackController::class, 'vote']);
        Route::delete('/{feedback}/vote', [FeedbackController::class, 'removeVote']);
    });
});

// Public Roadmap routes (no auth required)
Route::prefix('roadmap')->group(function () {
    Route::get('/', [RoadmapController::class, 'index']);
    Route::get('/{roadmapItem}', [RoadmapController::class, 'show']);
});

// Public Changelog routes (no auth required)
Route::prefix('changelog')->group(function () {
    Route::get('/', [ReleaseNoteController::class, 'index']);
    Route::get('/{slug}', [ReleaseNoteController::class, 'show']);
    Route::post('/subscribe', [ReleaseNoteController::class, 'subscribe']);
    Route::post('/unsubscribe', [ReleaseNoteController::class, 'unsubscribe']);
});

// Super admin authentication routes
Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [SuperAdminAuthController::class, 'login'])
        ->middleware('throttle:auth');
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('/me', [SuperAdminAuthController::class, 'me']);
    });
});

// Admin routes (require admin authentication)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin KB routes
    Route::prefix('kb')->group(function () {
        // Articles
        Route::apiResource('articles', AdminKBArticleController::class);
        Route::post('/articles/{article}/publish', [AdminKBArticleController::class, 'publish']);
        Route::post('/articles/{article}/unpublish', [AdminKBArticleController::class, 'unpublish']);
        Route::post('/articles/{article}/archive', [AdminKBArticleController::class, 'archive']);

        // Categories
        Route::put('/categories/order', [AdminKBCategoryController::class, 'updateOrder']);
        Route::apiResource('categories', AdminKBCategoryController::class);

        // Feedback
        Route::get('/feedback', [AdminKBFeedbackController::class, 'index']);
        Route::get('/feedback/pending', [AdminKBFeedbackController::class, 'pending']);
        Route::post('/feedback/{feedback}/resolve', [AdminKBFeedbackController::class, 'resolve']);
        Route::post('/feedback/{feedback}/action', [AdminKBFeedbackController::class, 'action']);
        Route::post('/feedback/{feedback}/dismiss', [AdminKBFeedbackController::class, 'dismiss']);
    });

    // Admin Support routes
    Route::prefix('support')->group(function () {
        // Stats
        Route::get('/stats', [AdminSupportTicketController::class, 'stats']);

        // Tickets
        Route::get('/tickets', [AdminSupportTicketController::class, 'index']);
        Route::get('/tickets/{ticket}', [AdminSupportTicketController::class, 'show']);
        Route::post('/tickets/{ticket}/assign', [AdminSupportTicketController::class, 'assign']);
        Route::post('/tickets/{ticket}/unassign', [AdminSupportTicketController::class, 'unassign']);
        Route::put('/tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus']);
        Route::put('/tickets/{ticket}/priority', [AdminSupportTicketController::class, 'updatePriority']);

        // Comments and Notes
        Route::get('/tickets/{ticket}/comments', [AdminSupportCommentController::class, 'index']);
        Route::post('/tickets/{ticket}/comments', [AdminSupportCommentController::class, 'store']);
        Route::post('/tickets/{ticket}/notes', [AdminSupportCommentController::class, 'storeNote']);

        // Categories
        Route::apiResource('categories', AdminSupportCategoryController::class)->except(['show']);
    });

    // Admin Feedback routes
    Route::prefix('feedback')->group(function () {
        Route::get('/', [AdminFeedbackController::class, 'index']);
        Route::get('/stats', [AdminFeedbackController::class, 'stats']);
        Route::get('/{feedback}', [AdminFeedbackController::class, 'show']);
        Route::put('/{feedback}/status', [AdminFeedbackController::class, 'updateStatus']);
        Route::post('/{feedback}/link-roadmap', [AdminFeedbackController::class, 'linkToRoadmap']);
    });

    // Admin Roadmap routes
    Route::prefix('roadmap')->group(function () {
        Route::get('/', [AdminRoadmapController::class, 'index']);
        Route::post('/', [AdminRoadmapController::class, 'store']);
        Route::get('/{roadmapItem}', [AdminRoadmapController::class, 'show']);
        Route::put('/{roadmapItem}', [AdminRoadmapController::class, 'update']);
        Route::put('/{roadmapItem}/status', [AdminRoadmapController::class, 'updateStatus']);
        Route::delete('/{roadmapItem}', [AdminRoadmapController::class, 'destroy']);
    });

    // Admin Release Notes routes
    Route::prefix('release-notes')->group(function () {
        Route::get('/', [AdminReleaseNoteController::class, 'index']);
        Route::post('/', [AdminReleaseNoteController::class, 'store']);
        Route::get('/{releaseNote}', [AdminReleaseNoteController::class, 'show']);
        Route::put('/{releaseNote}', [AdminReleaseNoteController::class, 'update']);
        Route::post('/{releaseNote}/publish', [AdminReleaseNoteController::class, 'publish']);
        Route::post('/{releaseNote}/unpublish', [AdminReleaseNoteController::class, 'unpublish']);
        Route::delete('/{releaseNote}', [AdminReleaseNoteController::class, 'destroy']);
    });

    // Dashboard routes
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/dashboard/revenue', [AdminDashboardController::class, 'revenue']);
    Route::get('/dashboard/growth', [AdminDashboardController::class, 'growth']);
    Route::get('/dashboard/activity', [AdminDashboardController::class, 'activity']);
    Route::get('/dashboard/tenants', [AdminDashboardController::class, 'tenants']);
    Route::get('/dashboard/users', [AdminDashboardController::class, 'users']);

    // Tenant management routes
    Route::get('/tenants/stats', [AdminTenantController::class, 'stats']);
    Route::get('/tenants', [AdminTenantController::class, 'index']);
    Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show']);
    Route::put('/tenants/{tenant}', [AdminTenantController::class, 'update']);
    Route::post('/tenants/{tenant}/suspend', [AdminTenantController::class, 'suspend']);
    Route::post('/tenants/{tenant}/activate', [AdminTenantController::class, 'activate']);
    Route::post('/tenants/{tenant}/impersonate', [AdminTenantController::class, 'impersonate']);

    // User management routes
    Route::get('/users/stats', [AdminUserController::class, 'stats']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
    Route::post('/users/{user}/activate', [AdminUserController::class, 'activate']);
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);

    // Plan management routes
    Route::apiResource('plans', AdminPlanController::class);
    Route::put('/plans/{plan}/limits', [AdminPlanController::class, 'updateLimits']);

    // Feature flag management routes
    Route::get('/feature-flags/check/{key}', [AdminFeatureFlagController::class, 'check']);
    Route::apiResource('feature-flags', AdminFeatureFlagController::class);
    Route::post('/feature-flags/{featureFlag}/toggle', [AdminFeatureFlagController::class, 'toggle']);
    Route::post('/feature-flags/{featureFlag}/tenants/{tenant}', [AdminFeatureFlagController::class, 'addTenant']);
    Route::delete('/feature-flags/{featureFlag}/tenants/{tenant}', [AdminFeatureFlagController::class, 'removeTenant']);

    // Integration management routes
    Route::prefix('integrations')->group(function () {
        Route::get('/', [AdminIntegrationController::class, 'index']);
        Route::get('/{provider}', [AdminIntegrationController::class, 'show']);
        Route::put('/{provider}', [AdminIntegrationController::class, 'update']);
        Route::post('/{provider}/verify', [AdminIntegrationController::class, 'verify']);
        Route::post('/{provider}/force-reauth', [AdminIntegrationController::class, 'forceReauth']);
        Route::get('/{provider}/health', [AdminIntegrationController::class, 'health']);
        Route::post('/{provider}/toggle', [AdminIntegrationController::class, 'toggle']);
        Route::get('/{provider}/audit-log', [AdminIntegrationController::class, 'auditLog']);
    });

    // Config management routes
    Route::get('/config/categories', [AdminConfigController::class, 'categories']);
    Route::get('/config/grouped', [AdminConfigController::class, 'grouped']);
    Route::post('/config/bulk', [AdminConfigController::class, 'bulkSet']);
    Route::get('/config', [AdminConfigController::class, 'index']);
    Route::get('/config/{key}', [AdminConfigController::class, 'show'])->where('key', '[a-zA-Z0-9._-]+');
    Route::put('/config/{key}', [AdminConfigController::class, 'update'])->where('key', '[a-zA-Z0-9._-]+');
    Route::delete('/config/{key}', [AdminConfigController::class, 'destroy'])->where('key', '[a-zA-Z0-9._-]+');

    // Admin Data Privacy routes
    Route::prefix('privacy')->group(function () {
        Route::get('/export-requests', [AdminDataPrivacyController::class, 'exportRequests']);
        Route::get('/deletion-requests', [AdminDataPrivacyController::class, 'deletionRequests']);
        Route::post('/deletion-requests/{deletionRequest}/approve', [AdminDataPrivacyController::class, 'approveDeletion']);
        Route::post('/deletion-requests/{deletionRequest}/reject', [AdminDataPrivacyController::class, 'rejectDeletion']);
    });

    // WhatsApp Admin routes
    Route::prefix('whatsapp')->group(function () {
        Route::get('/accounts', [WhatsAppAdminController::class, 'listAllAccounts']);
        Route::get('/accounts/{account}', [WhatsAppAdminController::class, 'getAccountDetail']);
        Route::post('/accounts/{account}/suspend', [WhatsAppAdminController::class, 'suspendAccount']);
        Route::post('/accounts/{account}/reactivate', [WhatsAppAdminController::class, 'reactivateAccount']);
        Route::post('/accounts/{account}/disable-marketing', [WhatsAppAdminController::class, 'disableMarketing']);
        Route::post('/accounts/{account}/enable-marketing', [WhatsAppAdminController::class, 'enableMarketing']);
        Route::post('/phone-numbers/{phone}/override-rate-limit', [WhatsAppAdminController::class, 'overrideRateLimit']);
        Route::get('/accounts/{account}/consent-logs', [WhatsAppAdminController::class, 'viewConsentLogs']);
        Route::get('/alerts', [WhatsAppAdminController::class, 'listAlerts']);
        Route::post('/alerts/{alert}/acknowledge', [WhatsAppAdminController::class, 'acknowledgeAlert']);
    });
});
