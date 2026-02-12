import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class AdminUsersPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/admin/users')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Users, text=Manage Users')).toBeVisible({ timeout: 10_000 })
  }

  getUserRows(): Locator {
    return this.page.locator('.p-datatable-tbody tr, .divide-y > div')
  }
}
