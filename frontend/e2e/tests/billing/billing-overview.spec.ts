import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Billing Overview', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/app/billing')
    await page.waitForLoadState('domcontentloaded')
  })

  test('billing overview page loads', async ({ page }) => {
    await expect(page).toHaveURL(/\/billing/)
    await expect(page.getByRole('heading', { name: 'Billing' })).toBeVisible({ timeout: 15_000 })
  })

  test('subscription card is displayed', async ({ page }) => {
    // Wait for skeleton loading and then content
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 25_000 }).catch(() => {})
    await expect(page.getByText('Subscription')).toBeVisible({ timeout: 15_000 })
  })

  test('usage metrics section is visible', async ({ page }) => {
    // Wait for skeleton loading to finish - API can be slow
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 25_000 }).catch(() => {})
    await expect(page.getByText('Usage')).toBeVisible({ timeout: 15_000 })
  })
})
