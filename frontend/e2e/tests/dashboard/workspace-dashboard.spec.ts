import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

let workspaceId: string

test.beforeAll(async ({ browser }) => {
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
})

test.describe('Workspace Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}`)
    await page.waitForLoadState('domcontentloaded')
  })

  test('workspace dashboard loads with description', async ({ page }) => {
    await expect(page.getByText('Overview of your workspace activity')).toBeVisible({ timeout: 15_000 })
  })

  test('shows stat cards: Total Posts, Social Accounts, Unread Inbox, Members', async ({ page }) => {
    // Wait for content to load
    await expect(page.getByText('Total Posts')).toBeVisible({ timeout: 15_000 })
    // Scope to main content to avoid sidebar "Social Accounts" match
    await expect(page.getByRole('main').getByText('Social Accounts')).toBeVisible()
    await expect(page.getByText('Unread Inbox')).toBeVisible()
    // Use exact match for "Members" stat card (sidebar has no "Members" text)
    await expect(page.getByRole('main').getByText('Members')).toBeVisible()
  })

  test('stat card values are numbers', async ({ page }) => {
    await expect(page.getByText('Total Posts')).toBeVisible({ timeout: 15_000 })
    // Use p.text-2xl to match only the number paragraph, not the icon
    const totalPosts = page.locator('.text-center').filter({ hasText: 'Total Posts' }).locator('p.text-2xl')
    const value = await totalPosts.textContent()
    expect(value).toMatch(/^\d+$/)
  })

  test('quick action buttons are visible', async ({ page }) => {
    await expect(page.getByText('Quick Actions')).toBeVisible({ timeout: 15_000 })
    // Use exact: true to distinguish from sidebar "Analytics" button
    await expect(page.getByRole('button', { name: 'Create Post' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'View Inbox' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Analytics', exact: true })).toBeVisible()
  })

  test('recent posts section is visible', async ({ page }) => {
    await expect(page.getByText('Recent Posts')).toBeVisible({ timeout: 15_000 })
  })
})
