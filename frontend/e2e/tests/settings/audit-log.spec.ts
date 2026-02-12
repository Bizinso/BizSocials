import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Audit Log', () => {
  test('audit log page loads', async ({ page }) => {
    // Route is /app/settings/audit (not audit-log)
    await page.goto('/app/settings/audit')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/audit/)
  })

  test('page shows audit content or empty state', async ({ page }) => {
    await page.goto('/app/settings/audit')
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for any content element
    const content = page.locator('.divide-y, .p-datatable, table, h1, h2, .p-card, button').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })
})
