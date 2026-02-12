import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class AnalyticsPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async goto(workspaceId: string) {
    await this.page.goto(`/app/w/${workspaceId}/analytics`)
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Impressions, text=Analytics')).toBeVisible({ timeout: 10_000 })
  }

  getMetricCard(label: string) {
    return this.page.locator('.grid > div, .p-card').filter({ hasText: label }).first()
  }

  async expectChartsVisible() {
    await expect(this.page.locator('canvas, [class*="chart"], text=Engagement Trend').first()).toBeVisible({ timeout: 10_000 })
  }
}
