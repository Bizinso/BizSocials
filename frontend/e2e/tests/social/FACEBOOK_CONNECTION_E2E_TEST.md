# Facebook Connection E2E Test Documentation

## Overview

This document describes the E2E test suite for Facebook OAuth connection flow implemented in `facebook-connection.spec.ts`.

**Task**: 14.8 Write E2E test for Facebook connection  
**Requirements**: 14.2  
**Status**: ✅ Complete

## Test Coverage

The test suite covers the complete Facebook OAuth connection workflow:

### 1. UI Elements Verification
- ✅ Connect Account button visibility
- ✅ Platform selection dialog display
- ✅ Facebook platform option availability

### 2. OAuth Flow Initiation
- ✅ OAuth flow starts when Facebook is selected
- ✅ Authorization URL generation
- ✅ Popup/redirect handling for OAuth

### 3. Account Connection
- ✅ Facebook account connection via test data helper
- ✅ Account details display after connection
- ✅ Account card rendering with platform information

### 4. Account Disconnection
- ✅ Disconnect button functionality
- ✅ Confirmation dialog handling
- ✅ Account removal verification

### 5. Error Handling
- ✅ OAuth error handling (access denied, cancelled)
- ✅ Error message display
- ✅ Graceful fallback to social accounts page

### 6. Edge Cases
- ✅ Duplicate account prevention
- ✅ Multiple account management

## Test Structure

### Setup
```typescript
test.beforeAll(async () => {
  // Initialize API helper for test data
  // Get workspace ID for testing
})

test.beforeEach(async ({ page }) => {
  // Login as owner user
  // Navigate to dashboard
})
```

### Cleanup
```typescript
test.afterAll(async () => {
  // Clean up test data from workspace
})
```

## Test Scenarios

### Scenario 1: Display Connect Button
**Given** user is on social accounts page  
**When** page loads  
**Then** "Connect Account" button should be visible

### Scenario 2: Open Platform Selection
**Given** user is on social accounts page  
**When** user clicks "Connect Account"  
**Then** platform selection dialog should open  
**And** Facebook option should be visible

### Scenario 3: Initiate OAuth Flow
**Given** platform selection dialog is open  
**When** user selects Facebook  
**Then** OAuth authorization flow should initiate  
**And** either popup opens or page redirects to OAuth

### Scenario 4: Connect Account
**Given** user has valid OAuth credentials  
**When** account connection is completed  
**Then** Facebook account should appear in account list  
**And** account details should be displayed

### Scenario 5: Disconnect Account
**Given** Facebook account is connected  
**When** user clicks disconnect button  
**And** confirms disconnection  
**Then** account should be removed from list

### Scenario 6: Handle OAuth Errors
**Given** OAuth flow encounters an error  
**When** user is redirected to callback with error  
**Then** error message should be displayed  
**And** user should be able to retry

### Scenario 7: Prevent Duplicates
**Given** Facebook account is already connected  
**When** user tries to connect same account again  
**Then** system should prevent duplicate or update existing

## OAuth Flow Details

### Real OAuth Flow (Production)
1. User clicks "Connect Account" → "Facebook"
2. Frontend calls `GET /api/v1/oauth/facebook/authorize`
3. Backend returns authorization URL with state
4. Frontend opens Facebook OAuth in popup/new tab
5. User authorizes on Facebook
6. Facebook redirects to `GET /api/v1/oauth/facebook/callback?code=...&state=...`
7. Backend redirects to frontend callback: `/app/oauth/callback?platform=facebook&code=...&state=...`
8. Frontend calls `POST /api/v1/oauth/facebook/exchange` with code and state
9. Backend exchanges code for tokens, returns session_key and available pages
10. Frontend calls `POST /api/v1/oauth/facebook/connect` with session_key and selected page
11. Backend creates social account record
12. Frontend displays connected account

### Test OAuth Flow (E2E)
For E2E testing, we use the test data helper to simulate account creation:
```typescript
await testData.createSocialAccount(workspaceId, 'facebook', 'Test Facebook Page')
```

This bypasses the OAuth flow and directly creates a test account in the database, allowing us to test the UI behavior without requiring real Facebook credentials.

## Running the Tests

### Prerequisites
1. Backend server running on `http://localhost:8080`
2. Frontend dev server running on `http://localhost:3000`
3. Test database seeded with test users

### Run All Facebook Connection Tests
```bash
cd frontend
npm run test:e2e -- facebook-connection.spec.ts --project=app
```

### Run Specific Test
```bash
cd frontend
npm run test:e2e -- facebook-connection.spec.ts --project=app -g "should connect Facebook account"
```

### Run in Headed Mode (with browser visible)
```bash
cd frontend
npm run test:e2e -- facebook-connection.spec.ts --project=app --headed
```

### Run with Debug
```bash
cd frontend
npm run test:e2e -- facebook-connection.spec.ts --project=app --debug
```

## Test Data Management

### Test User
- Email: `john.owner@acme.example.com`
- Password: `User@1234`
- Role: Owner

### Test Data Helper Methods Used
```typescript
// Create social account
await testData.createSocialAccount(workspaceId, 'facebook', 'Account Name')

// Cleanup workspace data
await testData.cleanupWorkspace(workspaceId)
```

## Page Objects Used

### SocialAccountsPage
```typescript
- goto(workspaceId: string): Navigate to social accounts page
- expectLoaded(): Wait for page to load
- clickConnect(): Click connect account button
- getAccountCards(): Get all account card elements
- connectButton: Locator for connect button
```

### LoginPage
```typescript
- goto(): Navigate to login page
- login(email: string, password: string): Perform login
```

## Assertions

The tests verify:
- ✅ UI elements are visible and interactive
- ✅ OAuth flow initiates correctly
- ✅ Accounts are created and displayed
- ✅ Account details are rendered properly
- ✅ Disconnection removes accounts
- ✅ Errors are handled gracefully
- ✅ Duplicate prevention works

## Known Limitations

1. **Real OAuth Testing**: These tests use test data helpers instead of real OAuth flow. To test real OAuth:
   - Requires Facebook test app credentials
   - Requires handling OAuth popup/redirect in Playwright
   - Requires Facebook test user accounts

2. **Rate Limiting**: Tests run serially to avoid rate limiting issues

3. **Cleanup**: Tests clean up after themselves, but manual cleanup may be needed if tests fail

## Future Enhancements

1. **Mock OAuth Server**: Implement a mock OAuth server for more realistic testing without external dependencies
2. **Visual Regression**: Add screenshot comparison for account cards
3. **Performance Testing**: Measure OAuth flow completion time
4. **Accessibility Testing**: Verify ARIA labels and keyboard navigation
5. **Multi-Account Testing**: Test connecting multiple Facebook accounts/pages

## Related Files

- Test: `frontend/e2e/tests/social/facebook-connection.spec.ts`
- Page Object: `frontend/e2e/pages/SocialAccountsPage.ts`
- API Helper: `frontend/e2e/helpers/api.helper.ts`
- Test Data Helper: `frontend/e2e/helpers/test-data.helper.ts`
- Backend Controller: `backend/app/Http/Controllers/Api/V1/Social/OAuthController.php`
- Backend Service: `backend/app/Services/Social/OAuthService.php`

## Validation

✅ Test file created with comprehensive coverage  
✅ All test scenarios implemented  
✅ Error handling included  
✅ Cleanup logic implemented  
✅ Documentation complete  
✅ TypeScript compilation successful  
✅ No linting errors

## Requirements Traceability

**Requirement 14.2**: End-to-End Testing
- THE Test_Infrastructure SHALL include E2E_Tests for social account connection flow

**Coverage**:
- ✅ Complete OAuth flow in browser (simulated via test data)
- ✅ Account connection workflow
- ✅ Account disconnection workflow
- ✅ Error handling scenarios
- ✅ UI element verification
- ✅ Data persistence validation

## Conclusion

The Facebook connection E2E test suite provides comprehensive coverage of the OAuth connection workflow, including happy path, error scenarios, and edge cases. The tests use the test data helper for reliable, fast execution without external dependencies.
