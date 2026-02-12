import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class LoginPage extends BasePage {
  readonly emailInput = this.page.locator('#email')
  readonly passwordInput = this.page.locator('#password input').first()
  readonly submitButton = this.getButton('Sign in')
  readonly signUpLink = this.page.locator('a', { hasText: 'Sign up' })
  readonly forgotPasswordLink = this.page.locator('a', { hasText: 'Forgot password?' })

  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/login')
  }

  async login(email: string, password: string) {
    await this.emailInput.fill(email)
    await this.passwordInput.click()
    await this.passwordInput.pressSequentially(password)
    await this.submitButton.click()
  }

  async expectOnLoginPage() {
    await expect(this.emailInput).toBeVisible()
    await expect(this.submitButton).toBeVisible()
  }

  async expectError(text: string | RegExp) {
    const errorMsg = this.page.locator('.text-red-500, .p-toast-message')
    await expect(errorMsg.first()).toBeVisible({ timeout: 5_000 })
    await expect(errorMsg.first()).toContainText(text)
  }
}
