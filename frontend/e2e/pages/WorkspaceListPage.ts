import { type Page, type Locator, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class WorkspaceListPage extends BasePage {
  readonly wsNameInput = this.page.locator('#ws-name')
  readonly wsDescInput = this.page.locator('#ws-desc')

  constructor(page: Page) {
    super(page)
  }

  async goto() {
    await this.page.goto('/app/workspaces')
    await this.waitForContentLoaded()
  }

  async expectLoaded() {
    await expect(this.page.locator('text=Workspaces')).toBeVisible({ timeout: 10_000 })
  }

  async openCreateDialog() {
    await this.getButton('Create Workspace').click()
    await this.page.locator('.p-dialog').first().waitFor({ state: 'visible' })
  }

  async fillName(name: string) {
    await this.wsNameInput.fill(name)
  }

  async fillDescription(desc: string) {
    await this.wsDescInput.fill(desc)
  }

  async submitCreate() {
    const dialog = this.page.locator('.p-dialog').first()
    await dialog.locator('.p-button').filter({ hasText: /create/i }).click()
  }

  getWorkspaceCards(): Locator {
    return this.page.locator('.cursor-pointer, [class*="workspace-card"]')
  }
}
