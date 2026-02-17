import { test, expect } from '@playwright/test'
import { getApiHelper } from '../../helpers/api.helper'
import { createTestDataHelper } from '../../helpers/test-data.helper'
import { uniqueEmail } from '../../helpers/constants'
import { LoginPage } from '../../pages/LoginPage'
import { DashboardPage } from '../../pages/DashboardPage'
import { PostListPage } from '../../pages/PostListPage'

/**
 * Example test demonstrating test data seeding
 * 
 * This test shows how to:
 * 1. Create test users with workspaces
 * 2. Seed test data (posts, inbox items, etc.)
 * 3. Cleanup test data after tests
 */
test.describe('Test Data Seeding Example', () => {
  test('should create user, seed data, and cleanup', async ({ page }) => {
    // Generate unique test user credentials
    const email = uniqueEmail()
    const password = 'Test@1234'
    const name = 'E2E Test User'

    // Get API helper (no auth needed for test data endpoints)
    const api = await getApiHelper('john.owner@acme.example.com', 'User@1234')
    const testData = createTestDataHelper(api)

    try {
      // Step 1: Create test user with workspace
      const { user, workspace } = await testData.createUserWithWorkspace({
        email,
        password,
        name,
        workspaceName: 'E2E Test Workspace',
        role: 'owner',
      })

      expect(user).toBeDefined()
      expect(workspace).toBeDefined()
      expect(user.email).toBe(email)

      // Step 2: Seed test posts
      const posts = await testData.seedPosts(workspace.id, user.id, 5, 'draft')
      expect(posts).toHaveLength(5)

      // Step 3: Login as the test user
      const loginPage = new LoginPage(page)
      await loginPage.goto()
      await loginPage.login(email, password)

      // Step 4: Verify user can see their workspace
      const dashboardPage = new DashboardPage(page)
      await dashboardPage.expectLoaded()
      await expect(page.getByText('E2E Test Workspace')).toBeVisible()

      // Step 5: Navigate to posts and verify seeded data
      await dashboardPage.clickWorkspace('E2E Test Workspace')
      
      const postListPage = new PostListPage(page)
      await postListPage.goto(workspace.id)
      await postListPage.expectLoaded()

      // Verify posts are visible
      const postRows = postListPage.getPostRows()
      await expect(postRows.first()).toBeVisible()

    } finally {
      // Step 6: Cleanup test data
      await testData.cleanup(email.replace('@', '%'))
    }
  })

  test('should seed inbox items and verify', async ({ page }) => {
    const api = await getApiHelper('john.owner@acme.example.com', 'User@1234')
    const testData = createTestDataHelper(api)

    // Get existing workspace
    const workspaces = await api.listWorkspaces()
    const workspace = workspaces[0]

    if (!workspace) {
      test.skip()
      return
    }

    try {
      // Seed inbox items
      const items = await testData.seedInboxItems(workspace.id, 3, 'facebook')
      expect(items).toHaveLength(3)

      // Login and verify
      const loginPage = new LoginPage(page)
      await loginPage.goto()
      await loginPage.login('john.owner@acme.example.com', 'User@1234')

      // Navigate to inbox
      await page.goto(`/app/w/${workspace.id}/inbox`)
      await page.waitForLoadState('domcontentloaded')

      // Verify inbox items are visible
      await expect(page.getByText('Inbox')).toBeVisible()

    } finally {
      // Cleanup workspace data
      await testData.cleanupWorkspace(workspace.id)
    }
  })
})
