import { test, expect } from '@playwright/test'

test.describe('Admin Users', () => {
  test('users page loads', async ({ page }) => {
    await page.goto('/admin/users')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/admin\/users/)
    await expect(page.getByRole('heading', { name: /User/i }).first()).toBeVisible({ timeout: 15_000 })
  })

  test('user list is displayed', async ({ page }) => {
    await page.goto('/admin/users')
    await page.waitForLoadState('domcontentloaded')
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {})
    const table = page.locator('.p-datatable, table').first()
    await expect(table).toBeVisible({ timeout: 10_000 })
  })
})
