# Task 2.2: Auth Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.2 Auth Services & API
- **Dependencies**: Task 2.1 (Core API Infrastructure), Task 1.3 (User & Auth Migrations)

---

## 1. Overview

This task implements authentication and user management services and API endpoints. It covers login, registration, password management, and user profile operations.

### Components to Implement
1. **AuthService** - Authentication logic (login, logout, register)
2. **PasswordService** - Password reset workflow
3. **UserService** - User profile management
4. **Auth Controllers** - API endpoints
5. **Spatie Data Classes** - Request/response DTOs
6. **Form Requests** - Validation
7. **Feature Tests** - Comprehensive test coverage

---

## 2. Services

### 2.1 AuthService
**File**: `app/Services/Auth/AuthService.php`

```php
final class AuthService extends BaseService
{
    public function login(LoginData $data): array; // Returns user + token
    public function register(RegisterData $data): User;
    public function logout(User $user): void;
    public function refreshToken(User $user): string;
    public function verifyEmail(User $user, string $hash): bool;
    public function resendVerification(User $user): void;
}
```

### 2.2 PasswordService
**File**: `app/Services/Auth/PasswordService.php`

```php
final class PasswordService extends BaseService
{
    public function sendResetLink(string $email): void;
    public function reset(ResetPasswordData $data): void;
    public function change(User $user, ChangePasswordData $data): void;
}
```

### 2.3 UserService
**File**: `app/Services/User/UserService.php`

```php
final class UserService extends BaseService
{
    public function getProfile(User $user): User;
    public function updateProfile(User $user, UpdateProfileData $data): User;
    public function updateSettings(User $user, array $settings): User;
    public function deleteAccount(User $user, string $password): void;
}
```

---

## 3. Data Classes (Spatie Data)

### 3.1 Auth Data
**Directory**: `app/Data/Auth/`

```php
// LoginData.php
final class LoginData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $password,
        public bool $remember = false,
    ) {}
}

// RegisterData.php
final class RegisterData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        #[Required, Email]
        public string $email,
        #[Required, Min(8)]
        public string $password,
        #[Required]
        public string $password_confirmation,
        public ?string $tenant_id = null,
    ) {}
}

// ResetPasswordData.php
final class ResetPasswordData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $token,
        #[Required, Min(8)]
        public string $password,
        #[Required]
        public string $password_confirmation,
    ) {}
}

// ChangePasswordData.php
final class ChangePasswordData extends Data
{
    public function __construct(
        #[Required]
        public string $current_password,
        #[Required, Min(8)]
        public string $password,
        #[Required]
        public string $password_confirmation,
    ) {}
}

// AuthResponseData.php
final class AuthResponseData extends Data
{
    public function __construct(
        public UserData $user,
        public string $token,
        public string $token_type = 'Bearer',
        public ?int $expires_in = null,
    ) {}
}
```

### 3.2 User Data
**Directory**: `app/Data/User/`

```php
// UserData.php
final class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $avatar_url,
        public ?string $timezone,
        public string $status,
        public ?string $email_verified_at,
        public string $created_at,
    ) {}

    public static function fromModel(User $user): self;
}

// UpdateProfileData.php
final class UpdateProfileData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $timezone = null,
        public ?string $phone = null,
        public ?string $job_title = null,
    ) {}
}
```

---

## 4. Controllers

### 4.1 AuthController
**File**: `app/Http/Controllers/Api/V1/Auth/AuthController.php`

Endpoints:
- `POST /auth/login` - Login with email/password
- `POST /auth/register` - Register new user
- `POST /auth/logout` - Logout (revoke token)
- `POST /auth/refresh` - Refresh token
- `POST /auth/verify-email/{id}/{hash}` - Verify email
- `POST /auth/resend-verification` - Resend verification email

### 4.2 PasswordController
**File**: `app/Http/Controllers/Api/V1/Auth/PasswordController.php`

Endpoints:
- `POST /auth/forgot-password` - Request password reset
- `POST /auth/reset-password` - Reset password with token
- `POST /auth/change-password` - Change password (authenticated)

### 4.3 UserController
**File**: `app/Http/Controllers/Api/V1/User/UserController.php`

Endpoints:
- `GET /user` - Get current user profile
- `PUT /user` - Update profile
- `PUT /user/settings` - Update settings
- `DELETE /user` - Delete account

---

## 5. Routes

**File**: `routes/api/v1.php` (update auth section)

```php
// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])->name('verification.verify');
});

// Protected auth routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('/change-password', [PasswordController::class, 'changePassword']);
});

// User routes
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/', [UserController::class, 'show']);
    Route::put('/', [UserController::class, 'update']);
    Route::put('/settings', [UserController::class, 'updateSettings']);
    Route::delete('/', [UserController::class, 'destroy']);
});
```

---

## 6. Form Requests

**Directory**: `app/Http/Requests/Auth/`

- `LoginRequest.php`
- `RegisterRequest.php`
- `ForgotPasswordRequest.php`
- `ResetPasswordRequest.php`
- `ChangePasswordRequest.php`

**Directory**: `app/Http/Requests/User/`

- `UpdateProfileRequest.php`
- `UpdateSettingsRequest.php`
- `DeleteAccountRequest.php`

---

## 7. Test Requirements

### Feature Tests
**Directory**: `tests/Feature/Api/Auth/`

- `LoginTest.php` - Login flow tests
- `RegisterTest.php` - Registration flow tests
- `LogoutTest.php` - Logout tests
- `PasswordResetTest.php` - Password reset flow
- `EmailVerificationTest.php` - Email verification flow

**Directory**: `tests/Feature/Api/User/`

- `ProfileTest.php` - Profile CRUD tests
- `SettingsTest.php` - Settings tests
- `DeleteAccountTest.php` - Account deletion tests

### Unit Tests
**Directory**: `tests/Unit/Services/Auth/`

- `AuthServiceTest.php`
- `PasswordServiceTest.php`

**Directory**: `tests/Unit/Services/User/`

- `UserServiceTest.php`

---

## 8. Implementation Checklist

- [ ] Create AuthService
- [ ] Create PasswordService
- [ ] Create UserService
- [ ] Create Auth Data classes (LoginData, RegisterData, etc.)
- [ ] Create User Data classes (UserData, UpdateProfileData)
- [ ] Create AuthController
- [ ] Create PasswordController
- [ ] Create UserController
- [ ] Create Form Requests
- [ ] Update routes/api/v1.php with auth routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 9. Response Examples

### Login Success
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@example.com",
      "avatar_url": null,
      "timezone": "UTC",
      "status": "active",
      "email_verified_at": "2026-02-06T10:00:00Z",
      "created_at": "2026-02-06T10:00:00Z"
    },
    "token": "1|abc123...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

### Login Failure
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```
