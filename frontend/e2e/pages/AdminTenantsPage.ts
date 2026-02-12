import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class AdminTenantsPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/admin/tenants')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Tenants, text=Manage Tenants')).toBeVisible({ timeout: 10_000 })
  }

  getTenantRows(): Locator {
    return this.page.locator('.p-datatable-tbody tr, .divide-y > div')
  }

  async clickTenant(name: string) {
    await this.page.locator('tr, .cursor-pointer').filter({ hasText: name }).first().click()
    await this.page.waitForURL(/\/admin\/tenants\//)
  }
}
