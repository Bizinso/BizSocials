# Task 2.9: Support Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.9 Support Services & API
- **Dependencies**: Task 2.1, Task 2.3, Task 1.11 (Support Migrations)

---

## 1. Overview

This task implements support ticket management services for customer support. It covers ticket creation, comments, attachments, status management, and admin operations.

### Components to Implement
1. **SupportTicketService** - Ticket CRUD and management
2. **SupportCommentService** - Comment management
3. **SupportCategoryService** - Category management (admin)
4. **Controllers** - User and admin API endpoints
5. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 SupportTicketService
**File**: `app/Services/Support/SupportTicketService.php`

```php
final class SupportTicketService extends BaseService
{
    // User methods
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator;
    public function create(User $user, Tenant $tenant, CreateTicketData $data): SupportTicket;
    public function get(string $id): SupportTicket;
    public function getForUser(User $user, string $id): SupportTicket;
    public function update(SupportTicket $ticket, UpdateTicketData $data): SupportTicket;
    public function close(SupportTicket $ticket): SupportTicket;
    public function reopen(SupportTicket $ticket): SupportTicket;

    // Admin methods
    public function listAll(array $filters = []): LengthAwarePaginator;
    public function assign(SupportTicket $ticket, SuperAdminUser $agent): SupportTicket;
    public function unassign(SupportTicket $ticket): SupportTicket;
    public function updateStatus(SupportTicket $ticket, SupportTicketStatus $status): SupportTicket;
    public function updatePriority(SupportTicket $ticket, SupportTicketPriority $priority): SupportTicket;
    public function getStats(): array;
}
```

### 2.2 SupportCommentService
**File**: `app/Services/Support/SupportCommentService.php`

```php
final class SupportCommentService extends BaseService
{
    public function listForTicket(SupportTicket $ticket): Collection;
    public function addUserComment(SupportTicket $ticket, User $user, AddCommentData $data): SupportTicketComment;
    public function addAgentComment(SupportTicket $ticket, SuperAdminUser $agent, AddCommentData $data): SupportTicketComment;
    public function addInternalNote(SupportTicket $ticket, SuperAdminUser $agent, AddCommentData $data): SupportTicketComment;
}
```

### 2.3 SupportCategoryService
**File**: `app/Services/Support/SupportCategoryService.php`

```php
final class SupportCategoryService extends BaseService
{
    public function listActive(): Collection;
    public function list(): Collection;
    public function create(CreateCategoryData $data): SupportCategory;
    public function update(SupportCategory $category, UpdateCategoryData $data): SupportCategory;
    public function delete(SupportCategory $category): void;
}
```

---

## 3. Data Classes

### 3.1 Support Data
**Directory**: `app/Data/Support/`

```php
// SupportTicketData.php
final class SupportTicketData extends Data
{
    public function __construct(
        public string $id,
        public string $ticket_number,
        public string $subject,
        public string $description,
        public string $status,
        public string $priority,
        public string $type,
        public string $channel,
        public ?string $category_id,
        public ?string $category_name,
        public string $user_id,
        public string $user_name,
        public string $user_email,
        public ?string $tenant_id,
        public ?string $tenant_name,
        public ?string $assigned_to_id,
        public ?string $assigned_to_name,
        public int $comment_count,
        public ?string $first_response_at,
        public ?string $resolved_at,
        public ?string $closed_at,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(SupportTicket $ticket): self;
}

// SupportTicketSummaryData.php
final class SupportTicketSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $ticket_number,
        public string $subject,
        public string $status,
        public string $priority,
        public ?string $assigned_to_name,
        public int $comment_count,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(SupportTicket $ticket): self;
}

// CreateTicketData.php
final class CreateTicketData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public string $subject,
        #[Required]
        public string $description,
        public SupportTicketType $type = SupportTicketType::QUESTION,
        public SupportTicketPriority $priority = SupportTicketPriority::MEDIUM,
        public ?string $category_id = null,
    ) {}
}

// UpdateTicketData.php
final class UpdateTicketData extends Data
{
    public function __construct(
        public ?string $subject = null,
        public ?string $description = null,
    ) {}
}

// SupportCommentData.php
final class SupportCommentData extends Data
{
    public function __construct(
        public string $id,
        public string $ticket_id,
        public string $comment_type,
        public string $content,
        public bool $is_internal,
        public string $author_type,
        public string $author_id,
        public string $author_name,
        public ?string $author_email,
        public string $created_at,
    ) {}

    public static function fromModel(SupportTicketComment $comment): self;
}

// AddCommentData.php
final class AddCommentData extends Data
{
    public function __construct(
        #[Required]
        public string $content,
        public bool $is_internal = false,
    ) {}
}

// SupportCategoryData.php
final class SupportCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public bool $is_active,
        public int $sort_order,
        public int $ticket_count,
    ) {}

    public static function fromModel(SupportCategory $category): self;
}

// SupportStatsData.php
final class SupportStatsData extends Data
{
    public function __construct(
        public int $total_tickets,
        public int $open_tickets,
        public int $pending_tickets,
        public int $resolved_tickets,
        public int $closed_tickets,
        public int $unassigned_tickets,
        public array $by_priority,
        public array $by_type,
    ) {}
}
```

---

## 4. Controllers

### 4.1 User Controllers

**SupportTicketController** - `app/Http/Controllers/Api/V1/Support/SupportTicketController.php`
- `GET /support/tickets` - List user's tickets
- `POST /support/tickets` - Create ticket
- `GET /support/tickets/{id}` - Get ticket
- `PUT /support/tickets/{id}` - Update ticket (limited)
- `POST /support/tickets/{id}/close` - Close ticket
- `POST /support/tickets/{id}/reopen` - Reopen ticket

**SupportCommentController** - `app/Http/Controllers/Api/V1/Support/SupportCommentController.php`
- `GET /support/tickets/{id}/comments` - List comments
- `POST /support/tickets/{id}/comments` - Add comment

**SupportCategoryController** - `app/Http/Controllers/Api/V1/Support/SupportCategoryController.php`
- `GET /support/categories` - List active categories (for ticket creation form)

### 4.2 Admin Controllers

**AdminSupportTicketController** - `app/Http/Controllers/Api/V1/Admin/Support/AdminSupportTicketController.php`
- `GET /admin/support/tickets` - List all tickets
- `GET /admin/support/tickets/{id}` - Get ticket
- `POST /admin/support/tickets/{id}/assign` - Assign to agent
- `POST /admin/support/tickets/{id}/unassign` - Unassign
- `PUT /admin/support/tickets/{id}/status` - Update status
- `PUT /admin/support/tickets/{id}/priority` - Update priority
- `GET /admin/support/stats` - Get statistics

**AdminSupportCommentController** - `app/Http/Controllers/Api/V1/Admin/Support/AdminSupportCommentController.php`
- `GET /admin/support/tickets/{id}/comments` - List all comments (including internal)
- `POST /admin/support/tickets/{id}/comments` - Add agent comment
- `POST /admin/support/tickets/{id}/notes` - Add internal note

**AdminSupportCategoryController** - `app/Http/Controllers/Api/V1/Admin/Support/AdminSupportCategoryController.php`
- `GET /admin/support/categories` - List all categories
- `POST /admin/support/categories` - Create category
- `PUT /admin/support/categories/{id}` - Update category
- `DELETE /admin/support/categories/{id}` - Delete category

---

## 5. Routes

```php
// User support routes
Route::middleware('auth:sanctum')->prefix('support')->group(function () {
    Route::get('/categories', [SupportCategoryController::class, 'index']);

    Route::get('/tickets', [SupportTicketController::class, 'index']);
    Route::post('/tickets', [SupportTicketController::class, 'store']);
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show']);
    Route::put('/tickets/{ticket}', [SupportTicketController::class, 'update']);
    Route::post('/tickets/{ticket}/close', [SupportTicketController::class, 'close']);
    Route::post('/tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen']);

    Route::get('/tickets/{ticket}/comments', [SupportCommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [SupportCommentController::class, 'store']);
});

// Admin support routes
Route::prefix('admin/support')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/stats', [AdminSupportTicketController::class, 'stats']);

    Route::get('/tickets', [AdminSupportTicketController::class, 'index']);
    Route::get('/tickets/{ticket}', [AdminSupportTicketController::class, 'show']);
    Route::post('/tickets/{ticket}/assign', [AdminSupportTicketController::class, 'assign']);
    Route::post('/tickets/{ticket}/unassign', [AdminSupportTicketController::class, 'unassign']);
    Route::put('/tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus']);
    Route::put('/tickets/{ticket}/priority', [AdminSupportTicketController::class, 'updatePriority']);

    Route::get('/tickets/{ticket}/comments', [AdminSupportCommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [AdminSupportCommentController::class, 'store']);
    Route::post('/tickets/{ticket}/notes', [AdminSupportCommentController::class, 'storeNote']);

    Route::apiResource('categories', AdminSupportCategoryController::class)->except(['show']);
});
```

---

## 6. Test Requirements

### Feature Tests
- `tests/Feature/Api/Support/SupportTicketTest.php`
- `tests/Feature/Api/Support/SupportCommentTest.php`
- `tests/Feature/Api/Admin/Support/AdminSupportTicketTest.php`
- `tests/Feature/Api/Admin/Support/AdminSupportCommentTest.php`
- `tests/Feature/Api/Admin/Support/AdminSupportCategoryTest.php`

### Unit Tests
- `tests/Unit/Services/Support/SupportTicketServiceTest.php`
- `tests/Unit/Services/Support/SupportCommentServiceTest.php`
- `tests/Unit/Services/Support/SupportCategoryServiceTest.php`

---

## 7. Implementation Checklist

- [ ] Create SupportTicketService
- [ ] Create SupportCommentService
- [ ] Create SupportCategoryService
- [ ] Create Support Data classes
- [ ] Create user controllers
- [ ] Create admin controllers
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 8. Business Rules

### Ticket Rules
- Users can only view their own tickets
- Tickets auto-generate unique ticket numbers
- Only open/pending tickets can have comments added
- Only the ticket creator can close/reopen

### Comment Rules
- Users see only public comments
- Admins see all comments including internal notes
- Adding comment auto-updates ticket status

### Status Transitions
- Open → Pending, In Progress, Resolved, Closed
- Pending → In Progress, Resolved, Closed
- In Progress → Pending, Resolved, Closed
- Resolved → Closed, Reopened
- Closed → Reopened
