import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('App Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    // Wait for heading to confirm Vue has rendered
    await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 15_000 })
  })

  test('dashboard page loads and shows heading and welcome description', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible()
    await expect(page.getByText('Welcome to BizSocials')).toBeVisible()
  })

  test('shows workspace count card', async ({ page }) => {
    // Wait for skeleton loading to finish (API can be slow)
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {})
    const statCard = page.getByRole('main').locator('.text-center').filter({ hasText: 'Workspaces' })
    await expect(statCard).toBeVisible({ timeout: 15_000 })
  })

  test('shows "Your Workspaces" heading', async ({ page }) => {
    // Wait for skeleton loading to finish (API can be slow)
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {})
    await expect(page.getByRole('heading', { name: 'Your Workspaces' })).toBeVisible({ timeout: 15_000 })
  })

  test('workspace cards are clickable and navigate to workspace dashboard', async ({ page }) => {
    const firstCard = page.locator('.cursor-pointer').first()
    await expect(firstCard).toBeVisible({ timeout: 15_000 })
    await firstCard.click()
    await page.waitForURL(/\/app\/w\//, { timeout: 10_000 })
  })
})
