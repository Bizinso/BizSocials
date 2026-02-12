import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class PostListPage extends BasePage {
  readonly newPostButton = this.getButton('New Post')
  readonly searchInput = this.page.locator('input[placeholder*="Search"], input[type="search"]').first()

  constructor(page: Page) {
    super(page)
  }

  async goto(workspaceId: string) {
    await this.page.goto(`/app/w/${workspaceId}/posts`)
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Posts')).toBeVisible({ timeout: 10_000 })
  }

  async clickNewPost() {
    await this.newPostButton.click()
    await this.page.waitForURL(/\/posts\/new|\/posts\/create/)
  }

  async filterByStatus(status: string) {
    const statusDropdown = this.page.locator('.p-select, .p-dropdown').first()
    await this.selectOption(statusDropdown, status)
  }

  async searchPosts(query: string) {
    await this.searchInput.fill(query)
  }

  getPostRows(): Locator {
    return this.page.locator('[class*="post-"], .divide-y > div, tr').filter({ has: this.page.locator('.truncate, td') })
  }

  async goToPage(n: number) {
    await this.page.locator('.p-paginator-page').filter({ hasText: String(n) }).click()
  }
}
