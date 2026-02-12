import { test, expect } from '@playwright/test'
import { ACCOUNTS } from '../../helpers/constants'

test.describe('Route Guards', () => {
  test('unauthenticated user visiting /app/dashboard is redirected to /login', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForURL('**/login**', { timeout: 15_000 })
    await expect(page).toHaveURL(/\/login/)
  })

  test('unauthenticated user visiting protected route is redirected to /login', async ({ page }) => {
    await page.goto('/app/settings/profile')
    await page.waitForURL('**/login**', { timeout: 15_000 })
    await expect(page).toHaveURL(/\/login/)
  })

  test('authenticated user visiting /login is redirected to dashboard', async ({ page }) => {
    // Login normally first to establish session
    await page.goto('/login')
    await page.locator('#email').fill(ACCOUNTS.owner.email)
    const pw = page.locator('#password').locator('input').first()
    await pw.click()
    await pw.pressSequentially(ACCOUNTS.owner.password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await page.waitForURL('**/app/dashboard', { timeout: 30_000 })

    // Visiting /login should redirect back to dashboard
    await page.goto('/login')
    await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
  })
})
