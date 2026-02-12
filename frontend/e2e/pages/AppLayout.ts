import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class AppLayout extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async navigateTo(label: string) {
    const menuItem = this.page.locator('ul button, ul a').filter({ hasText: label }).first()
    await menuItem.click()
    await this.page.waitForLoadState('domcontentloaded')
  }

  async expectSidebarItem(label: string) {
    await expect(this.page.locator('ul button, ul a').filter({ hasText: label }).first()).toBeVisible()
  }

  async logout() {
    // Open user menu dropdown
    const userMenu = this.page.locator('[class*="user-menu"], [class*="UserMenu"], button').filter({ hasText: /logout|sign out/i }).first()
    if (await userMenu.isVisible()) {
      await userMenu.click()
    } else {
      // Try avatar/initial button
      const avatar = this.page.locator('.rounded-full').first()
      await avatar.click()
      await this.page.locator('text=Logout, text=Sign out').first().click()
    }
  }
}
