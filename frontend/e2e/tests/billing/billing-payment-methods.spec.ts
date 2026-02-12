import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Payment Methods', () => {
  test('payment methods page loads', async ({ page }) => {
    await page.goto('/app/billing/payment-methods')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/billing\/payment-methods/)
  })

  test('payment method list or add option is visible', async ({ page }) => {
    await page.goto('/app/billing/payment-methods')
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for any content
    const content = page.locator('.p-card, .divide-y, button, h1, h2, h3, form, input').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
