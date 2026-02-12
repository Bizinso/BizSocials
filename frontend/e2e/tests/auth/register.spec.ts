import { test, expect } from '@playwright/test'

test.describe('Register Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/register')
  })

  test('displays register form with all fields', async ({ page }) => {
    await expect(page.locator('#name')).toBeVisible()
    await expect(page.locator('#email')).toBeVisible()
    await expect(page.locator('#password')).toBeVisible()
    await expect(page.locator('#password_confirmation')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Create account' })).toBeVisible()
  })

  test('shows error for duplicate email', async ({ page }) => {
    await page.locator('#name').fill('Test User')
    await page.locator('#email').fill('john.owner@acme.example.com')
    const pwInput = page.locator('#password').locator('input').first()
    await pwInput.click()
    await pwInput.pressSequentially('User@1234')
    await page.keyboard.press('Escape') // Dismiss password strength overlay
    const pwConfirm = page.locator('#password_confirmation').locator('input').first()
    await pwConfirm.click()
    await pwConfirm.pressSequentially('User@1234')
    await page.getByRole('button', { name: 'Create account' }).click()

    // Wait for form to finish submitting
    await expect(page.getByRole('button', { name: 'Create account' })).toBeEnabled({ timeout: 30_000 })
    // Error can be shown inline (below field) or as a toast
    await expect(
      page.getByText('already been taken').or(page.locator('.p-toast-message')).first(),
    ).toBeVisible({ timeout: 15_000 })
  })

  test('successful registration redirects to dashboard', async ({ page }) => {
    const uniqueEmail = `e2e-reg-${Date.now()}@test.example.com`
    await page.locator('#name').fill('E2E Test User')
    await page.locator('#email').fill(uniqueEmail)
    const pwInput = page.locator('#password').locator('input').first()
    await pwInput.click()
    await pwInput.pressSequentially('TestPass1234!')
    await page.keyboard.press('Escape') // Dismiss password strength overlay
    const pwConfirm = page.locator('#password_confirmation').locator('input').first()
    await pwConfirm.click()
    await pwConfirm.pressSequentially('TestPass1234!')
    await page.getByRole('button', { name: 'Create account' }).click()

    await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
    await expect(page).toHaveURL(/\/app\/dashboard/)
  })

  test('"Sign in" link navigates to /login', async ({ page }) => {
    await page.getByRole('link', { name: 'Sign in' }).click()
    await expect(page).toHaveURL(/\/login/)
  })
})
