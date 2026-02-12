import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class PublicPages extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async gotoFeedback() {
    await this.page.goto('/feedback')
    await this.waitForContentLoaded()
  }

  async gotoRoadmap() {
    await this.page.goto('/roadmap')
    await this.waitForContentLoaded()
  }

  async gotoChangelog() {
    await this.page.goto('/changelog')
    await this.waitForContentLoaded()
  }

  async expectFeedbackLoaded() {
    await expect(this.page.locator('text=Feedback')).toBeVisible({ timeout: 10_000 })
  }

  async expectRoadmapLoaded() {
    await expect(this.page.locator('text=Roadmap')).toBeVisible({ timeout: 10_000 })
  }

  async expectChangelogLoaded() {
    await expect(this.page.locator('text=Changelog, text=Release Notes, text=What')).toBeVisible({ timeout: 10_000 })
  }
}
