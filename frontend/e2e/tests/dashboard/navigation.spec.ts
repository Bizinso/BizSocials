import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Sidebar Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    // Wait for dashboard to fully render before interacting with sidebar
    await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 15_000 })
    // Click first workspace to populate sidebar workspace items
    const card = page.locator('.cursor-pointer').first()
    await card.waitFor({ state: 'visible', timeout: 15_000 })
    await card.click()
    await page.waitForURL(/\/app\/w\//, { timeout: 10_000 })
  })

  test('sidebar shows Dashboard and Workspaces links', async ({ page }) => {
    // Use text content matching for sidebar items
    await expect(page.locator('nav, aside, [role="navigation"]').locator('text=Dashboard').first()).toBeVisible({ timeout: 15_000 })
    await expect(page.locator('nav, aside, [role="navigation"]').locator('text=Workspaces').first()).toBeVisible()
  })

  test('clicking sidebar "Posts" navigates to posts page', async ({ page }) => {
    const postsBtn = page.locator('button', { hasText: 'Posts' }).first()
    await postsBtn.waitFor({ state: 'visible', timeout: 10_000 })
    await postsBtn.click()
    await page.waitForURL(/\/posts/, { timeout: 10_000 })
    await expect(page).toHaveURL(/\/posts/)
  })

  test('clicking sidebar "Inbox" navigates to inbox page', async ({ page }) => {
    const inboxBtn = page.locator('button', { hasText: 'Inbox' }).first()
    await inboxBtn.waitFor({ state: 'visible', timeout: 10_000 })
    await inboxBtn.click()
    await page.waitForURL(/\/inbox/, { timeout: 10_000 })
    await expect(page).toHaveURL(/\/inbox/)
  })

  test('clicking sidebar "Billing" navigates to billing page', async ({ page }) => {
    const billingBtn = page.locator('button', { hasText: 'Billing' }).first()
    await billingBtn.waitFor({ state: 'visible', timeout: 10_000 })
    await billingBtn.click()
    await page.waitForURL(/\/billing/, { timeout: 10_000 })
    await expect(page).toHaveURL(/\/billing/)
  })

  test('clicking sidebar "Settings" navigates to settings page', async ({ page }) => {
    const settingsBtn = page.locator('button', { hasText: 'Settings' }).first()
    await settingsBtn.waitFor({ state: 'visible', timeout: 10_000 })
    await settingsBtn.click()
    await page.waitForURL(/\/settings/, { timeout: 10_000 })
    await expect(page).toHaveURL(/\/settings/)
  })
})
