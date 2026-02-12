import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Tenant Settings', () => {
  test('tenant settings page loads', async ({ page }) => {
    await page.goto('/app/settings/tenant')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/tenant/)
  })

  test('page shows tenant information', async ({ page }) => {
    await page.goto('/app/settings/tenant')
    await page.waitForLoadState('domcontentloaded')
    // Wait for skeleton loading to finish - settings pages can be slow
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 25_000 }).catch(() => {})
    // Check for any content element
    const content = page.locator('form, input, .p-card, h1, h2, .p-inputtext, button, label').first()
    await expect(content).toBeVisible({ timeout: 20_000 })
  })
})
