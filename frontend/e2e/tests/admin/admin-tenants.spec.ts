import { test, expect } from '@playwright/test'

test.describe('Admin Tenants', () => {
  test('tenants page loads', async ({ page }) => {
    await page.goto('/admin/tenants')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/admin\/tenants/)
    await expect(page.getByRole('heading', { name: /Tenant/i }).first()).toBeVisible({ timeout: 15_000 })
  })

  test('tenant list is displayed', async ({ page }) => {
    await page.goto('/admin/tenants')
    await page.waitForLoadState('domcontentloaded')
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {})
    const table = page.locator('.p-datatable, table').first()
    await expect(table).toBeVisible({ timeout: 10_000 })
  })
})
