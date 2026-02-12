import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class WorkspaceDashboardPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto(workspaceId: string) {
    await this.page.goto(`/app/w/${workspaceId}`)
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Overview of your workspace activity')).toBeVisible({ timeout: 10_000 })
  }

  getStatCard(label: string) {
    return this.page.locator('.text-center').filter({ hasText: label }).first()
  }

  async getTotalPosts(): Promise<string> {
    return (await this.getStatCard('Total Posts').locator('.text-2xl').textContent()) || '0'
  }

  async getSocialAccounts(): Promise<string> {
    return (await this.getStatCard('Social Accounts').locator('.text-2xl').textContent()) || '0'
  }

  async getInboxUnread(): Promise<string> {
    return (await this.getStatCard('Unread Inbox').locator('.text-2xl').textContent()) || '0'
  }

  async getMemberCount(): Promise<string> {
    return (await this.getStatCard('Members').locator('.text-2xl').textContent()) || '0'
  }

  async clickQuickAction(label: string) {
    await this.getButton(label).click()
  }
}
