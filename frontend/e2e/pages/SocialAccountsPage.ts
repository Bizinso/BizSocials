import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class SocialAccountsPage extends BasePage {
  readonly connectButton = this.getButton('Connect Account')

  constructor(page: Page) {
    super(page)
  }

  async goto(workspaceId: string) {
    await this.page.goto(`/app/w/${workspaceId}/social-accounts`)
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Social Accounts')).toBeVisible({ timeout: 10_000 })
  }

  async clickConnect() {
    await this.connectButton.click()
    await this.page.locator('.p-dialog').first().waitFor({ state: 'visible' })
  }

  getAccountCards(): Locator {
    return this.page.locator('[class*="account-card"], .grid > div').filter({ has: this.page.locator('img, .pi-share-alt, [class*="platform"]') })
  }
}
