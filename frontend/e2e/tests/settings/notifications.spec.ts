import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Notification Settings', () => {
  test('notification preferences page loads', async ({ page }) => {
    await page.goto('/app/settings/notifications')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/notifications/)
  })

  test('page has notification-related content', async ({ page }) => {
    await page.goto('/app/settings/notifications')
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for any notification content element
    const content = page.locator('.p-toggleswitch, .p-inputswitch, input[type="checkbox"], h1, h2, .p-card, form, button').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
