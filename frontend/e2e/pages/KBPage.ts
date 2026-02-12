import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class KBPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/kb')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Knowledge Base, text=Help Center, text=Articles')).toBeVisible({ timeout: 10_000 })
  }

  getArticleCards(): Locator {
    return this.page.locator('[class*="article"], .grid > div, a').filter({ has: this.page.locator('h2, h3, .font-semibold, .font-medium') })
  }

  async search(query: string) {
    const searchInput = this.page.locator('input[placeholder*="Search"]').first()
    await searchInput.fill(query)
  }

  async clickArticle(title: string) {
    await this.page.locator('a, .cursor-pointer').filter({ hasText: title }).first().click()
    await this.page.waitForURL(/\/kb\//)
  }
}
