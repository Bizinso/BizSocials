import { test, expect } from '@playwright/test'
import { ACCOUNTS } from '../../helpers/constants'

test.describe('Logout', () => {
  test('clearing auth token and visiting dashboard redirects to login', async ({ page }) => {
    // Login via form to establish session
    await page.goto('/login')
    await page.locator('#email').fill(ACCOUNTS.owner.email)
    const pw = page.locator('#password').locator('input').first()
    await pw.click()
    await pw.pressSequentially(ACCOUNTS.owner.password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await page.waitForURL('**/app/dashboard', { timeout: 30_000 })

    // Clear token
    await page.evaluate(() => localStorage.removeItem('auth_token'))

    // Visit protected route — should redirect to login
    await page.goto('/app/dashboard')
    await page.waitForURL('**/login**', { timeout: 15_000 })
    await expect(page).toHaveURL(/\/login/)
  })
})
