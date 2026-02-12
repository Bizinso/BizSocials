import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Profile Settings', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/app/settings/profile')
    await page.waitForLoadState('domcontentloaded')
  })

  test('profile settings page loads', async ({ page }) => {
    await expect(page).toHaveURL(/\/settings\/profile/)
  })

  test('profile form displays user info fields', async ({ page }) => {
    // Auto-wait for any form input to appear (NOT instant isVisible)
    const content = page.locator('input, .p-inputtext, .p-password, form, textarea').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })

  test('save button is visible', async ({ page }) => {
    const saveBtn = page.getByRole('button', { name: /Save/i })
    await expect(saveBtn).toBeVisible({ timeout: 15_000 })
  })
})
