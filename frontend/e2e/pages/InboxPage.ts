import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class InboxPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto(workspaceId: string) {
    await this.page.goto(`/app/w/${workspaceId}/inbox`)
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Inbox')).toBeVisible({ timeout: 10_000 })
  }

  async filterByStatus(status: string) {
    const statusDropdown = this.page.locator('.p-select, .p-dropdown').first()
    await this.selectOption(statusDropdown, status)
  }

  async filterByType(type: string) {
    const typeDropdown = this.page.locator('.p-select, .p-dropdown').nth(1)
    await this.selectOption(typeDropdown, type)
  }

  async searchItems(query: string) {
    const search = this.page.locator('input[placeholder*="Search"]').first()
    await search.fill(query)
  }

  getItems(): Locator {
    return this.page.locator('[class*="inbox-item"], .divide-y > div')
  }

  async expectEmpty() {
    await expect(this.page.locator('text=No items, text=empty, text=No inbox')).toBeVisible()
  }
}
