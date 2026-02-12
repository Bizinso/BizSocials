# Task 2.1: Core API Infrastructure - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.1 Core API Infrastructure
- **Phase**: 2 - Services & API Layer

---

## 1. Overview

This task establishes the foundational infrastructure for the API layer including base classes, response helpers, error handling, and authentication setup.

### Components to Implement
1. **Base Service Class** - Abstract service with common utilities
2. **API Response Traits** - Standardized JSON responses
3. **Exception Handler** - Custom API error responses
4. **Base Controller** - API controller with common methods
5. **Base Data Classes** - Spatie Data base configurations
6. **API Authentication** - Sanctum configuration
7. **API Versioning** - Route structure for v1
8. **Rate Limiting** - Throttle configuration

---

## 2. Directory Structure

```
app/
├── Services/
│   └── BaseService.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── Controller.php (Base API Controller)
│   ├── Middleware/
│   │   └── ApiVersion.php
│   └── Resources/
│       └── ApiResource.php (Base Resource)
├── Data/
│   ├── BaseData.php
│   └── Shared/
│       ├── PaginationData.php
│       ├── MetaData.php
│       └── ErrorData.php
├── Traits/
│   └── ApiResponse.php
├── Exceptions/
│   ├── ApiException.php
│   ├── ValidationException.php
│   └── Handler.php (modify existing)
routes/
├── api.php (reorganize)
└── api/
    └── v1.php
```

---

## 3. Implementation Details

### 3.1 Base Service Class
**File**: `app/Services/BaseService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Log service activity.
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('service')->{$level}(
            sprintf('[%s] %s', static::class, $message),
            $context
        );
    }

    /**
     * Handle and log service exceptions.
     */
    protected function handleException(Throwable $e, string $context = ''): never
    {
        $this->log($context ?: $e->getMessage(), [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 'error');

        throw $e;
    }
}
```

### 3.2 API Response Trait
**File**: `app/Traits/ApiResponse.php`

```php
<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a success response.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a created response.
     */
    protected function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a no content response.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error response.
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return a paginated response.
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
```

### 3.3 Base API Controller
**File**: `app/Http/Controllers/Api/V1/Controller.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Traits\ApiResponse;

abstract class Controller extends BaseController
{
    use ApiResponse;
}
```

### 3.4 API Exceptions
**File**: `app/Exceptions/ApiException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected array $errors = [];

    public function __construct(
        string $message = 'An error occurred',
        int $code = 400,
        array $errors = []
    ) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->getCode());
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### 3.5 Spatie Data Base Classes
**File**: `app/Data/Shared/PaginationData.php`

```php
<?php

declare(strict_types=1);

namespace App\Data\Shared;

use Spatie\LaravelData\Data;

final class PaginationData extends Data
{
    public function __construct(
        public int $current_page,
        public int $last_page,
        public int $per_page,
        public int $total,
        public ?int $from,
        public ?int $to,
    ) {}

    public static function fromPaginator(\Illuminate\Pagination\LengthAwarePaginator $paginator): self
    {
        return new self(
            current_page: $paginator->currentPage(),
            last_page: $paginator->lastPage(),
            per_page: $paginator->perPage(),
            total: $paginator->total(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem(),
        );
    }
}
```

**File**: `app/Data/Shared/ErrorData.php`

```php
<?php

declare(strict_types=1);

namespace App\Data\Shared;

use Spatie\LaravelData\Data;

final class ErrorData extends Data
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public ?string $code = null,
    ) {}
}
```

### 3.6 API Routes Structure
**File**: `routes/api/v1.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    // Auth routes will be added in Task 2.2
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // User routes
    Route::prefix('user')->group(function () {
        // User routes will be added in Task 2.2
    });

    // Tenant routes
    Route::prefix('tenants')->group(function () {
        // Tenant routes will be added in Task 2.3
    });

    // Workspace routes
    Route::prefix('workspaces')->group(function () {
        // Workspace routes will be added in Task 2.3
    });

    // Social account routes
    Route::prefix('social-accounts')->group(function () {
        // Social account routes will be added in Task 2.4
    });

    // Content routes
    Route::prefix('posts')->group(function () {
        // Post routes will be added in Task 2.5
    });

    // Inbox routes
    Route::prefix('inbox')->group(function () {
        // Inbox routes will be added in Task 2.6
    });

    // Billing routes
    Route::prefix('billing')->group(function () {
        // Billing routes will be added in Task 2.7
    });

    // Support routes
    Route::prefix('support')->group(function () {
        // Support routes will be added in Task 2.9
    });
});

// Public Knowledge Base routes
Route::prefix('kb')->group(function () {
    // KB routes will be added in Task 2.8
});

// Public Feedback routes
Route::prefix('feedback')->group(function () {
    // Feedback routes will be added in Task 2.10
});

// Admin routes (require admin authentication)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin routes will be added in Task 2.11
});
```

**File**: `routes/api.php` (updated)

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API Version 1
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// Default redirect to v1
Route::get('/', fn () => response()->json([
    'message' => 'BizSocials API',
    'version' => 'v1',
    'docs' => url('/docs/api'),
]));
```

### 3.7 Sanctum Configuration
**File**: `config/sanctum.php` updates

- Configure token expiration
- Set up token abilities
- Configure domains for SPA authentication

### 3.8 Rate Limiting
**File**: `app/Providers/AppServiceProvider.php` (add to boot method)

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// In boot() method:
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
});
```

---

## 4. Test Requirements

### Unit Tests
- `tests/Unit/Traits/ApiResponseTest.php` - Test all response methods
- `tests/Unit/Data/Shared/PaginationDataTest.php` - Test pagination data
- `tests/Unit/Data/Shared/ErrorDataTest.php` - Test error data

### Feature Tests
- `tests/Feature/Api/HealthCheckTest.php` - Test health endpoint
- `tests/Feature/Api/RateLimitingTest.php` - Test rate limiting

---

## 5. Implementation Checklist

- [ ] Create app/Services/BaseService.php
- [ ] Create app/Traits/ApiResponse.php
- [ ] Create app/Http/Controllers/Api/V1/Controller.php
- [ ] Create app/Exceptions/ApiException.php
- [ ] Create app/Data/Shared/PaginationData.php
- [ ] Create app/Data/Shared/ErrorData.php
- [ ] Create routes/api/v1.php
- [ ] Update routes/api.php
- [ ] Configure rate limiting in AppServiceProvider
- [ ] Update exception handler for API responses
- [ ] Create unit tests
- [ ] Create feature tests
- [ ] All tests pass
