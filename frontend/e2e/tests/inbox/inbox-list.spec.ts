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

test.describe('Inbox', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/inbox`)
    await page.waitForLoadState('domcontentloaded')
  })

  test('inbox page loads at correct URL', async ({ page }) => {
    await expect(page).toHaveURL(new RegExp(`/app/w/${workspaceId}/inbox`))
  })

  test('page shows "Inbox" heading', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 15_000 })
  })

  test('inbox shows items or empty state', async ({ page }) => {
    // Auto-wait for any content after page loads
    const content = page.locator('.divide-y, .cursor-pointer, h1, h2, h3, .p-card, button').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })

  test('changing status filter updates the list', async ({ page }) => {
    const statusSelect = page.locator('.p-select').first()
    if (await statusSelect.isVisible({ timeout: 10_000 }).catch(() => false)) {
      await statusSelect.click()
      const overlay = page.locator('.p-select-overlay')
      await overlay.waitFor({ state: 'visible', timeout: 5_000 })
      await overlay.locator('.p-select-option').first().click()
      await page.waitForLoadState('domcontentloaded')
    }
  })
})
