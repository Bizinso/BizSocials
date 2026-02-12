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

test.describe('Approvals', () => {
  test('approvals page loads', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/approvals`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(new RegExp(`/app/w/${workspaceId}/approvals`))
  })

  test('page has appropriate heading', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/approvals`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByRole('heading', { name: /Approval/i })).toBeVisible({ timeout: 15_000 })
  })

  test('approval queue shows list or empty state', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/approvals`)
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for content to appear
    const content = page.locator('.divide-y, .p-datatable, table, h1, h2, h3').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
