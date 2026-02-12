import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Workspace Management', () => {
  test('workspace list page loads at /app/workspaces', async ({ page }) => {
    await page.goto('/app/workspaces')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/app\/workspaces/)
    await expect(page.getByRole('heading', { name: 'Workspaces' })).toBeVisible({ timeout: 15_000 })
  })

  test('workspace cards are displayed on dashboard', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    // Wait for heading first to confirm page has rendered
    await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 15_000 })
    const cards = page.locator('.cursor-pointer')
    await expect(cards.first()).toBeVisible({ timeout: 15_000 })
  })

  test('clicking a workspace navigates to workspace dashboard', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 15_000 })
    const card = page.locator('.cursor-pointer').first()
    await card.waitFor({ state: 'visible', timeout: 15_000 })
    await card.click()
    await page.waitForURL(/\/app\/w\//, { timeout: 10_000 })
  })
})
