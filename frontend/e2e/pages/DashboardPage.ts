import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class DashboardPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/app/dashboard')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Welcome to BizSocials')).toBeVisible({ timeout: 10_000 })
  }

  getWorkspaceCards(): Locator {
    return this.page.locator('.cursor-pointer').filter({ has: this.page.locator('.truncate') })
  }

  async clickWorkspace(name: string) {
    await this.page.locator('.cursor-pointer').filter({ hasText: name }).first().click()
    await this.page.waitForURL(/\/app\/w\//)
  }

  async expectEmptyState() {
    await expect(this.page.locator('text=No workspaces yet')).toBeVisible()
  }
}
