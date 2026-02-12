import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class NotFoundPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async expectVisible() {
    await expect(this.page.locator('text=404, text=not found, text=Not Found').first()).toBeVisible({ timeout: 5_000 })
  }
}
