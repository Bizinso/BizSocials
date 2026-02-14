# Implementation Plan: Digital Marketing Platform Completion

## Overview

This implementation plan breaks down the development of 11 feature phases into discrete, actionable tasks. Each phase builds on the existing Laravel 11 + Vue 3 codebase, following established patterns and integrating with the multi-tenant architecture. Tasks are organized to deliver incremental value with property-based testing integrated throughout.

## Tasks

### Phase 1: Email Marketing Engine

- [ ] 1. Set up email marketing foundation
  - [ ] 1.1 Create database migrations for email_campaigns, email_templates, email_sends, ab_tests tables
    - Add indexes for performance (campaign_id, contact_id, status, scheduled_at)
    - _Requirements: 1.2, 1.10_
  
  - [ ] 1.2 Create Eloquent models (EmailCampaign, EmailTemplate, EmailSend, ABTest)
    - Define relationships (campaign->sends, campaign->template, campaign->abTest)
    - Add casts for JSON fields
    - _Requirements: 1.2, 1.10_
  
  - [ ] 1.3 Implement EmailCampaignService with BaseService pattern
    - createCampaign(), createABTest(), scheduleCampaign(), sendCampaign() methods
    - _Requirements: 1.2, 1.9, 1.14_
  
  - [ ]* 1.4 Write property test for campaign data completeness
    - **Property 1: Campaign data completeness**
    - **Validates: Requirements 1.2**

- [ ] 2. Implement email builder and rendering
  - [ ] 2.1 Integrate GrapesJS email builder (BSD License) in Vue frontend
    - Create EmailBuilderComponent.vue with drag-and-drop interface
    - Add components: text, image, button, divider, spacer, social links
    - _Requirements: 1.1_
  
  - [ ] 2.2 Create EmailBuilderService for server-side rendering
    - renderEmail() method with merge tag replacement
    - validateContent() method for required elements
    - calculateSpamScore() method
    - _Requirements: 1.5, 1.21, 1.22_
  
  - [ ]* 2.3 Write property test for merge tag replacement
    - **Property 2: Merge tag replacement**
    - **Validates: Requirements 1.5**
  
  - [ ]* 2.4 Write property test for spam score range
    - **Property 10: Spam score range**
    - **Validates: Requirements 1.21**

- [ ] 3. Implement ESP integrations
  - [ ] 3.1 Create EmailServiceProvider interface
    - Define send(), sendBulk(), handleWebhook() methods
    - _Requirements: 1.7_
  
  - [ ] 3.2 Implement SendGridAdapter using sendgrid/sendgrid-php (MIT License)
    - OAuth configuration, send implementation, webhook handling
    - _Requirements: 1.7_
  
  - [ ] 3.3 Implement AmazonSESAdapter using aws/aws-sdk-php (Apache 2.0)
    - AWS credentials configuration, send implementation
    - _Requirements: 1.7_
  
  - [ ] 3.4 Implement MailgunAdapter using mailgun/mailgun-php (MIT License)
    - API key configuration, send implementation
    - _Requirements: 1.7_
  
  - [ ]* 3.5 Write unit tests for ESP adapters
    - Test send success, failure, webhook parsing
    - Mock HTTP responses
    - _Requirements: 1.7_

- [ ] 4. Implement email sending and tracking
  - [ ] 4.1 Create EmailSendingService
    - sendEmail() method with tracking pixel and link tracking
    - validateRecipients() method with RFC 5322 validation
    - trackOpen(), trackClick() methods
    - _Requirements: 1.6, 1.11, 1.12, 1.18, 1.19_
  
  - [ ] 4.2 Create SendEmailBatchJob for queue processing
    - Batch processing (1000 emails per job)
    - Retry logic with exponential backoff (max 3 retries)
    - _Requirements: 1.24, 1.25_
  
  - [ ] 4.3 Create email tracking routes (trackOpen, trackClick)
    - Public routes without authentication
    - Record timestamps and update EmailSend records
    - _Requirements: 1.11, 1.12_
  
  - [ ]* 4.4 Write property test for email validation filtering
    - **Property 3: Email validation filtering**
    - **Validates: Requirements 1.6**
  
  - [ ]* 4.5 Write property test for unsubscribe enforcement
    - **Property 8: Unsubscribe enforcement**
    - **Validates: Requirements 1.18**
  
  - [ ]* 4.6 Write property test for hard bounce handling
    - **Property 9: Hard bounce handling**
    - **Validates: Requirements 1.19**
  
  - [ ]* 4.7 Write property test for email send retry limit
    - **Property 11: Email send retry limit**
    - **Validates: Requirements 1.25**

- [ ] 5. Implement A/B testing
  - [ ] 5.1 Extend EmailCampaignService with A/B test logic
    - handleABTest() method to split recipients
    - sendVariant() method for each variant
    - _Requirements: 1.8, 1.9_
  
  - [ ] 5.2 Create SelectABTestWinnerJob
    - Calculate winning metric (open_rate, click_rate)
    - Send winning variant to remaining recipients
    - _Requirements: 1.9_
  
  - [ ]* 5.3 Write property test for A/B test winner selection
    - **Property 4: A/B test winner selection**
    - **Validates: Requirements 1.9**

- [ ] 6. Implement email analytics
  - [ ] 6.1 Create EmailAnalyticsService
    - Calculate deliverability rate, open rate, click rate
    - Aggregate metrics by campaign, date range
    - _Requirements: 1.13_
  
  - [ ]* 6.2 Write property test for deliverability rate calculation
    - **Property 7: Deliverability rate calculation**
    - **Validates: Requirements 1.13**

- [ ] 7. Create email API endpoints and controllers
  - [ ] 7.1 Create EmailCampaignController with CRUD operations
    - store(), index(), show(), update(), destroy(), send(), schedule(), sendTest()
    - _Requirements: 1.2, 1.14_
  
  - [ ] 7.2 Create EmailTemplateController
    - store(), index(), show() for template management
    - _Requirements: 1.3, 1.4_
  
  - [ ] 7.3 Create EmailAnalyticsController
    - show() for campaign analytics, overview() for aggregate stats
    - _Requirements: 1.13_
  
  - [ ]* 7.4 Write integration tests for email API endpoints
    - Test full campaign creation, sending, tracking flow
    - _Requirements: 1.2, 1.11, 1.12_

- [ ] 8. Create email frontend components
  - [ ] 8.1 Create CampaignListView.vue with PrimeVue DataTable
    - Display campaigns with status, metrics, actions
    - _Requirements: 1.2_
  
  - [ ] 8.2 Create CampaignEditorView.vue
    - Form for campaign details, email builder integration
    - A/B test configuration
    - _Requirements: 1.2, 1.8_
  
  - [ ] 8.3 Create CampaignAnalyticsView.vue with Chart.js
    - Display metrics: deliverability, opens, clicks, conversions
    - _Requirements: 1.13_

- [ ] 9. Checkpoint - Email Marketing Engine
  - Ensure all tests pass, verify email sending works with all ESPs, ask the user if questions arise.



### Phase 2: Landing Pages & Forms

- [ ] 10. Set up landing pages and forms foundation
  - [ ] 10.1 Create database migrations for landing_pages, forms, form_submissions, page_visits, page_conversions tables
    - Add indexes for slug, page_id, form_id, contact_id
    - _Requirements: 2.1, 2.5_
  
  - [ ] 10.2 Create Eloquent models (LandingPage, Form, FormSubmission, PageVisit, PageConversion)
    - Define relationships and JSON casts
    - _Requirements: 2.1, 2.5_
  
  - [ ] 10.3 Implement LandingPageService
    - createPage(), publishPage(), trackVisit(), trackConversion() methods
    - _Requirements: 2.1, 2.3, 2.13_
  
  - [ ]* 10.4 Write property test for unique URL generation
    - **Property 12: Unique URL generation**
    - **Validates: Requirements 2.3**

- [ ] 11. Implement landing page builder
  - [ ] 11.1 Create page builder component in Vue with drag-and-drop
    - Use Vue Draggable (MIT License)
    - Components: hero, text, image, form, button, testimonial, pricing table, FAQ
    - _Requirements: 2.1, 2.2_
  
  - [ ] 11.2 Create landing page templates (minimum 15)
    - Responsive designs for common use cases
    - _Requirements: 2.2_
  
  - [ ] 11.3 Implement page rendering engine
    - Convert JSON structure to HTML
    - Support custom CSS injection
    - _Requirements: 2.25_

- [ ] 12. Implement form builder and validation
  - [ ] 12.1 Create FormService
    - createForm(), validateSubmission(), handleSubmission() methods
    - Support field types: text, email, phone, number, dropdown, checkbox, radio, textarea, file, date, hidden
    - _Requirements: 2.5, 2.6, 2.7, 2.9_
  
  - [ ] 12.2 Implement validation rules engine
    - Required, min length, max length, pattern, custom validators
    - _Requirements: 2.6, 2.7, 2.8_
  
  - [ ] 12.3 Implement progressive profiling logic
    - Track previous submissions, hide already-collected fields
    - _Requirements: 2.21, 2.22_
  
  - [ ]* 12.4 Write property test for form validation enforcement
    - **Property 13: Form validation enforcement**
    - **Validates: Requirements 2.7**
  
  - [ ]* 12.5 Write property test for contact creation from form
    - **Property 14: Contact creation from form**
    - **Validates: Requirements 2.9**
  
  - [ ]* 12.6 Write property test for duplicate contact prevention
    - **Property 16: Duplicate contact prevention**
    - **Validates: Requirements 2.22**

- [ ] 13. Implement multi-step forms and conditional logic
  - [ ] 13.1 Add multi-step form support in FormService
    - Progress tracking, step validation
    - _Requirements: 2.10_
  
  - [ ] 13.2 Implement conditional logic engine
    - Show/hide fields based on previous answers
    - _Requirements: 2.11_

- [ ] 14. Implement A/B testing for landing pages
  - [ ] 14.1 Create page A/B test logic
    - Traffic splitting (50/50, 60/40, 70/30, 80/20)
    - Variant tracking
    - _Requirements: 2.12_
  
  - [ ] 14.2 Create analytics for page performance
    - Track visits, conversions, bounce rate, time on page
    - Calculate conversion rate
    - _Requirements: 2.13, 2.14_
  
  - [ ]* 14.3 Write property test for conversion rate calculation
    - **Property 15: Conversion rate calculation**
    - **Validates: Requirements 2.14**

- [ ] 15. Implement pop-ups and slide-ins
  - [ ] 15.1 Create PopupService
    - Trigger rules: time delay, scroll depth, exit intent, click trigger
    - _Requirements: 2.15, 2.16_
  
  - [ ] 15.2 Create frontend popup component
    - Display logic, trigger evaluation
    - _Requirements: 2.15, 2.16_

- [ ] 16. Implement form embedding and CAPTCHA
  - [ ] 16.1 Create form embed code generator
    - JavaScript snippet for external sites
    - _Requirements: 2.17_
  
  - [ ] 16.2 Integrate hCaptcha (MIT License) or reCAPTCHA v3
    - Validation on form submission
    - _Requirements: 2.20_
  
  - [ ] 16.3 Implement file upload handling
    - Validation (max 10MB, allowed types)
    - Store in MinIO S3-compatible storage
    - _Requirements: 2.23, 2.24_

- [ ] 17. Create landing page API endpoints and controllers
  - [ ] 17.1 Create LandingPageController
    - CRUD operations, publish(), createABTest(), analytics()
    - _Requirements: 2.1, 2.3, 2.12, 2.13_
  
  - [ ] 17.2 Create FormController
    - CRUD operations, submit(), embedCode(), submissions()
    - _Requirements: 2.5, 2.9, 2.17_
  
  - [ ]* 17.3 Write integration tests for landing page and form APIs
    - Test page creation, publishing, form submission flow
    - _Requirements: 2.1, 2.9_

- [ ] 18. Create landing page frontend components
  - [ ] 18.1 Create PageBuilderView.vue
    - Drag-and-drop interface, template selection
    - _Requirements: 2.1, 2.2_
  
  - [ ] 18.2 Create FormBuilderView.vue
    - Field configuration, validation rules, conditional logic
    - _Requirements: 2.5, 2.6, 2.11_
  
  - [ ] 18.3 Create PageAnalyticsView.vue
    - Display visits, conversions, A/B test results
    - _Requirements: 2.13, 2.14_

- [ ] 19. Checkpoint - Landing Pages & Forms
  - Ensure all tests pass, verify page publishing and form submissions work, ask the user if questions arise.



### Phase 3: Marketing Automation Engine

- [ ] 20. Set up automation foundation
  - [ ] 20.1 Create database migrations for workflows, workflow_enrollments, lead_scoring_rules tables
    - Add indexes for workflow_id, contact_id, status
    - _Requirements: 3.1, 3.14_
  
  - [ ] 20.2 Create Eloquent models (Workflow, WorkflowEnrollment, LeadScoringRule)
    - Define relationships and JSON casts for trigger_config and actions
    - _Requirements: 3.1_
  
  - [ ] 20.3 Implement WorkflowService
    - createWorkflow(), enrollContact(), executeStep() methods
    - _Requirements: 3.1, 3.4, 3.5_

- [ ] 21. Implement workflow builder backend
  - [ ] 21.1 Create TriggerEvaluator class
    - Support trigger types: form_submission, email_opened, email_clicked, link_clicked, page_visited, property_changed, date_based, time_based, API_event, manual
    - evaluateTrigger() method
    - _Requirements: 3.2_
  
  - [ ] 21.2 Create ActionExecutor class
    - Support action types: send_email, send_sms, send_whatsapp, update_property, add_to_segment, remove_from_segment, create_task, create_deal, wait, conditional_split, webhook, goal_check
    - execute() method with type matching
    - _Requirements: 3.3_
  
  - [ ] 21.3 Create ExecuteWorkflowStepJob
    - Process workflow steps asynchronously
    - Handle delays between steps
    - _Requirements: 3.6, 3.7_
  
  - [ ]* 21.4 Write property test for workflow enrollment on trigger
    - **Property 17: Workflow enrollment on trigger**
    - **Validates: Requirements 3.4**
  
  - [ ]* 21.5 Write property test for duplicate enrollment prevention
    - **Property 19: Duplicate enrollment prevention**
    - **Validates: Requirements 3.10**

- [ ] 22. Implement workflow goal tracking
  - [ ] 22.1 Create GoalTracker class
    - checkGoalAchievement(), exitWorkflowOnGoal() methods
    - _Requirements: 3.8, 3.9_
  
  - [ ]* 22.2 Write property test for goal-based unenrollment
    - **Property 18: Goal-based unenrollment**
    - **Validates: Requirements 3.9**

- [ ] 23. Implement workflow versioning
  - [ ] 23.1 Add versioning logic to WorkflowService
    - Create new version on edit, maintain old version for active enrollments
    - _Requirements: 3.11, 3.12_

- [ ] 24. Implement lead scoring
  - [ ] 24.1 Create LeadScoringService
    - calculateScore(), updateScore() methods
    - Support demographic and engagement scoring
    - _Requirements: 3.14, 3.15_
  
  - [ ]* 24.2 Write property test for lead score accumulation
    - **Property 20: Lead score accumulation**
    - **Validates: Requirements 3.14**

- [ ] 25. Implement webhook actions
  - [ ] 25.1 Add webhook execution to ActionExecutor
    - Send POST requests with contact data
    - Retry logic (max 3 retries, exponential backoff)
    - _Requirements: 3.21, 3.22_
  
  - [ ]* 25.2 Write property test for webhook retry limit
    - **Property 21: Webhook retry limit**
    - **Validates: Requirements 3.22**

- [ ] 26. Create workflow API endpoints and controllers
  - [ ] 26.1 Create WorkflowController
    - CRUD operations, activate(), deactivate(), enroll()
    - _Requirements: 3.1, 3.18, 3.19, 3.25_
  
  - [ ] 26.2 Create WorkflowEnrollmentController
    - index(), unenroll() methods
    - _Requirements: 3.19_
  
  - [ ] 26.3 Create LeadScoringController
    - rules(), calculate() methods
    - _Requirements: 3.14_
  
  - [ ]* 26.4 Write integration tests for workflow APIs
    - Test workflow creation, enrollment, execution
    - _Requirements: 3.1, 3.4_

- [ ] 27. Create workflow builder frontend
  - [ ] 27.1 Create WorkflowBuilderView.vue with visual canvas
    - Drag-and-drop nodes for triggers and actions
    - Connection lines between nodes
    - _Requirements: 3.1_
  
  - [ ] 27.2 Create trigger configuration components
    - Forms for each trigger type
    - _Requirements: 3.2_
  
  - [ ] 27.3 Create action configuration components
    - Forms for each action type
    - _Requirements: 3.3_
  
  - [ ] 27.4 Create WorkflowAnalyticsView.vue
    - Display enrollment stats, completion rates, goal achievement
    - _Requirements: 3.13_

- [ ] 28. Checkpoint - Marketing Automation Engine
  - Ensure all tests pass, verify workflows execute correctly, ask the user if questions arise.



### Phase 4: CRM Foundation

- [ ] 29. Set up CRM foundation
  - [ ] 29.1 Create database migrations for contacts, deals, activities, custom_properties tables
    - Add indexes for email, owner_id, lifecycle_stage, lead_score, stage
    - _Requirements: 4.1, 4.6_
  
  - [ ] 29.2 Create Eloquent models (Contact, Deal, Activity, CustomProperty)
    - Define relationships (contact->deals, contact->activities, deal->activities)
    - Add JSON casts for custom_properties
    - _Requirements: 4.1, 4.6_
  
  - [ ] 29.3 Implement ContactService
    - createContact(), updateContact(), mergeContacts(), deleteContact() methods
    - _Requirements: 4.1, 4.23, 4.24, 4.25_

- [ ] 30. Implement contact management
  - [ ] 30.1 Add custom property support to ContactService
    - setCustomProperties(), getCustomProperties() methods
    - Validate against CustomProperty definitions
    - _Requirements: 4.2, 4.3_
  
  - [ ] 30.2 Implement activity timeline
    - getTimeline() method to aggregate all contact interactions
    - _Requirements: 4.4, 4.5_
  
  - [ ]* 30.3 Write property test for duplicate detection by email
    - **Property 25: Duplicate detection by email**
    - **Validates: Requirements 4.22**
  
  - [ ]* 30.4 Write property test for contact merge data preservation
    - **Property 26: Contact merge data preservation**
    - **Validates: Requirements 4.24**

- [ ] 31. Implement contact import/export
  - [ ] 31.1 Add importContacts() method to ContactService
    - CSV parsing with field mapping
    - Email validation, duplicate handling
    - _Requirements: 4.19, 4.20_
  
  - [ ] 31.2 Add exportContacts() method
    - Generate CSV with selected fields
    - _Requirements: 4.21_
  
  - [ ]* 31.3 Write property test for contact import validation
    - **Property 24: Contact import validation**
    - **Validates: Requirements 4.20**

- [ ] 32. Implement deal management
  - [ ] 32.1 Create DealService
    - createDeal(), updateStage(), calculatePipelineMetrics() methods
    - _Requirements: 4.6, 4.8, 4.9_
  
  - [ ] 32.2 Implement pipeline metrics calculations
    - Total value, weighted value, win rate, average deal size
    - _Requirements: 4.9, 4.10, 4.11_
  
  - [ ]* 32.3 Write property test for weighted deal value calculation
    - **Property 22: Weighted deal value calculation**
    - **Validates: Requirements 4.10**
  
  - [ ]* 32.4 Write property test for win rate calculation
    - **Property 23: Win rate calculation**
    - **Validates: Requirements 4.11**

- [ ] 33. Implement tasks and notes
  - [ ] 33.1 Create Task model and service
    - Support task properties: title, description, due date, priority, status, assigned_to
    - _Requirements: 4.14, 4.15_
  
  - [ ] 33.2 Create Note model and service
    - Associate notes with contacts or deals
    - _Requirements: 4.16_

- [ ] 34. Implement contact segmentation
  - [ ] 34.1 Create SegmentService
    - createSegment(), getSegmentContacts(), evaluateContactForSegment() methods
    - Support filtering by properties and behavior
    - _Requirements: 4.17_
  
  - [ ] 34.2 Implement bulk actions
    - Update properties, add to segment, assign owner, add tags, delete
    - _Requirements: 4.18_

- [ ] 35. Implement external CRM integrations
  - [ ] 35.1 Create CRM integration adapters (Salesforce, HubSpot, Pipedrive)
    - Use open-source HTTP clients (Guzzle)
    - OAuth authentication, data sync
    - _Requirements: 4.25_

- [ ] 36. Create CRM API endpoints and controllers
  - [ ] 36.1 Create ContactController
    - CRUD operations, import(), export(), merge(), timeline()
    - _Requirements: 4.1, 4.19, 4.21, 4.23, 4.4_
  
  - [ ] 36.2 Create DealController
    - CRUD operations, updateStage(), pipelineMetrics()
    - _Requirements: 4.6, 4.8, 4.9_
  
  - [ ] 36.3 Create CustomPropertyController
    - CRUD operations for custom property definitions
    - _Requirements: 4.2_
  
  - [ ]* 36.4 Write integration tests for CRM APIs
    - Test contact creation, deal pipeline, import/export
    - _Requirements: 4.1, 4.6, 4.19_

- [ ] 37. Create CRM frontend components
  - [ ] 37.1 Create ContactListView.vue with PrimeVue DataTable
    - Display contacts with filtering, sorting, bulk actions
    - _Requirements: 4.1, 4.17, 4.18_
  
  - [ ] 37.2 Create ContactDetailView.vue
    - Display contact info, activity timeline, deals, tasks, notes
    - _Requirements: 4.1, 4.4, 4.14, 4.16_
  
  - [ ] 37.3 Create DealPipelineView.vue with Kanban board
    - Drag-and-drop deals between stages
    - Display pipeline metrics
    - _Requirements: 4.7, 4.9_
  
  - [ ] 37.4 Create ContactImportView.vue
    - CSV upload, field mapping interface
    - _Requirements: 4.19_

- [ ] 38. Checkpoint - CRM Foundation
  - Ensure all tests pass, verify contact and deal management works, ask the user if questions arise.



### Phase 5: Paid Advertising Management

- [ ] 39. Set up advertising foundation
  - [ ] 39.1 Create database migrations for ad_accounts, ad_campaigns, ad_metrics tables
    - Add indexes for platform, account_id, campaign_id, date
    - _Requirements: 5.1, 5.2, 5.3, 5.9_
  
  - [ ] 39.2 Create Eloquent models (AdAccount, AdCampaign, AdMetric)
    - Define relationships
    - _Requirements: 5.1, 5.2, 5.3_
  
  - [ ] 39.3 Implement AdCampaignService
    - connectAdAccount(), createCampaign(), syncMetrics() methods
    - _Requirements: 5.4, 5.6, 5.23_

- [ ] 40. Implement ad platform integrations
  - [ ] 40.1 Create AdPlatformAdapter interface
    - Define authenticate(), createCampaign(), getMetrics(), pauseCampaign(), resumeCampaign() methods
    - _Requirements: 5.1, 5.2, 5.3, 5.15_
  
  - [ ] 40.2 Implement FacebookAdsAdapter using facebook/graph-sdk
    - OAuth flow, campaign creation, metrics retrieval
    - _Requirements: 5.1, 5.4, 5.5, 5.9_
  
  - [ ] 40.3 Implement GoogleAdsAdapter using googleads/google-ads-php (Apache 2.0)
    - OAuth flow, campaign creation, metrics retrieval
    - _Requirements: 5.2, 5.4, 5.5, 5.9_
  
  - [ ] 40.4 Implement LinkedInAdsAdapter using Guzzle HTTP client
    - OAuth flow, campaign creation, metrics retrieval
    - _Requirements: 5.3, 5.4, 5.5, 5.9_
  
  - [ ]* 40.5 Write unit tests for ad platform adapters
    - Mock API responses, test authentication, campaign creation
    - _Requirements: 5.1, 5.2, 5.3_

- [ ] 41. Implement ad campaign management
  - [ ] 41.1 Add campaign creation with targeting and budget configuration
    - Support objective, targeting, daily/lifetime budget, schedule, bid strategy
    - _Requirements: 5.6_
  
  - [ ] 41.2 Add ad creative management
    - Upload images/videos, validate dimensions and file size
    - _Requirements: 5.7, 5.8_
  
  - [ ] 41.3 Implement campaign status management
    - Pause, resume, stop campaigns
    - _Requirements: 5.15_
  
  - [ ] 41.4 Implement budget alerts
    - Alert at 80% and 100% of budget
    - _Requirements: 5.13, 5.14, 5.25_

- [ ] 42. Implement ad metrics and analytics
  - [ ] 42.1 Create SyncAdMetricsJob
    - Fetch metrics from ad platforms every 6 hours
    - Store in ad_metrics table
    - _Requirements: 5.23_
  
  - [ ] 42.2 Implement metrics calculations
    - CTR, ROAS, ROI calculations
    - _Requirements: 5.10, 5.11, 5.17_
  
  - [ ]* 42.3 Write property test for CTR calculation
    - **Property 27: CTR calculation**
    - **Validates: Requirements 5.10**
  
  - [ ]* 42.4 Write property test for ROAS calculation
    - **Property 28: ROAS calculation**
    - **Validates: Requirements 5.11**
  
  - [ ]* 42.5 Write property test for ROI calculation
    - **Property 29: ROI calculation**
    - **Validates: Requirements 5.17**

- [ ] 43. Implement A/B testing for ads
  - [ ] 43.1 Add A/B test logic for ad creatives
    - Automatic winner selection based on conversion rate
    - _Requirements: 5.16_

- [ ] 44. Implement conversion tracking
  - [ ] 44.1 Create tracking pixel generator
    - Generate JavaScript pixel code
    - _Requirements: 5.19_
  
  - [ ] 44.2 Integrate pixels with landing pages
    - Auto-install on published landing pages
    - _Requirements: 5.20_

- [ ] 45. Implement audience synchronization
  - [ ] 45.1 Create audience sync logic
    - Create audiences from contact segments
    - Sync to ad platforms
    - _Requirements: 5.21, 5.22_

- [ ] 46. Create advertising API endpoints and controllers
  - [ ] 46.1 Create AdAccountController
    - connect(), index(), disconnect() methods
    - _Requirements: 5.4_
  
  - [ ] 46.2 Create AdCampaignController
    - CRUD operations, pause(), resume(), stop(), syncMetrics()
    - _Requirements: 5.6, 5.15, 5.23_
  
  - [ ] 46.3 Create AdAnalyticsController
    - Cross-platform reporting
    - _Requirements: 5.18_
  
  - [ ]* 46.4 Write integration tests for advertising APIs
    - Test account connection, campaign creation, metrics sync
    - _Requirements: 5.4, 5.6_

- [ ] 47. Create advertising frontend components
  - [ ] 47.1 Create AdAccountsView.vue
    - Connect ad accounts, display connected accounts
    - _Requirements: 5.4_
  
  - [ ] 47.2 Create AdCampaignListView.vue
    - Display campaigns with metrics, status controls
    - _Requirements: 5.6, 5.9, 5.15_
  
  - [ ] 47.3 Create AdCampaignEditorView.vue
    - Campaign configuration, creative upload
    - _Requirements: 5.6, 5.7_
  
  - [ ] 47.4 Create AdAnalyticsView.vue with Chart.js
    - Cross-platform metrics comparison
    - _Requirements: 5.18_

- [ ] 48. Checkpoint - Paid Advertising Management
  - Ensure all tests pass, verify ad platform integrations work, ask the user if questions arise.



### Phase 6: SEO & Content Marketing Tools

- [ ] 49. Set up SEO foundation
  - [ ] 49.1 Create database migrations for keywords, keyword_rankings, backlinks, seo_audits tables
    - Add indexes for keyword, url, domain, date
    - _Requirements: 6.1, 6.8, 6.9_
  
  - [ ] 49.2 Create Eloquent models (Keyword, KeywordRanking, Backlink, SEOAudit)
    - Define relationships
    - _Requirements: 6.1, 6.8, 6.9_
  
  - [ ] 49.3 Implement SEOAnalysisService
    - analyzeOnPage(), trackKeywordRanking(), analyzeBacklinks(), performSiteAudit() methods
    - _Requirements: 6.5, 6.3, 6.8, 6.9_

- [ ] 50. Implement keyword research and tracking
  - [ ] 50.1 Integrate with keyword research APIs (open-source or free tier)
    - Fetch search volume, difficulty, CPC data
    - _Requirements: 6.1, 6.2_
  
  - [ ] 50.2 Implement keyword ranking tracker
    - Daily ranking updates using Google Search Console API
    - _Requirements: 6.3, 6.4_
  
  - [ ] 50.3 Create TrackKeywordRankingsJob
    - Run daily to update rankings
    - _Requirements: 6.3_

- [ ] 51. Implement on-page SEO analysis
  - [ ] 51.1 Create on-page analysis engine
    - Analyze title, meta description, headings, keyword density, images, links, content length, readability
    - _Requirements: 6.5, 6.6_
  
  - [ ] 51.2 Implement SEO score calculation
    - Calculate score (0-100) based on on-page factors
    - _Requirements: 6.7_
  
  - [ ]* 51.3 Write property test for SEO score range
    - **Property 30: SEO score range**
    - **Validates: Requirements 6.7**

- [ ] 52. Implement backlink analysis
  - [ ] 52.1 Integrate with backlink data sources (open-source or free tier)
    - Fetch backlinks with domain authority, anchor text, follow/nofollow
    - _Requirements: 6.8_

- [ ] 53. Implement site audit
  - [ ] 53.1 Create site audit engine
    - Check SSL, broken links, mobile usability, page speed, duplicate content, missing meta tags
    - _Requirements: 6.9, 6.10_
  
  - [ ] 53.2 Create PerformSiteAuditJob
    - Run weekly audits
    - _Requirements: 6.9_

- [ ] 54. Implement content optimization
  - [ ] 54.1 Create ContentOptimizationService
    - optimizeContent() method with keyword analysis
    - Provide suggestions for improvement
    - _Requirements: 6.11, 6.12_
  
  - [ ] 54.2 Implement content score calculation
    - Calculate score based on SEO best practices
    - _Requirements: 6.13_
  
  - [ ]* 54.3 Write property test for content score range
    - **Property 31: Content score range**
    - **Validates: Requirements 6.13**

- [ ] 55. Implement blog post management
  - [ ] 55.1 Create BlogPost model and service
    - Editorial workflow: draft, in review, scheduled, published
    - _Requirements: 6.12_

- [ ] 56. Integrate with Google Search Console
  - [ ] 56.1 Implement Google Search Console API integration
    - OAuth authentication, fetch organic traffic data
    - _Requirements: 6.14, 6.15_

- [ ] 57. Implement competitor analysis
  - [ ] 57.1 Create competitor tracking
    - Compare keyword rankings and backlinks
    - _Requirements: 6.16_

- [ ] 58. Implement internal linking and sitemaps
  - [ ] 58.1 Create internal linking suggestion engine
    - Analyze site structure, suggest links
    - _Requirements: 6.17_
  
  - [ ] 58.2 Implement XML sitemap generation
    - Generate sitemaps for landing pages and blog posts
    - _Requirements: 6.18, 6.19_

- [ ] 59. Implement Core Web Vitals tracking
  - [ ] 59.1 Integrate with performance monitoring (open-source)
    - Track LCP, FID, CLS metrics
    - _Requirements: 6.20_

- [ ] 60. Implement schema markup
  - [ ] 60.1 Create schema markup generator
    - Support Article, Product, FAQ, HowTo, Organization schemas
    - _Requirements: 6.21, 6.22_
  
  - [ ] 60.2 Implement schema validation
    - Validate generated markup
    - _Requirements: 6.23_

- [ ] 61. Create SEO API endpoints and controllers
  - [ ] 61.1 Create KeywordController
    - CRUD operations, trackRanking()
    - _Requirements: 6.1, 6.3_
  
  - [ ] 61.2 Create SEOAnalysisController
    - analyzeOnPage(), analyzeBacklinks(), performAudit()
    - _Requirements: 6.5, 6.8, 6.9_
  
  - [ ] 61.3 Create ContentOptimizationController
    - optimize(), calculateScore()
    - _Requirements: 6.11, 6.13_
  
  - [ ]* 61.4 Write integration tests for SEO APIs
    - Test keyword tracking, on-page analysis, site audit
    - _Requirements: 6.3, 6.5, 6.9_

- [ ] 62. Create SEO frontend components
  - [ ] 62.1 Create KeywordTrackerView.vue
    - Display keywords with rankings, trends
    - _Requirements: 6.1, 6.3, 6.4_
  
  - [ ] 62.2 Create SEOAnalysisView.vue
    - Display on-page analysis, SEO score, suggestions
    - _Requirements: 6.5, 6.6, 6.7_
  
  - [ ] 62.3 Create SiteAuditView.vue
    - Display audit issues by severity
    - _Requirements: 6.9, 6.10_
  
  - [ ] 62.4 Create ContentOptimizerView.vue
    - Content editor with real-time optimization suggestions
    - _Requirements: 6.11, 6.12, 6.13_

- [ ] 63. Checkpoint - SEO & Content Marketing Tools
  - Ensure all tests pass, verify SEO analysis and tracking work, ask the user if questions arise.



### Phase 7: SMS Marketing

- [ ] 64. Set up SMS marketing foundation
  - [ ] 64.1 Create database migrations for sms_campaigns, sms_sends tables
    - Add indexes for campaign_id, contact_id, status
    - _Requirements: 7.1, 7.16_
  
  - [ ] 64.2 Create Eloquent models (SMSCampaign, SMSSend)
    - Define relationships
    - _Requirements: 7.1, 7.16_
  
  - [ ] 64.3 Implement SMSCampaignService
    - createCampaign(), sendCampaign(), handleIncomingSMS() methods
    - _Requirements: 7.2, 7.9_

- [ ] 65. Implement SMS provider integrations
  - [ ] 65.1 Create SMSProvider interface
    - Define send(), getDeliveryStatus() methods
    - _Requirements: 7.1_
  
  - [ ] 65.2 Implement TwilioAdapter using twilio/sdk (MIT License)
    - API configuration, send implementation, webhook handling
    - _Requirements: 7.1_
  
  - [ ] 65.3 Implement PlivoAdapter using plivo/plivo-php (MIT License)
    - API configuration, send implementation
    - _Requirements: 7.1_
  
  - [ ] 65.4 Implement MessageBirdAdapter using messagebird/php-rest-api (BSD License)
    - API configuration, send implementation
    - _Requirements: 7.1_
  
  - [ ]* 65.5 Write unit tests for SMS provider adapters
    - Mock API responses, test send success/failure
    - _Requirements: 7.1_

- [ ] 66. Implement SMS sending and tracking
  - [ ] 66.1 Add phone number validation (E.164 format)
    - validatePhoneNumbers() method
    - _Requirements: 7.2_
  
  - [ ] 66.2 Implement SMS personalization
    - personalizeMessage() method with merge tags
    - _Requirements: 7.3_
  
  - [ ] 66.3 Implement character limit enforcement
    - Calculate segments for long messages (160 chars single, 153 per segment)
    - _Requirements: 7.4, 7.5, 7.6_
  
  - [ ] 66.4 Create SendSMSBatchJob
    - Batch processing for SMS sending
    - _Requirements: 7.1_
  
  - [ ]* 66.5 Write property test for SMS merge tag replacement
    - **Property 32: SMS merge tag replacement**
    - **Validates: Requirements 7.3**
  
  - [ ]* 66.6 Write property test for SMS character limit validation
    - **Property 33: SMS character limit validation**
    - **Validates: Requirements 7.4**

- [ ] 67. Implement TCPA compliance
  - [ ] 67.1 Add opt-in verification before sending
    - Check contact.sms_opted_out field
    - _Requirements: 7.10_
  
  - [ ] 67.2 Implement STOP/START handling
    - Handle incoming STOP and START messages
    - Update contact opt-out status
    - _Requirements: 7.11, 7.12_
  
  - [ ]* 67.3 Write property test for SMS opt-out enforcement
    - **Property 34: SMS opt-out enforcement**
    - **Validates: Requirements 7.11**

- [ ] 68. Implement two-way SMS conversations
  - [ ] 68.1 Integrate SMS messages with existing Unified Inbox
    - Store incoming SMS in messages table
    - _Requirements: 7.9_

- [ ] 69. Implement SMS analytics
  - [ ] 69.1 Track SMS metrics (sent, delivered, failed, clicked, opt-outs, response rate)
    - _Requirements: 7.16_
  
  - [ ] 69.2 Calculate SMS delivery rate
    - _Requirements: 7.17_
  
  - [ ]* 69.3 Write property test for SMS delivery rate calculation
    - **Property 35: SMS delivery rate calculation**
    - **Validates: Requirements 7.17**

- [ ] 70. Implement SMS scheduling and A/B testing
  - [ ] 70.1 Add timezone-aware scheduling
    - Enforce sending windows (8 AM - 9 PM local time)
    - _Requirements: 7.18, 7.22, 7.23_
  
  - [ ] 70.2 Implement A/B testing for SMS content
    - _Requirements: 7.19_

- [ ] 71. Implement link tracking in SMS
  - [ ] 71.1 Add link shortening with click tracking
    - _Requirements: 7.21_

- [ ] 72. Create SMS API endpoints and controllers
  - [ ] 72.1 Create SMSCampaignController
    - CRUD operations, send(), schedule()
    - _Requirements: 7.2, 7.18_
  
  - [ ] 72.2 Create SMSAnalyticsController
    - Display campaign metrics
    - _Requirements: 7.16, 7.17_
  
  - [ ]* 72.3 Write integration tests for SMS APIs
    - Test campaign creation, sending, opt-out handling
    - _Requirements: 7.2, 7.11_

- [ ] 73. Create SMS frontend components
  - [ ] 73.1 Create SMSCampaignListView.vue
    - Display campaigns with metrics
    - _Requirements: 7.2, 7.16_
  
  - [ ] 73.2 Create SMSCampaignEditorView.vue
    - Message editor with character count, personalization
    - _Requirements: 7.2, 7.3, 7.4_
  
  - [ ] 73.3 Create SMSAnalyticsView.vue
    - Display delivery rates, opt-outs, response rates
    - _Requirements: 7.16, 7.17_

- [ ] 74. Checkpoint - SMS Marketing
  - Ensure all tests pass, verify SMS sending and TCPA compliance work, ask the user if questions arise.



### Phase 8: Advanced Analytics & Attribution

- [ ] 75. Set up analytics foundation
  - [ ] 75.1 Create database migrations for touchpoints, conversions, attribution_credits tables
    - Add indexes for contact_id, occurred_at, touchpoint_id, conversion_id
    - _Requirements: 8.3, 8.8, 8.2_
  
  - [ ] 75.2 Create Eloquent models (Touchpoint, Conversion, AttributionCredit)
    - Define relationships
    - _Requirements: 8.3, 8.8, 8.2_
  
  - [ ] 75.3 Create TouchpointService
    - recordTouchpoint() method to track all marketing interactions
    - _Requirements: 8.3_

- [ ] 76. Implement multi-touch attribution
  - [ ] 76.1 Create AttributionService
    - calculateAttribution() method with model selection
    - _Requirements: 8.1, 8.2_
  
  - [ ] 76.2 Implement attribution models
    - firstTouchAttribution(), lastTouchAttribution(), linearAttribution(), timeDecayAttribution(), positionBasedAttribution() methods
    - _Requirements: 8.1, 8.2_
  
  - [ ]* 76.3 Write property test for attribution credit totals
    - **Property 36: Attribution credit totals**
    - **Validates: Requirements 8.2**

- [ ] 77. Implement customer journey visualization
  - [ ] 77.1 Add getCustomerJourney() method to AttributionService
    - Aggregate all touchpoints and conversions for a contact
    - _Requirements: 8.3_

- [ ] 78. Implement marketing metrics calculations
  - [ ] 78.1 Create MarketingMetricsService
    - calculateCAC(), calculateCLV(), calculateChannelROI() methods
    - _Requirements: 8.4, 8.5, 8.7_
  
  - [ ]* 78.2 Write property test for CAC calculation
    - **Property 37: CAC calculation**
    - **Validates: Requirements 8.4**
  
  - [ ]* 78.3 Write property test for channel ROI calculation
    - **Property 38: Channel ROI calculation**
    - **Validates: Requirements 8.7**

- [ ] 79. Implement cohort analysis
  - [ ] 79.1 Create CohortAnalysisService
    - createCohort(), analyzeCohort() methods
    - Support grouping by acquisition date, first campaign, custom property
    - _Requirements: 8.10, 8.11_

- [ ] 80. Implement predictive analytics
  - [ ] 80.1 Create PredictiveAnalyticsService
    - predictChurn(), predictLeadScore(), predictLifetimeValue() methods
    - Use historical data for predictions
    - _Requirements: 8.12, 8.13, 8.14_

- [ ] 81. Implement marketing mix modeling
  - [ ] 81.1 Create MarketingMixService
    - optimizeBudgetAllocation() method
    - Recommend budget distribution based on historical ROI
    - _Requirements: 8.15, 8.16_

- [ ] 82. Implement cross-channel reporting
  - [ ] 82.1 Create ReportingService
    - generateReport() method for cross-channel data
    - Support email, SMS, social, paid ads, organic channels
    - _Requirements: 8.17_
  
  - [ ] 82.2 Calculate marketing influenced and sourced revenue
    - _Requirements: 8.21, 8.22_

- [ ] 83. Implement custom dashboards
  - [ ] 83.1 Create DashboardService
    - createDashboard(), addWidget(), getDashboardData() methods
    - _Requirements: 8.18_
  
  - [ ] 83.2 Implement dashboard widgets
    - Support metrics, charts, tables, funnels, cohorts, attribution widgets
    - _Requirements: 8.19_

- [ ] 84. Implement funnel analysis
  - [ ] 84.1 Create FunnelAnalysisService
    - createFunnel(), calculateDropOffRates() methods
    - _Requirements: 8.23, 8.24, 8.25_
  
  - [ ]* 84.2 Write property test for funnel drop-off calculation
    - **Property 39: Funnel drop-off calculation**
    - **Validates: Requirements 8.25**

- [ ] 85. Create analytics API endpoints and controllers
  - [ ] 85.1 Create AttributionController
    - calculateAttribution(), getCustomerJourney() methods
    - _Requirements: 8.1, 8.3_
  
  - [ ] 85.2 Create MarketingMetricsController
    - getCAC(), getCLV(), getChannelROI() methods
    - _Requirements: 8.4, 8.5, 8.7_
  
  - [ ] 85.3 Create DashboardController
    - CRUD operations for dashboards and widgets
    - _Requirements: 8.18, 8.19_
  
  - [ ] 85.4 Create ReportingController
    - generateReport(), exportReport() methods
    - _Requirements: 8.17, 8.20_
  
  - [ ]* 85.5 Write integration tests for analytics APIs
    - Test attribution calculation, metrics, reporting
    - _Requirements: 8.1, 8.4, 8.17_

- [ ] 86. Create analytics frontend components
  - [ ] 86.1 Create AttributionView.vue
    - Display attribution model selector, touchpoint visualization
    - _Requirements: 8.1, 8.2_
  
  - [ ] 86.2 Create CustomerJourneyView.vue
    - Timeline visualization of all touchpoints
    - _Requirements: 8.3_
  
  - [ ] 86.3 Create MarketingMetricsView.vue with Chart.js
    - Display CAC, CLV, ROI by channel
    - _Requirements: 8.4, 8.5, 8.7_
  
  - [ ] 86.4 Create DashboardBuilderView.vue
    - Drag-and-drop dashboard builder
    - _Requirements: 8.18, 8.19_
  
  - [ ] 86.5 Create FunnelAnalysisView.vue
    - Funnel visualization with drop-off rates
    - _Requirements: 8.23, 8.24, 8.25_

- [ ] 87. Checkpoint - Advanced Analytics & Attribution
  - Ensure all tests pass, verify attribution models and metrics work, ask the user if questions arise.



### Phase 9: Enhanced API & Integration Platform

- [ ] 88. Set up API and integration foundation
  - [ ] 88.1 Create database migrations for api_keys, webhooks, webhook_deliveries tables
    - Add indexes for key_hash, key_prefix, webhook_id, status
    - _Requirements: 9.2, 9.5, 9.7_
  
  - [ ] 88.2 Create Eloquent models (APIKey, Webhook, WebhookDelivery)
    - Define relationships
    - _Requirements: 9.2, 9.5, 9.7_
  
  - [ ] 88.3 Implement APIKeyService
    - generateAPIKey(), validateAPIKey(), revokeAPIKey() methods
    - _Requirements: 9.2_

- [ ] 89. Implement API rate limiting
  - [ ] 89.1 Create RateLimiter class
    - checkLimit(), incrementUsage(), getRemainingQuota() methods
    - Use Redis for distributed rate limiting
    - _Requirements: 9.3, 9.4_
  
  - [ ] 89.2 Create rate limiting middleware
    - Apply to all API routes
    - Return HTTP 429 with Retry-After header when exceeded
    - _Requirements: 9.4_
  
  - [ ]* 89.3 Write property test for rate limit enforcement
    - **Property 40: Rate limit enforcement**
    - **Validates: Requirements 9.4**

- [ ] 90. Implement webhook system
  - [ ] 90.1 Create WebhookService
    - createWebhook(), triggerWebhook(), deliverWebhook() methods
    - _Requirements: 9.5, 9.7, 9.8_
  
  - [ ] 90.2 Create DeliverWebhookJob
    - Async webhook delivery with retry logic (max 5 retries, exponential backoff)
    - _Requirements: 9.7, 9.8_
  
  - [ ] 90.3 Implement webhook signature verification
    - HMAC-SHA256 signature generation and verification
    - _Requirements: 9.9_
  
  - [ ]* 90.4 Write property test for webhook delivery attempt
    - **Property 41: Webhook delivery attempt**
    - **Validates: Requirements 9.7**
  
  - [ ]* 90.5 Write property test for webhook retry limit
    - **Property 42: Webhook retry limit**
    - **Validates: Requirements 9.8**
  
  - [ ]* 90.6 Write property test for webhook signature verification
    - **Property 43: Webhook signature verification**
    - **Validates: Requirements 9.9**

- [ ] 91. Implement comprehensive REST API
  - [ ] 91.1 Create API resource classes for all entities
    - ContactResource, CampaignResource, DealResource, WorkflowResource, etc.
    - _Requirements: 9.10_
  
  - [ ] 91.2 Implement bulk API operations
    - Support creating/updating up to 100 records per request
    - _Requirements: 9.11_
  
  - [ ] 91.3 Implement API versioning
    - Version prefix in routes (/api/v1/)
    - Maintain backward compatibility for 12 months
    - _Requirements: 9.12_
  
  - [ ] 91.4 Implement API pagination
    - Cursor-based pagination for large datasets
    - _Requirements: 9.21_
  
  - [ ] 91.5 Implement API filtering, sorting, field selection
    - Query parameters for filtering and sorting
    - _Requirements: 9.22_

- [ ] 92. Implement GraphQL API
  - [ ] 92.1 Install and configure lighthouse-php (MIT License)
    - GraphQL schema definition
    - _Requirements: 9.13, 9.14_
  
  - [ ] 92.2 Create GraphQL queries for all entities
    - _Requirements: 9.14_
  
  - [ ] 92.3 Create GraphQL mutations for all entities
    - _Requirements: 9.14_
  
  - [ ] 92.4 Implement GraphQL subscriptions for real-time updates
    - _Requirements: 9.14_

- [ ] 93. Create API documentation
  - [ ] 93.1 Generate OpenAPI 3.0 specification
    - Use l5-swagger (MIT License) for Laravel
    - _Requirements: 9.1_
  
  - [ ] 93.2 Create interactive API documentation
    - Swagger UI with try-it-out functionality
    - _Requirements: 9.25_

- [ ] 94. Implement SDKs
  - [ ] 94.1 Create JavaScript/TypeScript SDK
    - HTTP client with authentication, error handling
    - _Requirements: 9.15_
  
  - [ ] 94.2 Create Python SDK
    - HTTP client with authentication, error handling
    - _Requirements: 9.15_
  
  - [ ] 94.3 Create PHP SDK
    - HTTP client with authentication, error handling
    - _Requirements: 9.15_

- [ ] 95. Implement Zapier integration
  - [ ] 95.1 Create Zapier app using Zapier Platform SDK
    - Define triggers, actions, searches
    - _Requirements: 9.16_

- [ ] 96. Create integration marketplace
  - [ ] 96.1 Build marketplace UI for third-party integrations
    - _Requirements: 9.17_
  
  - [ ] 96.2 Implement OAuth 2.0 for third-party apps
    - Authorization code flow with scopes
    - _Requirements: 9.18, 9.19_

- [ ] 97. Implement API monitoring
  - [ ] 97.1 Create API usage analytics
    - Track requests per endpoint, response times, error rates
    - _Requirements: 9.20_
  
  - [ ] 97.2 Implement API request logging
    - Log all requests for debugging
    - _Requirements: 9.24_

- [ ] 98. Create integration API endpoints and controllers
  - [ ] 98.1 Create APIKeyController
    - CRUD operations for API keys
    - _Requirements: 9.2_
  
  - [ ] 98.2 Create WebhookController
    - CRUD operations for webhooks
    - _Requirements: 9.5_
  
  - [ ] 98.3 Create IntegrationController
    - OAuth connection flow for third-party integrations
    - _Requirements: 9.18_
  
  - [ ]* 98.4 Write integration tests for API and webhook systems
    - Test API key authentication, rate limiting, webhook delivery
    - _Requirements: 9.2, 9.4, 9.7_

- [ ] 99. Create integration frontend components
  - [ ] 99.1 Create APIKeysView.vue
    - Display API keys, generate new keys, revoke keys
    - _Requirements: 9.2_
  
  - [ ] 99.2 Create WebhooksView.vue
    - Display webhooks, create/edit webhooks, view delivery logs
    - _Requirements: 9.5, 9.7_
  
  - [ ] 99.3 Create IntegrationMarketplaceView.vue
    - Browse and connect third-party integrations
    - _Requirements: 9.17_

- [ ] 100. Checkpoint - Enhanced API & Integration Platform
  - Ensure all tests pass, verify API authentication and webhooks work, ask the user if questions arise.



### Phase 10: Enterprise Features

- [ ] 101. Set up enterprise security foundation
  - [ ] 101.1 Create database migrations for email_domains, backups tables
    - Add indexes for domain, tenant_id
    - _Requirements: 10.9, 10.18_
  
  - [ ] 101.2 Create Eloquent models (EmailDomain, Backup)
    - _Requirements: 10.9, 10.18_
  
  - [ ] 101.3 Implement SecurityService
    - checkIPWhitelist(), detectSuspiciousActivity() methods
    - _Requirements: 10.3, 10.4, 10.16, 10.17_

- [ ] 102. Implement data encryption
  - [ ] 102.1 Ensure all sensitive fields use Laravel encryption
    - Encrypt access_tokens, api_keys, passwords, dkim_private_key
    - _Requirements: 10.1_
  
  - [ ] 102.2 Verify TLS 1.3 for all connections
    - Configure web server and database connections
    - _Requirements: 10.2_

- [ ] 103. Implement IP whitelisting
  - [ ] 103.1 Add IP whitelist middleware
    - Check incoming requests against tenant whitelist
    - Support CIDR notation
    - _Requirements: 10.3, 10.4_
  
  - [ ]* 103.2 Write property test for IP whitelist enforcement
    - **Property 44: IP whitelist enforcement**
    - **Validates: Requirements 10.4**

- [ ] 104. Implement audit logging
  - [ ] 104.1 Extend existing audit log system
    - Ensure all user actions are logged
    - _Requirements: 10.5_
  
  - [ ] 104.2 Implement 7-year retention
    - Configure audit log retention policy
    - _Requirements: 10.6_

- [ ] 105. Implement data retention policies
  - [ ] 105.1 Create ComplianceService
    - applyDataRetentionPolicy() method
    - Support retention periods: 30 days, 90 days, 1 year, 2 years, 5 years, 7 years, never
    - _Requirements: 10.7_
  
  - [ ] 105.2 Create ApplyDataRetentionJob
    - Run daily to delete old data
    - _Requirements: 10.7_

- [ ] 106. Implement GDPR compliance
  - [ ] 106.1 Add exportContactData() method to ComplianceService
    - Generate complete data package for contact
    - _Requirements: 10.8, 10.9_
  
  - [ ] 106.2 Add deleteContactData() method
    - Anonymize personal data (not hard delete)
    - _Requirements: 10.10_
  
  - [ ]* 106.3 Write property test for GDPR data deletion
    - **Property 45: GDPR data deletion**
    - **Validates: Requirements 10.10**

- [ ] 107. Implement SOC 2 compliance features
  - [ ] 107.1 Document security controls
    - Create security policy documentation
    - _Requirements: 10.11_

- [ ] 108. Implement vulnerability scanning
  - [ ] 108.1 Integrate open-source security scanner (e.g., OWASP ZAP)
    - Run weekly automated scans
    - _Requirements: 10.12_

- [ ] 109. Implement data residency
  - [ ] 109.1 Add region configuration per tenant
    - Support US, EU, APAC regions
    - _Requirements: 10.13_

- [ ] 110. Implement session management
  - [ ] 110.1 Add configurable session timeout per workspace
    - Support 15 min, 30 min, 1 hour, 4 hours, 8 hours, 24 hours
    - _Requirements: 10.14, 10.15_

- [ ] 111. Implement suspicious activity detection
  - [ ] 111.1 Add detectSuspiciousActivity() logic
    - Detect multiple failed logins, new location, new device
    - Send email alerts
    - _Requirements: 10.16, 10.17_

- [ ] 112. Implement backup and disaster recovery
  - [ ] 112.1 Create BackupService
    - createBackup(), restoreBackup() methods
    - _Requirements: 10.18_
  
  - [ ] 112.2 Create CreateBackupJob
    - Run every 6 hours
    - _Requirements: 10.19_
  
  - [ ] 112.3 Implement point-in-time recovery
    - Support recovery for last 30 days
    - _Requirements: 10.20_

- [ ] 113. Implement advanced RBAC
  - [ ] 113.1 Extend existing Spatie Permission package
    - Add custom roles, permission inheritance, role hierarchies
    - _Requirements: 10.21, 10.22_
  
  - [ ] 113.2 Implement field-level permissions
    - Control access to sensitive fields
    - _Requirements: 10.23_
  
  - [ ] 113.3 Implement data masking
    - Mask sensitive fields based on permissions
    - _Requirements: 10.24_

- [ ] 114. Create enterprise API endpoints and controllers
  - [ ] 114.1 Create SecurityController
    - Configure IP whitelist, session timeout
    - _Requirements: 10.3, 10.14_
  
  - [ ] 114.2 Create ComplianceController
    - exportContactData(), deleteContactData(), configureRetentionPolicy()
    - _Requirements: 10.8, 10.10, 10.7_
  
  - [ ] 114.3 Create BackupController
    - createBackup(), restoreBackup(), listBackups()
    - _Requirements: 10.18, 10.20_
  
  - [ ]* 114.4 Write integration tests for enterprise features
    - Test IP whitelisting, GDPR export/deletion, backups
    - _Requirements: 10.4, 10.9, 10.10_

- [ ] 115. Create enterprise frontend components
  - [ ] 115.1 Create SecuritySettingsView.vue
    - Configure IP whitelist, session timeout, MFA
    - _Requirements: 10.3, 10.14_
  
  - [ ] 115.2 Create ComplianceView.vue
    - Configure data retention, export/delete contact data
    - _Requirements: 10.7, 10.8, 10.10_
  
  - [ ] 115.3 Create AuditLogView.vue
    - Display audit logs with filtering
    - _Requirements: 10.5_
  
  - [ ] 115.4 Create BackupView.vue
    - Display backups, create manual backup, restore
    - _Requirements: 10.18, 10.20_

- [ ] 116. Checkpoint - Enterprise Features
  - Ensure all tests pass, verify security and compliance features work, ask the user if questions arise.



### Phase 11: White-Label & Multi-Brand

- [ ] 117. Set up white-label foundation
  - [ ] 117.1 Create database migrations for brands table
    - Add indexes for tenant_id
    - _Requirements: 11.8_
  
  - [ ] 117.2 Create Brand model
    - Define relationships with tenant
    - _Requirements: 11.8_
  
  - [ ] 117.3 Implement BrandingService
    - updateBranding(), mapCustomDomain(), configureEmailDomain() methods
    - _Requirements: 11.1, 11.3, 11.5_

- [ ] 118. Implement custom branding
  - [ ] 118.1 Add branding configuration to tenant settings
    - Store logo_url, primary_color, secondary_color, accent_color, font_family, white_label_enabled
    - _Requirements: 11.1_
  
  - [ ] 118.2 Implement white-label mode
    - Hide platform branding when enabled
    - _Requirements: 11.2_

- [ ] 119. Implement custom domain mapping
  - [ ] 119.1 Add domain ownership verification
    - DNS TXT record verification
    - _Requirements: 11.3, 11.4_
  
  - [ ] 119.2 Implement SSL certificate provisioning
    - Use Let's Encrypt via ACME protocol
    - Create ProvisionSSLJob
    - _Requirements: 11.3_

- [ ] 120. Implement custom email domains
  - [ ] 120.1 Add DKIM and SPF configuration
    - Generate DKIM keys, provide DNS records
    - _Requirements: 11.5, 11.6_
  
  - [ ] 120.2 Implement DNS validation
    - Verify DKIM and SPF records
    - _Requirements: 11.6_
  
  - [ ]* 120.3 Write property test for email domain DNS validation
    - **Property 46: Email domain DNS validation**
    - **Validates: Requirements 11.6**

- [ ] 121. Implement custom SMTP configuration
  - [ ] 121.1 Add SMTP settings per workspace
    - Support custom SMTP server, port, username, password
    - _Requirements: 11.7_

- [ ] 122. Implement multi-brand management
  - [ ] 122.1 Create MultiBrandService
    - createBrand(), getBrandAssets() methods
    - _Requirements: 11.8, 11.9_
  
  - [ ] 122.2 Associate content with brands
    - Add brand_id to campaigns, templates, landing pages
    - _Requirements: 11.11_

- [ ] 123. Implement brand-specific templates
  - [ ] 123.1 Filter templates by brand
    - _Requirements: 11.10_

- [ ] 124. Implement custom CSS
  - [ ] 124.1 Add custom CSS support per workspace
    - Sanitize CSS to prevent security vulnerabilities
    - _Requirements: 11.12, 11.13_

- [ ] 125. Implement custom login pages
  - [ ] 125.1 Create custom login page builder
    - Support custom branding on login page
    - _Requirements: 11.14_

- [ ] 126. Implement custom email templates per brand
  - [ ] 126.1 Add brand-specific email signature
    - _Requirements: 11.15_

- [ ] 127. Implement reseller features
  - [ ] 127.1 Add reseller pricing configuration
    - Support markup percentages
    - _Requirements: 11.16_
  
  - [ ] 127.2 Implement reseller billing
    - Charge reseller, allow them to bill clients separately
    - _Requirements: 11.17_
  
  - [ ] 127.3 Create reseller dashboard
    - Display all client workspaces and usage
    - _Requirements: 11.18_

- [ ] 128. Implement white-label API documentation
  - [ ] 128.1 Add custom branding to API docs
    - _Requirements: 11.19_

- [ ] 129. Implement custom help and support
  - [ ] 129.1 Add custom help documentation URLs per workspace
    - _Requirements: 11.20_
  
  - [ ] 129.2 Add custom support email addresses
    - _Requirements: 11.21_

- [ ] 130. Implement feature hiding
  - [ ] 130.1 Add feature flags per workspace
    - Allow resellers to hide specific features
    - _Requirements: 11.22_

- [ ] 131. Implement custom onboarding
  - [ ] 131.1 Add custom onboarding flows per workspace
    - _Requirements: 11.23_

- [ ] 132. Implement brand asset export
  - [ ] 132.1 Add exportBrandAssets() method
    - Export logos, colors, fonts for external use
    - _Requirements: 11.24_

- [ ] 133. Implement brand consistency checking
  - [ ] 133.1 Add checkBrandConsistency() method
    - Verify all content uses correct brand assets
    - _Requirements: 11.25_

- [ ] 134. Create white-label API endpoints and controllers
  - [ ] 134.1 Create BrandingController
    - updateBranding(), mapCustomDomain(), configureEmailDomain()
    - _Requirements: 11.1, 11.3, 11.5_
  
  - [ ] 134.2 Create BrandController
    - CRUD operations for brands
    - _Requirements: 11.8_
  
  - [ ] 134.3 Create ResellerController
    - Configure pricing, view client workspaces
    - _Requirements: 11.16, 11.18_
  
  - [ ]* 134.4 Write integration tests for white-label features
    - Test custom domain mapping, email domain validation, branding
    - _Requirements: 11.3, 11.6, 11.1_

- [ ] 135. Create white-label frontend components
  - [ ] 135.1 Create BrandingSettingsView.vue
    - Configure logo, colors, fonts, white-label mode
    - _Requirements: 11.1, 11.2_
  
  - [ ] 135.2 Create CustomDomainView.vue
    - Configure custom domain, view DNS records, SSL status
    - _Requirements: 11.3, 11.4_
  
  - [ ] 135.3 Create EmailDomainView.vue
    - Configure email domain, view DKIM/SPF records, validation status
    - _Requirements: 11.5, 11.6_
  
  - [ ] 135.4 Create BrandManagementView.vue
    - Create and manage multiple brands
    - _Requirements: 11.8, 11.9_
  
  - [ ] 135.5 Create ResellerDashboardView.vue
    - Display client workspaces, usage, billing
    - _Requirements: 11.18_

- [ ] 136. Checkpoint - White-Label & Multi-Brand
  - Ensure all tests pass, verify custom branding and domains work, ask the user if questions arise.



### Final Integration & Testing

- [ ] 137. Cross-feature integration
  - [ ] 137.1 Integrate email campaigns with automation workflows
    - Allow workflows to send email campaigns
    - _Requirements: 3.3, 1.2_
  
  - [ ] 137.2 Integrate SMS campaigns with automation workflows
    - Allow workflows to send SMS campaigns
    - _Requirements: 3.3, 7.2_
  
  - [ ] 137.3 Integrate form submissions with automation workflows
    - Trigger workflows on form submission
    - _Requirements: 3.2, 2.9_
  
  - [ ] 137.4 Integrate landing page visits with touchpoint tracking
    - Record page visits as touchpoints for attribution
    - _Requirements: 2.13, 8.3_
  
  - [ ] 137.5 Integrate email opens/clicks with touchpoint tracking
    - Record email interactions as touchpoints
    - _Requirements: 1.11, 1.12, 8.3_
  
  - [ ] 137.6 Integrate ad conversions with attribution
    - Track ad conversions for attribution modeling
    - _Requirements: 5.19, 8.2_

- [ ] 138. End-to-end testing
  - [ ]* 138.1 Write end-to-end test for email campaign flow
    - Create campaign → send → track opens/clicks → attribute conversion
    - _Requirements: 1.2, 1.11, 1.12, 8.2_
  
  - [ ]* 138.2 Write end-to-end test for landing page conversion flow
    - Create page → publish → submit form → create contact → trigger workflow
    - _Requirements: 2.1, 2.9, 3.4_
  
  - [ ]* 138.3 Write end-to-end test for CRM pipeline flow
    - Create contact → create deal → move through stages → calculate metrics
    - _Requirements: 4.1, 4.6, 4.8, 4.9_
  
  - [ ]* 138.4 Write end-to-end test for automation workflow
    - Trigger workflow → execute actions → track completion
    - _Requirements: 3.4, 3.5, 3.13_

- [ ] 139. Performance optimization
  - [ ] 139.1 Add database indexes for all foreign keys
    - Review and optimize all queries
    - _Requirements: Performance_
  
  - [ ] 139.2 Implement query caching for frequently accessed data
    - Cache templates, segments, settings
    - _Requirements: Performance_
  
  - [ ] 139.3 Optimize N+1 queries with eager loading
    - Review all relationships and add eager loading
    - _Requirements: Performance_
  
  - [ ] 139.4 Implement Redis caching for expensive calculations
    - Cache attribution calculations, metrics aggregations
    - _Requirements: Performance_

- [ ] 140. Security hardening
  - [ ] 140.1 Run security audit on all API endpoints
    - Verify authentication, authorization, input validation
    - _Requirements: Security_
  
  - [ ] 140.2 Implement Content Security Policy headers
    - Configure CSP for XSS prevention
    - _Requirements: Security_
  
  - [ ] 140.3 Verify all sensitive data is encrypted
    - Audit database for unencrypted sensitive fields
    - _Requirements: 10.1_
  
  - [ ] 140.4 Run penetration testing
    - Use open-source tools (OWASP ZAP, Burp Suite Community)
    - _Requirements: 10.25_

- [ ] 141. Documentation
  - [ ] 141.1 Complete API documentation
    - Ensure all endpoints documented in OpenAPI spec
    - _Requirements: 9.1_
  
  - [ ] 141.2 Create developer guides
    - Getting started, authentication, common use cases
    - _Requirements: 9.1_
  
  - [ ] 141.3 Create user documentation
    - Feature guides, tutorials, best practices
    - _Requirements: Documentation_
  
  - [ ] 141.4 Create deployment documentation
    - Infrastructure setup, configuration, scaling
    - _Requirements: Documentation_

- [ ] 142. Database migration from MySQL to PostgreSQL
  - [ ] 142.1 Set up PostgreSQL database
    - Configure connection, create schemas
    - _Requirements: Database_Migration_
  
  - [ ] 142.2 Implement dual-write to both databases
    - Write to MySQL and PostgreSQL simultaneously
    - _Requirements: Database_Migration_
  
  - [ ] 142.3 Migrate existing tenant data
    - Create migration scripts, run incremental migrations
    - _Requirements: Database_Migration_
  
  - [ ] 142.4 Switch to PostgreSQL as primary
    - Update application to read from PostgreSQL
    - _Requirements: Database_Migration_
  
  - [ ] 142.5 Deprecate MySQL
    - Archive MySQL data, decommission MySQL
    - _Requirements: Database_Migration_

- [ ] 143. Final checkpoint - Complete platform
  - Ensure all 46 property tests pass, all integration tests pass, all features work end-to-end, ask the user if questions arise.

## Notes

### Task Execution Guidelines

- Tasks marked with `*` are optional property-based tests and unit tests
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at phase boundaries
- Property tests should run with minimum 100 iterations
- All external service integrations should use sandbox/test environments during development

### Technology Stack Summary

All technologies use open-source licenses (MIT, Apache 2.0, BSD):
- **Backend**: Laravel 11 (MIT), PHP 8.2+ (PHP License)
- **Frontend**: Vue 3 (MIT), PrimeVue (MIT), Chart.js (MIT)
- **Database**: MySQL 8.0 (GPL) → PostgreSQL 15+ (PostgreSQL License)
- **Cache/Queue**: Redis 7 (BSD), Laravel Horizon (MIT)
- **Email**: GrapesJS (BSD), SendGrid SDK (MIT), AWS SES SDK (Apache 2.0), Mailgun SDK (MIT)
- **SMS**: Twilio SDK (MIT), Plivo SDK (MIT), MessageBird SDK (BSD)
- **Testing**: Eris (MIT) for property-based testing
- **HTTP Client**: Guzzle (MIT)
- **GraphQL**: Lighthouse (MIT)

### Estimated Timeline

- **Phase 1 (Email Marketing)**: 3-4 weeks
- **Phase 2 (Landing Pages & Forms)**: 3-4 weeks
- **Phase 3 (Marketing Automation)**: 4-5 weeks
- **Phase 4 (CRM Foundation)**: 3-4 weeks
- **Phase 5 (Paid Advertising)**: 2-3 weeks
- **Phase 6 (SEO Tools)**: 3-4 weeks
- **Phase 7 (SMS Marketing)**: 2-3 weeks
- **Phase 8 (Advanced Analytics)**: 4-5 weeks
- **Phase 9 (API & Integration)**: 3-4 weeks
- **Phase 10 (Enterprise Features)**: 2-3 weeks
- **Phase 11 (White-Label)**: 2-3 weeks
- **Final Integration & Testing**: 2-3 weeks

**Total Estimated Timeline**: 33-45 weeks (8-11 months)

### Success Criteria

- All 46 correctness properties pass with 100+ iterations
- All integration tests pass
- API response times < 200ms (95th percentile)
- Page load times < 2 seconds (95th percentile)
- 99.9% uptime
- All features integrated with existing codebase
- Zero breaking changes to existing features
- Complete API documentation
- All open-source license requirements met

