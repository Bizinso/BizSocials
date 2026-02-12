import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class AdminDashboardPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/admin/dashboard')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Admin Dashboard, text=Platform Stats, text=Dashboard')).toBeVisible({ timeout: 10_000 })
  }

  getStatCards(): Locator {
    return this.page.locator('.text-center, .p-card').filter({ has: this.page.locator('.text-2xl, .text-3xl') })
  }
}
