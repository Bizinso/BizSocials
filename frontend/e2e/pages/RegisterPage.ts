import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class RegisterPage extends BasePage {
  readonly nameInput = this.page.locator('#name')
  readonly emailInput = this.page.locator('#email')
  readonly passwordInput = this.page.locator('#password input').first()
  readonly confirmPasswordInput = this.page.locator('#password_confirmation input').first()
  readonly submitButton = this.getButton('Create account')
  readonly signInLink = this.page.locator('a', { hasText: 'Sign in' })

  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/register')
  }

  async register(name: string, email: string, password: string) {
    await this.nameInput.fill(name)
    await this.emailInput.fill(email)
    await this.passwordInput.fill(password)
    await this.confirmPasswordInput.fill(password)
    await this.submitButton.click()
  }

  async expectOnRegisterPage() {
    await expect(this.nameInput).toBeVisible()
    await expect(this.submitButton).toBeVisible()
  }
}
