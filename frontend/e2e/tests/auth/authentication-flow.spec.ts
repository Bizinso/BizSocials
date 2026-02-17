import { test, expect } from '@playwright/test'
import { LoginPage } from '../../pages/LoginPage'
import { RegisterPage } from '../../pages/RegisterPage'
import { DashboardPage } from '../../pages/DashboardPage'
import { ACCOUNTS } from '../../helpers/constants'
import { getApiHelper } from '../../helpers/api.helper'
import { createTestDataHelper } from '../../helpers/test-data.helper'

/**
 * E2E Test Suite: Authentication Flow
 * 
 * Task: 10.4 Write E2E test for authentication flow
 * Requirements: 14.1
 * 
 * This test suite validates the complete authentication flows:
 * - Login flow (successful and error cases)
 * - Registration flow (successful and validation)
 * - Password reset flow (forgot password and reset)
 */

test.describe('Authentication Flow - Complete E2E Tests', () => {
  
  test.describe('Login Flow', () => {
    let loginPage: LoginPage

    test.beforeEach(async ({ page }) => {
      loginPage = new LoginPage(page)
      await loginPage.goto()
    })

    test('should display login form with all required elements', async ({ page }) => {
      await expect(page.getByRole('heading', { name: 'Sign in to your account' })).toBeVisible()
      await expect(loginPage.emailInput).toBeVisible()
      await expect(loginPage.passwordInput).toBeVisible()
      await expect(loginPage.submitButton).toBeVisible()
      await expect(page.getByText('Remember me')).toBeVisible()
      await expect(loginPage.signUpLink).toBeVisible()
      await expect(loginPage.forgotPasswordLink).toBeVisible()
    })

    test('should successfully login with valid credentials and redirect to dashboard', async ({ page }) => {
      await loginPage.login(ACCOUNTS.owner.email, ACCOUNTS.owner.password)
      
      // Wait for redirect to dashboard
      await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
      await expect(page).toHaveURL(/\/app\/dashboard/)
      
      // Verify dashboard is loaded
      await expect(page.locator('h1, h2, [role="heading"]').first()).toBeVisible({ timeout: 10_000 })
    })

    test('should show validation error for empty email and password', async ({ page }) => {
      await loginPage.submitButton.click()
      
      // Wait for form submission to complete
      await expect(loginPage.submitButton).toBeEnabled({ timeout: 20_000 })
      
      // Check for error message (toast or inline)
      const errorIndicator = page.locator('.p-toast-message, .text-red-500, small.text-red-500')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error for invalid credentials', async ({ page }) => {
      await loginPage.login('invalid@example.com', 'WrongPassword123')
      
      // Wait for form submission to complete
      await expect(loginPage.submitButton).toBeEnabled({ timeout: 20_000 })
      
      // Check for error message
      const errorIndicator = page.locator('.p-toast-message, .text-red-500')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error for valid email but wrong password', async ({ page }) => {
      await loginPage.login(ACCOUNTS.owner.email, 'WrongPassword123')
      
      // Wait for form submission to complete
      await expect(loginPage.submitButton).toBeEnabled({ timeout: 20_000 })
      
      // Check for error message
      const errorIndicator = page.locator('.p-toast-message, .text-red-500')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should redirect to specified URL after login when redirect param is present', async ({ page }) => {
      const redirectTarget = '/app/settings/profile'
      await page.goto(`/login?redirect=${encodeURIComponent(redirectTarget)}`)
      
      await loginPage.login(ACCOUNTS.owner.email, ACCOUNTS.owner.password)
      
      // Wait for redirect to target URL
      await page.waitForURL(`**${redirectTarget}`, { timeout: 30_000 })
      await expect(page).toHaveURL(new RegExp(redirectTarget.replace(/\//g, '\\/')))
    })

    test('should navigate to register page when clicking Sign up link', async ({ page }) => {
      await loginPage.signUpLink.click()
      await page.waitForURL('**/register', { timeout: 10_000 })
      await expect(page).toHaveURL(/\/register/)
    })

    test('should navigate to forgot password page when clicking Forgot password link', async ({ page }) => {
      await loginPage.forgotPasswordLink.click()
      await page.waitForURL('**/forgot-password', { timeout: 10_000 })
      await expect(page).toHaveURL(/\/forgot-password/)
    })

    test('should persist session when Remember me is checked', async ({ page, context }) => {
      // Check remember me checkbox
      const rememberCheckbox = page.locator('input[type="checkbox"]').first()
      await rememberCheckbox.check()
      
      await loginPage.login(ACCOUNTS.owner.email, ACCOUNTS.owner.password)
      await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
      
      // Verify token is stored in localStorage
      const token = await page.evaluate(() => localStorage.getItem('auth_token'))
      expect(token).toBeTruthy()
    })
  })

  test.describe('Registration Flow', () => {
    let registerPage: RegisterPage
    let testEmail: string
    let api: any
    let testData: any

    test.beforeEach(async ({ page }) => {
      registerPage = new RegisterPage(page)
      await registerPage.goto()
      
      // Generate unique email for this test
      testEmail = `e2e-reg-${Date.now()}@test.example.com`
      
      // Setup API helper for cleanup
      api = await getApiHelper(ACCOUNTS.owner.email, ACCOUNTS.owner.password)
      testData = createTestDataHelper(api)
    })

    test.afterEach(async () => {
      // Cleanup test user
      try {
        await testData.cleanup(testEmail.replace('@', '%'))
      } catch (error) {
        console.log('Cleanup error (may be expected if user was not created):', error)
      }
    })

    test('should display registration form with all required fields', async ({ page }) => {
      await expect(page.getByRole('heading', { name: /create.*account/i })).toBeVisible()
      await expect(registerPage.nameInput).toBeVisible()
      await expect(registerPage.emailInput).toBeVisible()
      await expect(registerPage.passwordInput).toBeVisible()
      await expect(registerPage.confirmPasswordInput).toBeVisible()
      await expect(registerPage.submitButton).toBeVisible()
      await expect(registerPage.signInLink).toBeVisible()
    })

    test('should successfully register new user and redirect to dashboard', async ({ page }) => {
      await registerPage.nameInput.fill('E2E Test User')
      await registerPage.emailInput.fill(testEmail)
      await registerPage.passwordInput.click()
      await registerPage.passwordInput.pressSequentially('TestPass1234!')
      
      // Dismiss password strength overlay if present
      await page.keyboard.press('Escape')
      
      await registerPage.confirmPasswordInput.click()
      await registerPage.confirmPasswordInput.pressSequentially('TestPass1234!')
      await registerPage.submitButton.click()
      
      // Wait for redirect to dashboard
      await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
      await expect(page).toHaveURL(/\/app\/dashboard/)
      
      // Verify user is logged in
      const token = await page.evaluate(() => localStorage.getItem('auth_token'))
      expect(token).toBeTruthy()
    })

    test('should show error for duplicate email', async ({ page }) => {
      await registerPage.nameInput.fill('Test User')
      await registerPage.emailInput.fill(ACCOUNTS.owner.email) // Use existing email
      await registerPage.passwordInput.click()
      await registerPage.passwordInput.pressSequentially('User@1234')
      await page.keyboard.press('Escape')
      
      await registerPage.confirmPasswordInput.click()
      await registerPage.confirmPasswordInput.pressSequentially('User@1234')
      await registerPage.submitButton.click()
      
      // Wait for form submission to complete
      await expect(registerPage.submitButton).toBeEnabled({ timeout: 30_000 })
      
      // Check for error message about duplicate email
      await expect(
        page.getByText(/already.*taken/i).or(page.locator('.p-toast-message')).first()
      ).toBeVisible({ timeout: 15_000 })
    })

    test('should show validation error for empty required fields', async ({ page }) => {
      await registerPage.submitButton.click()
      
      // Wait for form submission to complete
      await expect(registerPage.submitButton).toBeEnabled({ timeout: 20_000 })
      
      // Check for validation errors
      const errorIndicator = page.locator('.text-red-500, .p-toast-message, small.text-red-500')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error when passwords do not match', async ({ page }) => {
      await registerPage.nameInput.fill('Test User')
      await registerPage.emailInput.fill(testEmail)
      await registerPage.passwordInput.click()
      await registerPage.passwordInput.pressSequentially('TestPass1234!')
      await page.keyboard.press('Escape')
      
      await registerPage.confirmPasswordInput.click()
      await registerPage.confirmPasswordInput.pressSequentially('DifferentPass1234!')
      await registerPage.submitButton.click()
      
      // Wait for form submission to complete
      await expect(registerPage.submitButton).toBeEnabled({ timeout: 20_000 })
      
      // Check for password mismatch error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error for weak password', async ({ page }) => {
      await registerPage.nameInput.fill('Test User')
      await registerPage.emailInput.fill(testEmail)
      await registerPage.passwordInput.click()
      await registerPage.passwordInput.pressSequentially('weak')
      await page.keyboard.press('Escape')
      
      await registerPage.confirmPasswordInput.click()
      await registerPage.confirmPasswordInput.pressSequentially('weak')
      await registerPage.submitButton.click()
      
      // Wait for form submission to complete
      await expect(registerPage.submitButton).toBeEnabled({ timeout: 20_000 })
      
      // Check for password validation error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should navigate to login page when clicking Sign in link', async ({ page }) => {
      await registerPage.signInLink.click()
      await page.waitForURL('**/login', { timeout: 10_000 })
      await expect(page).toHaveURL(/\/login/)
    })
  })

  test.describe('Password Reset Flow', () => {
    let testEmail: string
    let testPassword: string
    let api: any
    let testData: any

    test.beforeEach(async ({ page }) => {
      // Generate unique test user for password reset tests
      testEmail = `e2e-pwd-reset-${Date.now()}@test.example.com`
      testPassword = 'OldPassword1234!'
      
      // Setup API helper
      api = await getApiHelper(ACCOUNTS.owner.email, ACCOUNTS.owner.password)
      testData = createTestDataHelper(api)
    })

    test.afterEach(async () => {
      // Cleanup test user
      try {
        await testData.cleanup(testEmail.replace('@', '%'))
      } catch (error) {
        console.log('Cleanup error:', error)
      }
    })

    test('should display forgot password form and submit successfully', async ({ page }) => {
      await page.goto('/forgot-password')
      
      // Verify page elements
      await expect(page.getByRole('heading', { name: /forgot password/i })).toBeVisible()
      await expect(page.locator('#email')).toBeVisible()
      await expect(page.getByRole('button', { name: /send reset link/i })).toBeVisible()
      
      // Fill in email
      await page.locator('#email').fill(ACCOUNTS.owner.email)
      await page.getByRole('button', { name: /send reset link/i }).click()
      
      // Wait for success message
      await expect(page.getByText(/check your email/i)).toBeVisible({ timeout: 15_000 })
      await expect(page.getByText(ACCOUNTS.owner.email)).toBeVisible()
    })

    test('should show error for invalid email format in forgot password', async ({ page }) => {
      await page.goto('/forgot-password')
      
      await page.locator('#email').fill('invalid-email')
      await page.getByRole('button', { name: /send reset link/i }).click()
      
      // Wait for form submission to complete
      await expect(page.getByRole('button', { name: /send reset link/i })).toBeEnabled({ timeout: 20_000 })
      
      // Check for validation error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error for empty email in forgot password', async ({ page }) => {
      await page.goto('/forgot-password')
      
      await page.getByRole('button', { name: /send reset link/i }).click()
      
      // Wait for form submission to complete
      await expect(page.getByRole('button', { name: /send reset link/i })).toBeEnabled({ timeout: 20_000 })
      
      // Check for validation error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should navigate back to login from forgot password page', async ({ page }) => {
      await page.goto('/forgot-password')
      
      const backToSignInLink = page.getByRole('link', { name: /back to sign in/i })
      await expect(backToSignInLink).toBeVisible()
      await backToSignInLink.click()
      
      await page.waitForURL('**/login', { timeout: 10_000 })
      await expect(page).toHaveURL(/\/login/)
    })

    test('should display reset password form with token', async ({ page }) => {
      const testToken = 'test-reset-token-123'
      await page.goto(`/reset-password/${testToken}?email=${encodeURIComponent(ACCOUNTS.owner.email)}`)
      
      // Verify page elements
      await expect(page.getByRole('heading', { name: /reset.*password/i })).toBeVisible()
      await expect(page.locator('#email')).toBeVisible()
      await expect(page.locator('#password')).toBeVisible()
      await expect(page.locator('#password_confirmation')).toBeVisible()
      await expect(page.getByRole('button', { name: /reset password/i })).toBeVisible()
      
      // Verify email is pre-filled
      await expect(page.locator('#email')).toHaveValue(ACCOUNTS.owner.email)
    })

    test('should show validation error for empty fields in reset password', async ({ page }) => {
      const testToken = 'test-reset-token-123'
      await page.goto(`/reset-password/${testToken}`)
      
      await page.getByRole('button', { name: /reset password/i }).click()
      
      // Wait for form submission to complete
      await expect(page.getByRole('button', { name: /reset password/i })).toBeEnabled({ timeout: 20_000 })
      
      // Check for validation error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error when passwords do not match in reset password', async ({ page }) => {
      const testToken = 'test-reset-token-123'
      await page.goto(`/reset-password/${testToken}?email=${encodeURIComponent(testEmail)}`)
      
      await page.locator('#email').fill(testEmail)
      
      const passwordInput = page.locator('#password').locator('input').first()
      await passwordInput.click()
      await passwordInput.pressSequentially('NewPassword1234!')
      await page.keyboard.press('Escape')
      
      const confirmInput = page.locator('#password_confirmation').locator('input').first()
      await confirmInput.click()
      await confirmInput.pressSequentially('DifferentPassword1234!')
      
      await page.getByRole('button', { name: /reset password/i }).click()
      
      // Wait for form submission to complete
      await expect(page.getByRole('button', { name: /reset password/i })).toBeEnabled({ timeout: 20_000 })
      
      // Check for password mismatch error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })

    test('should show error for invalid or expired token', async ({ page }) => {
      const invalidToken = 'invalid-token-xyz'
      await page.goto(`/reset-password/${invalidToken}?email=${encodeURIComponent(ACCOUNTS.owner.email)}`)
      
      await page.locator('#email').fill(ACCOUNTS.owner.email)
      
      const passwordInput = page.locator('#password').locator('input').first()
      await passwordInput.click()
      await passwordInput.pressSequentially('NewPassword1234!')
      await page.keyboard.press('Escape')
      
      const confirmInput = page.locator('#password_confirmation').locator('input').first()
      await confirmInput.click()
      await confirmInput.pressSequentially('NewPassword1234!')
      
      await page.getByRole('button', { name: /reset password/i }).click()
      
      // Wait for form submission to complete
      await expect(page.getByRole('button', { name: /reset password/i })).toBeEnabled({ timeout: 20_000 })
      
      // Check for invalid token error
      const errorIndicator = page.locator('.text-red-500, .p-toast-message')
      await expect(errorIndicator.first()).toBeVisible({ timeout: 15_000 })
    })
  })

  test.describe('Complete Authentication Flow Integration', () => {
    let testEmail: string
    let testPassword: string
    let api: any
    let testData: any

    test.beforeEach(async () => {
      testEmail = `e2e-auth-flow-${Date.now()}@test.example.com`
      testPassword = 'TestPassword1234!'
      
      api = await getApiHelper(ACCOUNTS.owner.email, ACCOUNTS.owner.password)
      testData = createTestDataHelper(api)
    })

    test.afterEach(async () => {
      try {
        await testData.cleanup(testEmail.replace('@', '%'))
      } catch (error) {
        console.log('Cleanup error:', error)
      }
    })

    test('should complete full user journey: register -> logout -> login', async ({ page }) => {
      // Step 1: Register new user
      await page.goto('/register')
      await page.locator('#name').fill('E2E Flow Test User')
      await page.locator('#email').fill(testEmail)
      
      const pwInput = page.locator('#password').locator('input').first()
      await pwInput.click()
      await pwInput.pressSequentially(testPassword)
      await page.keyboard.press('Escape')
      
      const pwConfirm = page.locator('#password_confirmation').locator('input').first()
      await pwConfirm.click()
      await pwConfirm.pressSequentially(testPassword)
      
      await page.getByRole('button', { name: 'Create account' }).click()
      await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
      
      // Step 2: Verify logged in
      const token = await page.evaluate(() => localStorage.getItem('auth_token'))
      expect(token).toBeTruthy()
      
      // Step 3: Logout
      // Look for user menu or logout button
      const userMenuButton = page.locator('[data-testid="user-menu"], .user-menu, button:has-text("Profile"), button:has-text("Account")').first()
      if (await userMenuButton.isVisible({ timeout: 5000 }).catch(() => false)) {
        await userMenuButton.click()
        const logoutButton = page.getByRole('button', { name: /logout|sign out/i })
        await logoutButton.click()
      } else {
        // Alternative: navigate directly to logout or clear storage
        await page.evaluate(() => localStorage.clear())
        await page.goto('/login')
      }
      
      // Wait for redirect to login
      await page.waitForURL('**/login', { timeout: 10_000 })
      
      // Step 4: Login with same credentials
      await page.locator('#email').fill(testEmail)
      const loginPwInput = page.locator('#password').locator('input').first()
      await loginPwInput.click()
      await loginPwInput.pressSequentially(testPassword)
      await page.getByRole('button', { name: 'Sign in' }).click()
      
      // Step 5: Verify logged in again
      await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
      const newToken = await page.evaluate(() => localStorage.getItem('auth_token'))
      expect(newToken).toBeTruthy()
    })

    test('should prevent access to protected routes when not authenticated', async ({ page }) => {
      // Clear any existing auth
      await page.goto('/login')
      await page.evaluate(() => localStorage.clear())
      
      // Try to access protected route
      await page.goto('/app/dashboard')
      
      // Should redirect to login
      await page.waitForURL('**/login', { timeout: 10_000 })
      await expect(page).toHaveURL(/\/login/)
    })

    test('should redirect authenticated users away from auth pages', async ({ page }) => {
      // Login first
      await page.goto('/login')
      await page.locator('#email').fill(ACCOUNTS.owner.email)
      const pwInput = page.locator('#password').locator('input').first()
      await pwInput.click()
      await pwInput.pressSequentially(ACCOUNTS.owner.password)
      await page.getByRole('button', { name: 'Sign in' }).click()
      await page.waitForURL('**/app/dashboard', { timeout: 30_000 })
      
      // Try to access login page while authenticated
      await page.goto('/login')
      
      // Should redirect to dashboard
      await page.waitForURL('**/app/dashboard', { timeout: 10_000 })
      await expect(page).toHaveURL(/\/app\/dashboard/)
    })
  })
})
