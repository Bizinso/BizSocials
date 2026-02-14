# Test Data Seeding for E2E Tests

This document explains how to use the test data seeding infrastructure for E2E tests.

## Overview

The test data seeding system provides API endpoints and helper functions to:
- Create test users with workspaces
- Seed test data (posts, inbox items, tickets, social accounts)
- Cleanup test data after tests
- Reset the test database

## Security

Test data endpoints are **only available in non-production environments**. They are protected by the `testing` middleware which blocks access in production.

## Backend API Endpoints

All endpoints are prefixed with `/api/v1/testing/`:

### Create Test User
```
POST /api/v1/testing/users
```

**Request Body:**
```json
{
  "email": "test@example.com",
  "password": "Test@1234",
  "name": "Test User",
  "tenant_id": "optional-tenant-id",
  "role": "owner",
  "with_workspace": true,
  "workspace_name": "Test Workspace"
}
```

### Create Test Posts
```
POST /api/v1/testing/posts
```

**Request Body:**
```json
{
  "workspace_id": "workspace-id",
  "user_id": "user-id",
  "count": 10,
  "status": "draft"
}
```

### Create Test Inbox Items
```
POST /api/v1/testing/inbox-items
```

**Request Body:**
```json
{
  "workspace_id": "workspace-id",
  "count": 10,
  "status": "unread",
  "platform": "facebook"
}
```

### Create Test Tickets
```
POST /api/v1/testing/tickets
```

**Request Body:**
```json
{
  "tenant_id": "tenant-id",
  "user_id": "user-id",
  "count": 5,
  "status": "open"
}
```

### Create Test Social Account
```
POST /api/v1/testing/social-accounts
```

**Request Body:**
```json
{
  "workspace_id": "workspace-id",
  "platform": "facebook",
  "account_name": "Test Facebook Account"
}
```

### Cleanup Test Data
```
POST /api/v1/testing/cleanup
```

**Request Body:**
```json
{
  "email_pattern": "e2e-test-%",
  "workspace_id": "optional-workspace-id",
  "user_id": "optional-user-id"
}
```

### Reset Test Database
```
POST /api/v1/testing/reset
```

**Warning:** This truncates test-related tables. Use with caution!

## Frontend Helpers

### ApiHelper Methods

The `ApiHelper` class includes methods for all test data endpoints:

```typescript
import { getApiHelper } from './helpers/api.helper'

const api = await getApiHelper('user@example.com', 'password')

// Create test user
await api.createTestUser({
  email: 'test@example.com',
  password: 'Test@1234',
  name: 'Test User',
  with_workspace: true,
})

// Create test posts
await api.createTestPosts({
  workspace_id: 'workspace-id',
  user_id: 'user-id',
  count: 10,
})

// Cleanup
await api.cleanupTestData({
  email_pattern: 'test@%',
})
```

### TestDataHelper

The `TestDataHelper` class provides convenient high-level methods:

```typescript
import { getApiHelper } from './helpers/api.helper'
import { createTestDataHelper } from './helpers/test-data.helper'

const api = await getApiHelper('user@example.com', 'password')
const testData = createTestDataHelper(api)

// Create user with workspace
const { user, workspace } = await testData.createUserWithWorkspace({
  email: 'test@example.com',
  password: 'Test@1234',
  name: 'Test User',
})

// Seed posts
await testData.seedPosts(workspace.id, user.id, 10, 'draft')

// Seed inbox items
await testData.seedInboxItems(workspace.id, 5, 'facebook')

// Cleanup
await testData.cleanup('test@%')
```

## Usage in Tests

### Basic Pattern

```typescript
import { test, expect } from '@playwright/test'
import { getApiHelper } from '../../helpers/api.helper'
import { createTestDataHelper } from '../../helpers/test-data.helper'
import { uniqueEmail } from '../../helpers/constants'

test('my test', async ({ page }) => {
  const email = uniqueEmail()
  const api = await getApiHelper('existing-user@example.com', 'password')
  const testData = createTestDataHelper(api)

  try {
    // Create test data
    const { user, workspace } = await testData.createUserWithWorkspace({
      email,
      password: 'Test@1234',
      name: 'Test User',
    })

    // Run your test...

  } finally {
    // Always cleanup in finally block
    await testData.cleanup(email.replace('@', '%'))
  }
})
```

### Seeding Data for Existing Workspaces

```typescript
test('test with existing workspace', async ({ page }) => {
  const api = await getApiHelper('john.owner@acme.example.com', 'User@1234')
  const testData = createTestDataHelper(api)

  const workspaces = await api.listWorkspaces()
  const workspace = workspaces[0]

  try {
    // Seed data
    await testData.seedPosts(workspace.id, 'user-id', 20, 'published')
    await testData.seedInboxItems(workspace.id, 10, 'twitter')

    // Run your test...

  } finally {
    // Cleanup workspace data
    await testData.cleanupWorkspace(workspace.id)
  }
})
```

## Best Practices

1. **Always cleanup**: Use try/finally blocks to ensure cleanup happens even if tests fail

2. **Use unique emails**: Use the `uniqueEmail()` helper to generate unique test user emails

3. **Cleanup patterns**: Use SQL LIKE patterns for cleanup (e.g., 'e2e-test-%')

4. **Isolation**: Each test should create and cleanup its own data

5. **Avoid reset**: Only use `resetDatabase()` in setup/teardown, not in individual tests

6. **Check environment**: Test data endpoints automatically check environment, but be aware they won't work in production

## Example Test

See `frontend/e2e/tests/examples/test-data-seeding.spec.ts` for a complete example.

## Troubleshooting

### Endpoints return 403

The testing endpoints are blocked in production. Ensure you're running in a non-production environment.

### Data not appearing

Check that:
1. The API calls succeeded (check response status)
2. You're using the correct IDs (workspace_id, user_id, etc.)
3. The database connection is working

### Cleanup not working

Ensure:
1. The email pattern matches your test users (use SQL LIKE syntax)
2. You have the correct permissions
3. Foreign key constraints aren't blocking deletion

## Database Schema

The test data endpoints work with these tables:
- `users`
- `workspaces`
- `posts`
- `inbox_items`
- `support_tickets`
- `social_accounts`

Cleanup operations handle foreign key relationships automatically.
