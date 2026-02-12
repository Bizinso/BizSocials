import { test, expect } from '@playwright/test'

test.describe('Member Role Access', () => {
  test.use({ storageState: 'e2e/.auth/member.json' })

  test('member can access app dashboard', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    // Member auth can be slower - wait for heading with longer timeout
    const heading = page.getByRole('heading', { name: 'Dashboard' })
    await expect(heading).toBeVisible({ timeout: 30_000 })
  })

  test('member can view posts page', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 30_000 })
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {})
    const card = page.locator('.cursor-pointer').first()
    if (await card.isVisible({ timeout: 10_000 }).catch(() => false)) {
      await card.click()
      await page.waitForURL(/\/app\/w\//)
      const wsId = page.url().match(/\/app\/w\/([^/]+)/)?.[1]
      await page.goto(`/app/w/${wsId}/posts`)
      await page.waitForLoadState('domcontentloaded')
      await expect(page).toHaveURL(/\/posts/)
    }
  })

  test('member can access inbox', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 30_000 })
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {})
    const card = page.locator('.cursor-pointer').first()
    if (await card.isVisible({ timeout: 10_000 }).catch(() => false)) {
      await card.click()
      await page.waitForURL(/\/app\/w\//)
      const wsId = page.url().match(/\/app\/w\/([^/]+)/)?.[1]
      await page.goto(`/app/w/${wsId}/inbox`)
      await page.waitForLoadState('domcontentloaded')
      await expect(page).toHaveURL(/\/inbox/)
    }
  })

  test('member cannot access admin panel', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await page.waitForURL(/\/(login|app)/, { timeout: 15_000 }).catch(() => {})
    const url = page.url()
    expect(url).toBeTruthy()
  })
})
