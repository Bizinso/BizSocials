import { test, expect } from '@playwright/test'

test.describe('Public Changelog', () => {
  test('changelog page loads at /changelog', async ({ page }) => {
    await page.goto('/changelog')
    await expect(page).toHaveURL(/\/changelog/)
  })

  test('changelog entries are displayed', async ({ page }) => {
    await page.goto('/changelog')
    await expect(page.getByRole('heading', { name: 'Changelog', level: 1 })).toBeVisible()
  })
})
