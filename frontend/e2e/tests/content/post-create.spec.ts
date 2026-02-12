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

test.describe('Post Create', () => {
  test('post create page loads with heading', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByRole('heading', { name: 'Create Post' })).toBeVisible({ timeout: 15_000 })
  })

  test('post editor has a textarea', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    const textarea = page.locator('textarea').first()
    await expect(textarea).toBeVisible({ timeout: 15_000 })
  })

  test('can type content in textarea', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    const textarea = page.locator('textarea').first()
    await textarea.waitFor({ state: 'visible', timeout: 15_000 })
    await textarea.fill('E2E test post content')
    await expect(textarea).toHaveValue(/E2E test post content/)
  })

  test('save draft button is visible', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    // Wait for page heading to confirm the view loaded
    await page.getByRole('heading', { name: 'Create Post' }).waitFor({ state: 'visible', timeout: 20_000 })
    await expect(page.getByRole('button', { name: /Save Draft/i })).toBeVisible({ timeout: 15_000 })
  })
})
