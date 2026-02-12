import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class BillingPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async gotoOverview() {
    await this.page.goto('/app/billing')
    await this.waitForContentLoaded()
  }

  async gotoPlans() {
    await this.page.goto('/app/billing/plans')
    await this.waitForContentLoaded()
  }

  async gotoInvoices() {
    await this.page.goto('/app/billing/invoices')
    await this.waitForContentLoaded()
  }

  async gotoPaymentMethods() {
    await this.page.goto('/app/billing/payment-methods')
    await this.waitForContentLoaded()
  }

  async expectOverviewLoaded() {
    await expect(this.page.locator('text=Billing')).toBeVisible({ timeout: 10_000 })
  }

  async toggleBillingPeriod() {
    await this.page.locator('.p-toggleswitch, .p-inputswitch').first().click()
  }

  getPlanCards(): Locator {
    return this.page.locator('[class*="plan-card"], .grid > div').filter({ has: this.page.locator('text=/\\$\\d+/') })
  }
}
