import { test, expect } from '@playwright/test'
import { ACCOUNTS } from '../../helpers/constants'

test.describe('Login Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await expect(page.getByRole('heading', { name: 'Sign in to your account' })).toBeVisible()
  })

  test('displays login form with email, password fields and sign in button', async ({ page }) => {
    const emailInput = page.locator('#email')
    await expect(emailInput).toBeVisible()
    await expect(emailInput).toHaveAttribute('type', 'email')
    const passwordInput = page.locator('#password').locator('input').first()
    await expect(passwordInput).toBeVisible()
    await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible()
    await expect(page.getByText('Remember me')).toBeVisible()
  })

  test('shows validation error for empty fields', async ({ page }) => {
    const signInBtn = page.getByRole('button', { name: 'Sign in' })
    await signInBtn.click()
    // Wait for the button to return to enabled (loading finished)
    await expect(signInBtn).toBeEnabled({ timeout: 20_000 })
    // After submit with empty fields, backend returns 422 with field errors shown inline,
    // or a general error shown as a toast
    const toast = page.locator('.p-toast-message')
    const inlineError = page.locator('small.text-red-500')
    await expect(toast.or(inlineError).first()).toBeVisible({ timeout: 15_000 })
  })

  test('shows error for invalid credentials', async ({ page }) => {
    await page.locator('#email').fill('wrong@example.com')
    const pwInput = page.locator('#password').locator('input').first()
    await pwInput.click()
    await pwInput.pressSequentially('WrongPassword123')
    await page.getByRole('button', { name: 'Sign in' }).click()
    // Wait for loading to finish
    await expect(page.getByRole('button', { name: 'Sign in' })).toBeEnabled({ timeout: 20_000 })
    // Error may be shown inline or as a toast
    const errorIndicator = page.locator('.p-toast-message, .text-red-500')
    await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
  })

  test('successful login redirects to /app/dashboard', async ({ page }) => {
    await page.locator('#email').fill(ACCOUNTS.owner.email)
    const pwInput = page.locator('#password').locator('input').first()
    await pwInput.click()
    await pwInput.pressSequentially(ACCOUNTS.owner.password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
    await expect(page).toHaveURL(/\/app\/dashboard/)
  })

  test('login with redirect param goes to that URL after login', async ({ page }) => {
    const redirectTarget = '/app/settings/profile'
    await page.goto("/login?redirect=" + encodeURIComponent(redirectTarget))
    await page.locator('#email').fill(ACCOUNTS.owner.email)
    const pwInput = page.locator('#password').locator('input').first()
    await pwInput.click()
    await pwInput.pressSequentially(ACCOUNTS.owner.password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await page.waitForURL('**' + redirectTarget, { timeout: 30_000 })
    await expect(page).toHaveURL(new RegExp(redirectTarget.replace(/\//g, '\\/')))
  })

  test('"Sign up" link navigates to /register', async ({ page }) => {
    const signUpLink = page.getByRole('link', { name: 'Sign up' })
    await expect(signUpLink).toBeVisible()
    await signUpLink.click()
    await page.waitForURL('**/register', { timeout: 10_000 })
    await expect(page).toHaveURL(/\/register/)
  })

  test('"Forgot password?" link navigates to /forgot-password', async ({ page }) => {
    const forgotLink = page.getByRole('link', { name: 'Forgot password?' })
    await expect(forgotLink).toBeVisible()
    await forgotLink.click()
    await page.waitForURL('**/forgot-password', { timeout: 10_000 })
    await expect(page).toHaveURL(/\/forgot-password/)
  })

})
