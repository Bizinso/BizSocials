# Task 1.11: Support Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.11 Support Migrations
- **Dependencies**: Task 1.1 (Platform Admin), Task 1.2 (Tenant Management), Task 1.3 (User & Auth), Task 1.9 (Knowledge Base)

---

## 1. Overview

This task implements the Support Ticket system for handling customer support requests. Support tickets are submitted by tenants/users and managed by SuperAdmins (support staff).

### Entities to Implement
1. **SupportCategory** - Categories for organizing tickets
2. **SupportTicket** - Main ticket entity
3. **SupportTicketComment** - Comments/replies on tickets
4. **SupportTicketAttachment** - File attachments on tickets
5. **SupportTicketTag** - Tags for categorizing tickets
6. **SupportTicketTagAssignment** - Pivot table for ticket-tag M:N
7. **SupportTicketWatcher** - Users watching ticket updates
8. **SupportCannedResponse** - Pre-defined responses for support staff

---

## 2. Enums

### 2.1 SupportTicketStatus Enum
**File**: `app/Enums/Support/SupportTicketStatus.php`

```php
enum SupportTicketStatus: string
{
    case NEW = 'new';
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_CUSTOMER = 'waiting_customer';
    case WAITING_INTERNAL = 'waiting_internal';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    public function label(): string;
    public function isOpen(): bool;  // NEW, OPEN, IN_PROGRESS, REOPENED
    public function isPending(): bool;  // WAITING_CUSTOMER, WAITING_INTERNAL
    public function isClosed(): bool;  // RESOLVED, CLOSED
    public function canTransitionTo(SupportTicketStatus $status): bool;
}
```

### 2.2 SupportTicketPriority Enum
**File**: `app/Enums/Support/SupportTicketPriority.php`

```php
enum SupportTicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string;
    public function weight(): int;  // 1, 2, 3, 4
    public function color(): string;
    public function slaHours(): int;  // 72, 24, 8, 4
}
```

### 2.3 SupportTicketType Enum
**File**: `app/Enums/Support/SupportTicketType.php`

```php
enum SupportTicketType: string
{
    case QUESTION = 'question';
    case PROBLEM = 'problem';
    case FEATURE_REQUEST = 'feature_request';
    case BUG_REPORT = 'bug_report';
    case BILLING = 'billing';
    case ACCOUNT = 'account';
    case OTHER = 'other';

    public function label(): string;
    public function icon(): string;
}
```

### 2.4 SupportChannel Enum
**File**: `app/Enums/Support/SupportChannel.php`

```php
enum SupportChannel: string
{
    case WEB_FORM = 'web_form';
    case EMAIL = 'email';
    case IN_APP = 'in_app';
    case CHAT = 'chat';
    case API = 'api';

    public function label(): string;
}
```

### 2.5 SupportCommentType Enum
**File**: `app/Enums/Support/SupportCommentType.php`

```php
enum SupportCommentType: string
{
    case REPLY = 'reply';
    case NOTE = 'note';  // Internal note
    case STATUS_CHANGE = 'status_change';
    case ASSIGNMENT = 'assignment';
    case SYSTEM = 'system';

    public function label(): string;
    public function isPublic(): bool;  // REPLY only
    public function isInternal(): bool;  // NOTE, ASSIGNMENT
}
```

### 2.6 SupportAttachmentType Enum
**File**: `app/Enums/Support/SupportAttachmentType.php`

```php
enum SupportAttachmentType: string
{
    case IMAGE = 'image';
    case DOCUMENT = 'document';
    case VIDEO = 'video';
    case ARCHIVE = 'archive';
    case OTHER = 'other';

    public function label(): string;
    public function allowedExtensions(): array;
    public function maxSizeBytes(): int;
}
```

### 2.7 CannedResponseCategory Enum
**File**: `app/Enums/Support/CannedResponseCategory.php`

```php
enum CannedResponseCategory: string
{
    case GREETING = 'greeting';
    case BILLING = 'billing';
    case TECHNICAL = 'technical';
    case ACCOUNT = 'account';
    case FEATURE_REQUEST = 'feature_request';
    case BUG_REPORT = 'bug_report';
    case CLOSING = 'closing';
    case GENERAL = 'general';

    public function label(): string;
}
```

---

## 3. Migrations

### 3.1 Create Support Categories Table
**File**: `database/migrations/2026_02_06_1100001_create_support_categories_table.php`

```php
Schema::create('support_categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 100);
    $table->string('slug', 100)->unique();
    $table->text('description')->nullable();
    $table->string('color', 7)->default('#6B7280');
    $table->string('icon', 50)->nullable();
    $table->uuid('parent_id')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->integer('ticket_count')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('parent_id');
    $table->index('is_active');
    $table->index('sort_order');

    // Self-referencing foreign key
    $table->foreign('parent_id')
        ->references('id')
        ->on('support_categories')
        ->nullOnDelete();
});
```

### 3.2 Create Support Tickets Table
**File**: `database/migrations/2026_02_06_1100002_create_support_tickets_table.php`

```php
Schema::create('support_tickets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('ticket_number', 20)->unique();
    $table->uuid('tenant_id')->nullable();
    $table->uuid('user_id')->nullable();
    $table->string('requester_email', 255);
    $table->string('requester_name', 100);
    $table->uuid('category_id')->nullable();
    $table->string('subject', 255);
    $table->longText('description');
    $table->string('ticket_type', 20)->default('question');  // SupportTicketType
    $table->string('priority', 10)->default('medium');  // SupportTicketPriority
    $table->string('status', 20)->default('new');  // SupportTicketStatus
    $table->string('channel', 15)->default('web_form');  // SupportChannel
    $table->uuid('assigned_to')->nullable();
    $table->uuid('assigned_team_id')->nullable();
    $table->timestamp('first_response_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamp('closed_at')->nullable();
    $table->timestamp('last_activity_at')->nullable();
    $table->timestamp('sla_due_at')->nullable();
    $table->boolean('is_sla_breached')->default(false);
    $table->integer('comment_count')->default(0);
    $table->integer('attachment_count')->default(0);
    $table->json('custom_fields')->nullable();
    $table->string('browser_info', 500)->nullable();
    $table->string('page_url', 500)->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('ticket_number');
    $table->index('status');
    $table->index('priority');
    $table->index('ticket_type');
    $table->index('tenant_id');
    $table->index('assigned_to');
    $table->index('created_at');
    $table->index('sla_due_at');
    $table->index(['status', 'priority'], 'tickets_status_priority_idx');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('category_id')
        ->references('id')
        ->on('support_categories')
        ->nullOnDelete();

    $table->foreign('assigned_to')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.3 Create Support Ticket Comments Table
**File**: `database/migrations/2026_02_06_1100003_create_support_ticket_comments_table.php`

```php
Schema::create('support_ticket_comments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('ticket_id');
    $table->uuid('user_id')->nullable();
    $table->uuid('admin_id')->nullable();
    $table->string('author_name', 100)->nullable();
    $table->string('author_email', 255)->nullable();
    $table->longText('content');
    $table->string('comment_type', 20)->default('reply');  // SupportCommentType
    $table->boolean('is_internal')->default(false);
    $table->json('metadata')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('ticket_id');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('ticket_id')
        ->references('id')
        ->on('support_tickets')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('admin_id')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.4 Create Support Ticket Attachments Table
**File**: `database/migrations/2026_02_06_1100004_create_support_ticket_attachments_table.php`

```php
Schema::create('support_ticket_attachments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('ticket_id');
    $table->uuid('comment_id')->nullable();
    $table->string('filename', 255);
    $table->string('original_filename', 255);
    $table->string('file_path', 500);
    $table->string('mime_type', 100);
    $table->string('attachment_type', 20)->default('other');  // SupportAttachmentType
    $table->bigInteger('file_size');
    $table->uuid('uploaded_by')->nullable();
    $table->boolean('is_inline')->default(false);
    $table->timestamps();

    // Indexes
    $table->index('ticket_id');
    $table->index('comment_id');

    // Foreign keys
    $table->foreign('ticket_id')
        ->references('id')
        ->on('support_tickets')
        ->cascadeOnDelete();

    $table->foreign('comment_id')
        ->references('id')
        ->on('support_ticket_comments')
        ->nullOnDelete();

    $table->foreign('uploaded_by')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
});
```

### 3.5 Create Support Ticket Tags Table
**File**: `database/migrations/2026_02_06_1100005_create_support_ticket_tags_table.php`

```php
Schema::create('support_ticket_tags', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 50);
    $table->string('slug', 50)->unique();
    $table->string('color', 7)->default('#6B7280');
    $table->text('description')->nullable();
    $table->integer('usage_count')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('slug');
    $table->index('usage_count');
});
```

### 3.6 Create Support Ticket Tag Assignments Table
**File**: `database/migrations/2026_02_06_1100006_create_support_ticket_tag_assignments_table.php`

```php
Schema::create('support_ticket_tag_assignments', function (Blueprint $table) {
    $table->uuid('ticket_id');
    $table->uuid('tag_id');
    $table->timestamps();

    // Primary key
    $table->primary(['ticket_id', 'tag_id']);

    // Foreign keys
    $table->foreign('ticket_id')
        ->references('id')
        ->on('support_tickets')
        ->cascadeOnDelete();

    $table->foreign('tag_id')
        ->references('id')
        ->on('support_ticket_tags')
        ->cascadeOnDelete();
});
```

### 3.7 Create Support Ticket Watchers Table
**File**: `database/migrations/2026_02_06_1100007_create_support_ticket_watchers_table.php`

```php
Schema::create('support_ticket_watchers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('ticket_id');
    $table->uuid('user_id')->nullable();
    $table->uuid('admin_id')->nullable();
    $table->string('email', 255)->nullable();
    $table->boolean('notify_on_reply')->default(true);
    $table->boolean('notify_on_status_change')->default(true);
    $table->boolean('notify_on_assignment')->default(false);
    $table->timestamps();

    // Unique constraint
    $table->unique(['ticket_id', 'user_id'], 'watchers_ticket_user_unique');
    $table->unique(['ticket_id', 'admin_id'], 'watchers_ticket_admin_unique');

    // Foreign keys
    $table->foreign('ticket_id')
        ->references('id')
        ->on('support_tickets')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('admin_id')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.8 Create Support Canned Responses Table
**File**: `database/migrations/2026_02_06_1100008_create_support_canned_responses_table.php`

```php
Schema::create('support_canned_responses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('title', 100);
    $table->string('shortcut', 50)->nullable()->unique();
    $table->longText('content');
    $table->string('category', 20)->default('general');  // CannedResponseCategory
    $table->uuid('created_by');
    $table->boolean('is_shared')->default(true);
    $table->integer('usage_count')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('shortcut');
    $table->index('category');
    $table->index('created_by');

    // Foreign keys
    $table->foreign('created_by')
        ->references('id')
        ->on('super_admin_users')
        ->cascadeOnDelete();
});
```

---

## 4. Models

### 4.1 SupportCategory Model
**File**: `app/Models/Support/SupportCategory.php`

Key methods:
- Relationships: `parent()`, `children()`, `tickets()`
- Scopes: `active()`, `roots()`, `ordered()`, `withTicketCount()`
- Helpers: `isRoot()`, `hasChildren()`, `getFullPath()`, `incrementTicketCount()`, `decrementTicketCount()`

### 4.2 SupportTicket Model
**File**: `app/Models/Support/SupportTicket.php`

Key methods:
- Relationships: `tenant()`, `user()`, `category()`, `assignee()`, `comments()`, `attachments()`, `tags()`, `watchers()`
- Scopes: `newTickets()`, `open()`, `pending()`, `closed()`, `byStatus()`, `byPriority()`, `byType()`, `forTenant()`, `forUser()`, `assignedTo()`, `unassigned()`, `overdue()`, `search()`
- Helpers: `isNew()`, `isOpen()`, `isPending()`, `isClosed()`, `isOverdue()`, `assign()`, `unassign()`, `changeStatus()`, `resolve()`, `close()`, `reopen()`, `addComment()`, `calculateSlaDue()`, `checkSlaBreach()`, `getRequesterDisplay()`
- Boot: Auto-generate `ticket_number` with format `TKT-XXXXXX`

### 4.3 SupportTicketComment Model
**File**: `app/Models/Support/SupportTicketComment.php`

Key methods:
- Relationships: `ticket()`, `user()`, `admin()`, `attachments()`
- Scopes: `forTicket()`, `public()`, `internal()`, `replies()`, `notes()`, `system()`
- Helpers: `isPublic()`, `isInternal()`, `isReply()`, `isNote()`, `isSystem()`, `getAuthorName()`, `getAuthorEmail()`

### 4.4 SupportTicketAttachment Model
**File**: `app/Models/Support/SupportTicketAttachment.php`

Key methods:
- Relationships: `ticket()`, `comment()`, `uploader()`
- Scopes: `forTicket()`, `forComment()`, `images()`, `documents()`, `inline()`
- Helpers: `isImage()`, `isDocument()`, `isInline()`, `getUrl()`, `getHumanFileSize()`

### 4.5 SupportTicketTag Model
**File**: `app/Models/Support/SupportTicketTag.php`

Key methods:
- Relationships: `tickets()`
- Scopes: `popular()`, `ordered()`, `search()`
- Helpers: `incrementUsageCount()`, `decrementUsageCount()`

### 4.6 SupportTicketTagAssignment Model (Pivot)
**File**: `app/Models/Support/SupportTicketTagAssignment.php`

Key methods:
- Relationships: `ticket()`, `tag()`

### 4.7 SupportTicketWatcher Model
**File**: `app/Models/Support/SupportTicketWatcher.php`

Key methods:
- Relationships: `ticket()`, `user()`, `admin()`
- Scopes: `forTicket()`, `byUser()`, `byAdmin()`, `shouldNotifyOnReply()`, `shouldNotifyOnStatusChange()`
- Helpers: `isUser()`, `isAdmin()`, `getWatcherEmail()`, `shouldNotifyFor()`

### 4.8 SupportCannedResponse Model
**File**: `app/Models/Support/SupportCannedResponse.php`

Key methods:
- Relationships: `creator()`
- Scopes: `shared()`, `byCategory()`, `byCreator()`, `search()`, `popular()`
- Helpers: `isShared()`, `incrementUsageCount()`, `renderContent()`

---

## 5. Factories & Seeders

### Factories
- `SupportCategoryFactory` - with states for active/inactive, root/child
- `SupportTicketFactory` - with states for statuses, priorities, types, channels
- `SupportTicketCommentFactory` - with states for reply/note/system, internal
- `SupportTicketAttachmentFactory` - with states for image/document
- `SupportTicketTagFactory` - with states for popular
- `SupportTicketWatcherFactory` - with states for user/admin watchers
- `SupportCannedResponseFactory` - with states for categories, shared/personal

### Seeders
- `SupportCategorySeeder` - Create default categories
- `SupportTicketTagSeeder` - Create common tags
- `SupportCannedResponseSeeder` - Create default canned responses
- `SupportTicketSeeder` - Create sample tickets with comments
- `SupportSeeder` (Orchestrator)

---

## 6. Test Requirements

Create tests for:
- 7 enum tests
- 8 model tests

---

## 7. Implementation Checklist

- [ ] Create SupportTicketStatus enum
- [ ] Create SupportTicketPriority enum
- [ ] Create SupportTicketType enum
- [ ] Create SupportChannel enum
- [ ] Create SupportCommentType enum
- [ ] Create SupportAttachmentType enum
- [ ] Create CannedResponseCategory enum
- [ ] Create support_categories migration
- [ ] Create support_tickets migration
- [ ] Create support_ticket_comments migration
- [ ] Create support_ticket_attachments migration
- [ ] Create support_ticket_tags migration
- [ ] Create support_ticket_tag_assignments migration
- [ ] Create support_ticket_watchers migration
- [ ] Create support_canned_responses migration
- [ ] Create all 8 models
- [ ] Create all factories
- [ ] Create all seeders
- [ ] Create all unit tests
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 8. Notes

1. **Ticket Numbering**: Auto-generate unique ticket numbers with format `TKT-XXXXXX` using a boot method.

2. **SLA Tracking**: Calculate SLA due date based on priority. Track breaches with `is_sla_breached` flag.

3. **Assignment**: Tickets can be assigned to SuperAdminUsers (support staff). Support team assignment via `assigned_team_id` for future use.

4. **Internal Notes**: Comments with `is_internal=true` are only visible to support staff.

5. **Watchers**: Both users and admins can watch tickets for notifications. Notification preferences are configurable per watcher.

6. **Canned Responses**: Pre-defined responses with shortcuts for quick insertion. Can be shared or personal to creator.

7. **Soft Deletes**: Tickets use soft deletes to preserve history.

8. **Channel Tracking**: Track where tickets originate from (web form, email, in-app, etc.).
