import { test, expect } from '@playwright/test'

test.describe('404 Not Found', () => {
  test('visiting invalid URL shows 404 page', async ({ page }) => {
    await page.goto('/some-random-nonexistent-page')
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByText('404')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByRole('heading', { name: 'Page not found' })).toBeVisible()
  })

  test('404 page has navigation buttons', async ({ page }) => {
    await page.goto('/totally-invalid-route-xyz')
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByText('404')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByRole('button', { name: 'Go back' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Home' })).toBeVisible()
  })
})
