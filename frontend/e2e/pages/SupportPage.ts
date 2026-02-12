import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class SupportPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/app/support')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Support')).toBeVisible({ timeout: 10_000 })
  }

  async clickCreateTicket() {
    await this.getButton('Create Ticket').click()
  }

  getTickets(): Locator {
    return this.page.locator('[class*="ticket"], .divide-y > div, tr')
  }
}
