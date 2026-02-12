import { test, expect } from '@playwright/test'

test.describe('Public Feedback', () => {
  test('feedback page loads at /feedback', async ({ page }) => {
    await page.goto('/feedback')
    await expect(page).toHaveURL(/\/feedback/)
  })

  test('page has appropriate heading', async ({ page }) => {
    await page.goto('/feedback')
    await expect(page.getByRole('heading', { name: 'Feedback', level: 1 })).toBeVisible()
  })
})
