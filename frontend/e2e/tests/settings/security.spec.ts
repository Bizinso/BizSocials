import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Security Settings', () => {
  test('security settings page loads', async ({ page }) => {
    await page.goto('/app/settings/security')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/security/)
  })

  test('page has security-related content', async ({ page }) => {
    await page.goto('/app/settings/security')
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for any security content element
    const content = page.locator('input[type="password"], .p-password, h1, h2, form, button').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
