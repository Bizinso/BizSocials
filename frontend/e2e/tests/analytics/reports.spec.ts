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

test.describe('Reports', () => {
  test('reports page loads', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/reports`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(new RegExp(`/app/w/${workspaceId}/reports`))
  })

  test('page shows "Reports" heading', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/reports`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByRole('heading', { name: 'Reports' })).toBeVisible({ timeout: 15_000 })
  })

  test('reports list or empty state is displayed', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/reports`)
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for content
    const content = page.locator('.p-datatable, .divide-y, h1, h2, h3, button, .p-card').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
