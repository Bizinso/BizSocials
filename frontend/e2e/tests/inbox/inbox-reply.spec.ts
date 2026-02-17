import { test, expect } from '@playwright/test'
import { ApiHelper, getApiHelper } from '../../helpers/api.helper'
import { createTestDataHelper } from '../../helpers/test-data.helper'

test.use({ storageState: 'e2e/.auth/owner.json' })

let workspaceId: string
let api: ApiHelper
let inboxItemId: string

test.beforeAll(async ({ browser }) => {
  // Get workspace ID
  const ctx = await browser.newContext({ storageState: 'e2e/.auth/owner.json' })
  const page = await ctx.newPage()
  await page.goto('/app/dashboard')
  await page.waitForLoadState('domcontentloaded')
  await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 15_000 })
  const card = page.locator('.cursor-pointer').first()
  await card.waitFor({ state: 'visible', timeout: 15_000 })
  await card.click()
  await page.waitForURL(/\/app\/w\//, { timeout: 15_000 })
  workspaceId = page.url().match(/\/app\/w\/([^/]+)/)?.[1] || ''
  await ctx.close()

  // Set up API helper and seed test data
  api = await getApiHelper('owner@bizsocials.test', 'password')
  const testData = createTestDataHelper(api)
  
  // Seed inbox items for testing
  const items = await testData.seedInboxItems(workspaceId, 3, 'facebook') as any
  inboxItemId = items[0]?.id || ''
})

test.describe('Inbox Reply Flow', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!workspaceId || !inboxItemId, 'No workspace or inbox item available')
  })

  test('views inbox messages', async ({ page }) => {
    // Navigate to inbox
    await page.goto(`/app/w/${workspaceId}/inbox`)
    await page.waitForLoadState('domcontentloaded')

    // Verify inbox page loads
    await expect(page.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 15_000 })

    // Verify inbox items are displayed
    const inboxItems = page.locator('[class*="inbox-item"], .divide-y > div, .cursor-pointer')
    await expect(inboxItems.first()).toBeVisible({ timeout: 10_000 })
  })

  test('opens inbox item detail view', async ({ page }) => {
    // Navigate to inbox
    await page.goto(`/app/w/${workspaceId}/inbox`)
    await page.waitForLoadState('domcontentloaded')

    // Click on first inbox item
    const firstItem = page.locator('[class*="inbox-item"], .divide-y > div, .cursor-pointer').first()
    await firstItem.waitFor({ state: 'visible', timeout: 10_000 })
    await firstItem.click()

    // Wait for detail view to load
    await page.waitForURL(/\/inbox\//, { timeout: 10_000 })

    // Verify detail view elements are visible
    await expect(page.getByRole('button', { name: /Back to Inbox/i })).toBeVisible({ timeout: 10_000 })
  })

  test('sends a reply to inbox message', async ({ page }) => {
    // Navigate directly to inbox item detail
    await page.goto(`/app/w/${workspaceId}/inbox/${inboxItemId}`)
    await page.waitForLoadState('domcontentloaded')

    // Wait for the page to load
    await page.getByRole('button', { name: /Back to Inbox/i }).waitFor({ state: 'visible', timeout: 15_000 })

    // Find the reply textarea
    const replyTextarea = page.locator('textarea[placeholder*="reply" i], textarea[placeholder*="Type" i]').first()
    
    // Check if reply is available (not a mention)
    const isReplyAvailable = await replyTextarea.isVisible({ timeout: 5_000 }).catch(() => false)
    
    if (isReplyAvailable) {
      // Type a reply
      const replyMessage = `E2E Test Reply - ${Date.now()}`
      await replyTextarea.fill(replyMessage)

      // Click send button
      const sendButton = page.getByRole('button', { name: /Send Reply/i })
      await expect(sendButton).toBeEnabled()
      await sendButton.click()

      // Wait for success message
      await expect(page.locator('text=/Reply sent/i, .p-toast-message')).toBeVisible({ timeout: 10_000 })

      // Verify reply appears in the thread
      await expect(page.locator(`text="${replyMessage}"`)).toBeVisible({ timeout: 10_000 })

      // Verify textarea is cleared
      await expect(replyTextarea).toHaveValue('')
    } else {
      // If reply is not available (mention), verify the message
      await expect(page.locator('text=/Replies are not available/i')).toBeVisible({ timeout: 5_000 })
    }
  })

  test('displays existing replies in thread', async ({ page }) => {
    // Navigate to inbox item detail
    await page.goto(`/app/w/${workspaceId}/inbox/${inboxItemId}`)
    await page.waitForLoadState('domcontentloaded')

    // Wait for the page to load
    await page.getByRole('button', { name: /Back to Inbox/i }).waitFor({ state: 'visible', timeout: 15_000 })

    // Check for replies section
    const repliesHeading = page.locator('text=/Replies \\(/i')
    await expect(repliesHeading).toBeVisible({ timeout: 10_000 })

    // Verify replies count is displayed
    await expect(repliesHeading).toContainText(/Replies \(\d+\)/)
  })

  test('validates empty reply submission', async ({ page }) => {
    // Navigate to inbox item detail
    await page.goto(`/app/w/${workspaceId}/inbox/${inboxItemId}`)
    await page.waitForLoadState('domcontentloaded')

    // Wait for the page to load
    await page.getByRole('button', { name: /Back to Inbox/i }).waitFor({ state: 'visible', timeout: 15_000 })

    // Find the reply textarea
    const replyTextarea = page.locator('textarea[placeholder*="reply" i], textarea[placeholder*="Type" i]').first()
    
    // Check if reply is available
    const isReplyAvailable = await replyTextarea.isVisible({ timeout: 5_000 }).catch(() => false)
    
    if (isReplyAvailable) {
      // Ensure textarea is empty
      await replyTextarea.clear()

      // Verify send button is disabled
      const sendButton = page.getByRole('button', { name: /Send Reply/i })
      await expect(sendButton).toBeDisabled()
    }
  })

  test('navigates back to inbox list', async ({ page }) => {
    // Navigate to inbox item detail
    await page.goto(`/app/w/${workspaceId}/inbox/${inboxItemId}`)
    await page.waitForLoadState('domcontentloaded')

    // Click back button
    const backButton = page.getByRole('button', { name: /Back to Inbox/i })
    await backButton.waitFor({ state: 'visible', timeout: 15_000 })
    await backButton.click()

    // Verify we're back at inbox list
    await page.waitForURL(new RegExp(`/app/w/${workspaceId}/inbox$`), { timeout: 10_000 })
    await expect(page.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 10_000 })
  })
})
