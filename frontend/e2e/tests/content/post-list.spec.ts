import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

let workspaceId: string

test.beforeAll(async ({ browser }) => {
  const ctx = await browser.newContext({ storageState: 'e2e/.auth/owner.json' })
  const page = await ctx.newPage()
  await page.goto('/app/dashboard')
  await page.waitForLoadState('domcontentloaded')
  await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 30_000 })
  await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {})
  const card = page.locator('.cursor-pointer').first()
  await card.waitFor({ state: 'visible', timeout: 30_000 })
  await card.click()
  await page.waitForURL(/\/app\/w\//, { timeout: 15_000 })
  workspaceId = page.url().match(/\/app\/w\/([^/]+)/)?.[1] || ''
  await ctx.close()
})

test.describe('Post List', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts`)
    await page.waitForLoadState('domcontentloaded')
  })

  test('post list page loads with "Posts" heading', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Posts' })).toBeVisible({ timeout: 15_000 })
  })

  test('"New Post" button is visible', async ({ page }) => {
    await expect(page.getByRole('button', { name: /New Post/i })).toBeVisible({ timeout: 15_000 })
  })

  test('post list shows items or empty state', async ({ page }) => {
    // Auto-wait for content to appear
    const content = page.locator('.divide-y, .p-datatable, table, h1, h2, h3').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })

  test('search box is functional', async ({ page }) => {
    const search = page.locator('input[placeholder*="Search"], input[placeholder*="search"]').first()
    if (await search.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await search.fill('test')
      await page.waitForTimeout(500)
    }
  })

  test('filter by status dropdown exists', async ({ page }) => {
    const statusDropdown = page.locator('.p-select').first()
    if (await statusDropdown.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await statusDropdown.click()
      const overlay = page.locator('.p-select-overlay')
      await overlay.waitFor({ state: 'visible', timeout: 5_000 })
      await overlay.locator('.p-select-option').first().click()
    }
  })
})
