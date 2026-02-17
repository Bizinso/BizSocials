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

  async clickFirstItem() {
    const firstItem = this.getItems().first()
    await firstItem.waitFor({ state: 'visible', timeout: 10_000 })
    await firstItem.click()
  }

  async expectDetailView() {
    await expect(this.page.getByRole('button', { name: /Back to Inbox/i })).toBeVisible({ timeout: 10_000 })
  }

  async typeReply(message: string) {
    const replyTextarea = this.page.locator('textarea[placeholder*="reply" i], textarea[placeholder*="Type" i]').first()
    await replyTextarea.fill(message)
  }

  async sendReply() {
    const sendButton = this.page.getByRole('button', { name: /Send Reply/i })
    await sendButton.click()
  }

  async expectReplySent() {
    await expect(this.page.locator('text=/Reply sent/i, .p-toast-message')).toBeVisible({ timeout: 10_000 })
  }

  async expectReplyInThread(message: string) {
    await expect(this.page.locator(`text="${message}"`)).toBeVisible({ timeout: 10_000 })
  }

  async clickBackToInbox() {
    const backButton = this.page.getByRole('button', { name: /Back to Inbox/i })
    await backButton.click()
  }

  async expectEmpty() {
    await expect(this.page.locator('text=No items, text=empty, text=No inbox')).toBeVisible()
  }
}
