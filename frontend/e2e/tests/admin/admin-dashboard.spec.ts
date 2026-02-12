import { test, expect } from '@playwright/test'

test.describe('Admin Dashboard', () => {
  test('admin dashboard loads with heading', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByRole('heading', { name: /Platform Dashboard/i })).toBeVisible({ timeout: 15_000 })
  })

  test('platform stats cards are displayed', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {})
    await expect(page.getByText('Total Tenants')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByText('Total Users')).toBeVisible()
  })
})
