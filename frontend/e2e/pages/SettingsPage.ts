import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class SettingsPage extends BasePage {
  constructor(page: Page) {
    super(page)
  }

  async gotoProfile() {
    await this.page.goto('/app/settings/profile')
    await this.waitForContentLoaded()
  }

  async gotoTenant() {
    await this.page.goto('/app/settings/tenant')
    await this.waitForContentLoaded()
  }

  async gotoTeam() {
    await this.page.goto('/app/settings/team')
    await this.waitForContentLoaded()
  }

  async gotoNotifications() {
    await this.page.goto('/app/settings/notifications')
    await this.waitForContentLoaded()
  }

  async gotoSecurity() {
    await this.page.goto('/app/settings/security')
    await this.waitForContentLoaded()
  }

  async gotoAuditLog() {
    await this.page.goto('/app/settings/audit-log')
    await this.waitForContentLoaded()
  }

  async updateName(name: string) {
    await this.page.locator('#profile-name').fill(name)
  }

  async save() {
    await this.getButton('Save').click()
  }
}
