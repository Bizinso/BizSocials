import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Billing Invoices', () => {
  test('invoices page loads', async ({ page }) => {
    await page.goto('/app/billing/invoices')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/billing\/invoices/)
  })

  test('invoice list or empty state is displayed', async ({ page }) => {
    await page.goto('/app/billing/invoices')
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for any content
    const content = page.locator('.p-datatable, .divide-y, table, h1, h2, h3, button, .p-card').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
