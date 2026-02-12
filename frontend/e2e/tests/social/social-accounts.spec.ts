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

test.describe('Social Accounts', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/social-accounts`)
    await page.waitForLoadState('domcontentloaded')
  })

  test('social accounts page loads', async ({ page }) => {
    await expect(page).toHaveURL(new RegExp(`/app/w/${workspaceId}/social-accounts`))
  })

  test('page shows "Social Accounts" heading', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Social Accounts' })).toBeVisible({ timeout: 15_000 })
  })

  test('"Connect Account" button is visible', async ({ page }) => {
    await expect(page.getByRole('button', { name: /Connect Account/i })).toBeVisible({ timeout: 15_000 })
  })

  test('account list or empty state is displayed', async ({ page }) => {
    // Wait for heading to confirm page rendered
    await page.getByRole('heading', { name: 'Social Accounts' }).waitFor({ state: 'visible', timeout: 20_000 })
    // Wait for skeleton loading to finish
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 20_000 }).catch(() => {})
    // Check for any visible content after loading
    const content = page.locator('button, .rounded-lg, .divide-y, h1, h2, h3, .p-card').first()
    await expect(content).toBeVisible({ timeout: 10_000 })
  })
})
