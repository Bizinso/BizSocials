import { test, expect } from '@playwright/test'

test.describe('Public Roadmap', () => {
  test('roadmap page loads at /roadmap', async ({ page }) => {
    await page.goto('/roadmap')
    await expect(page).toHaveURL(/\/roadmap/)
  })

  test('roadmap shows content', async ({ page }) => {
    await page.goto('/roadmap')
    await expect(page.getByRole('heading', { name: /Product Roadmap/i, level: 1 })).toBeVisible()
  })
})
