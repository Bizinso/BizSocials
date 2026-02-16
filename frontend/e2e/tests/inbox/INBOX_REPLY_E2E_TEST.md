# Inbox Reply Flow E2E Test

## Overview

This E2E test validates the inbox message handling flow, specifically testing the ability to view messages and send replies through the unified inbox interface.

## Test Coverage

### Requirement 14.4: E2E Tests for Inbox Message Handling Flow

The test suite covers the following scenarios:

1. **View Inbox Messages**
   - Navigate to inbox page
   - Verify inbox page loads correctly
   - Verify inbox items are displayed

2. **Open Inbox Item Detail View**
   - Click on an inbox item
   - Navigate to detail view
   - Verify detail view elements are visible

3. **Send Reply to Inbox Message**
   - Navigate to inbox item detail
   - Type a reply message
   - Send the reply
   - Verify success notification
   - Verify reply appears in thread
   - Verify textarea is cleared after sending

4. **Display Existing Replies**
   - View inbox item detail
   - Verify replies section is visible
   - Verify replies count is displayed

5. **Validate Empty Reply Submission**
   - Attempt to send empty reply
   - Verify send button is disabled

6. **Navigate Back to Inbox List**
   - Click back button from detail view
   - Verify navigation to inbox list

## Test Data Setup

The test uses the test data seeding infrastructure to:
- Get an existing workspace from the authenticated owner user
- Seed 3 inbox items for testing using the Facebook platform
- Use the first seeded item for detailed testing

## Test Structure

```typescript
test.beforeAll(async ({ browser }) => {
  // Get workspace ID from authenticated session
  // Set up API helper
  // Seed test inbox items
})

test.describe('Inbox Reply Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Skip if no workspace or inbox item available
  })

  test('views inbox messages', async ({ page }) => { ... })
  test('opens inbox item detail view', async ({ page }) => { ... })
  test('sends a reply to inbox message', async ({ page }) => { ... })
  test('displays existing replies in thread', async ({ page }) => { ... })
  test('validates empty reply submission', async ({ page }) => { ... })
  test('navigates back to inbox list', async ({ page }) => { ... })
})
```

## Key Features

### Adaptive Testing
- Checks if reply functionality is available (comments vs mentions)
- Handles cases where replies are not supported
- Verifies appropriate messaging for non-replyable items

### Robust Selectors
- Uses flexible selectors to handle UI variations
- Combines multiple selector strategies for reliability
- Uses role-based selectors where possible

### Proper Waiting
- Uses explicit waits with timeouts
- Waits for page load states
- Waits for URL changes
- Waits for element visibility

### Error Handling
- Gracefully handles missing test data
- Skips tests when prerequisites are not met
- Uses try-catch for optional elements

## Page Object Integration

The test also updates the `InboxPage` page object with new methods:
- `clickFirstItem()` - Click the first inbox item
- `expectDetailView()` - Verify detail view is loaded
- `typeReply(message)` - Type a reply message
- `sendReply()` - Click send reply button
- `expectReplySent()` - Verify success notification
- `expectReplyInThread(message)` - Verify reply appears in thread
- `clickBackToInbox()` - Navigate back to inbox list

## Running the Tests

```bash
# Run all inbox tests
npm run test:e2e -- inbox/

# Run only reply tests
npm run test:e2e -- inbox-reply.spec.ts

# Run in headed mode (see browser)
npm run test:e2e:headed -- inbox-reply.spec.ts

# Run with UI mode
npm run test:e2e:ui -- inbox-reply.spec.ts
```

## Prerequisites

1. Backend API must be running
2. Test database must be seeded with owner user
3. Authentication state must be set up (owner.json)
4. Test data seeding endpoints must be available

## Validation

The test validates:
- ✅ Inbox page loads and displays messages
- ✅ Inbox item detail view opens correctly
- ✅ Reply form is functional
- ✅ Replies are sent successfully
- ✅ Replies appear in the thread
- ✅ Empty replies are prevented
- ✅ Navigation between list and detail works
- ✅ Existing replies are displayed
- ✅ Reply count is accurate

## Related Files

- Test: `frontend/e2e/tests/inbox/inbox-reply.spec.ts`
- Page Object: `frontend/e2e/pages/InboxPage.ts`
- View: `frontend/src/views/inbox/InboxDetailView.vue`
- Component: `frontend/src/components/inbox/InboxThread.vue`
- Component: `frontend/src/components/inbox/InboxReplyForm.vue`
- API: `frontend/src/api/inbox.ts`

## Notes

- The test handles both replyable items (comments) and non-replyable items (mentions)
- Reply messages include timestamps to ensure uniqueness
- The test uses the existing authentication state from owner.json
- Test data is seeded before tests run to ensure consistent state
