import { type Page, expect } from '@playwright/test'
import { BasePage } from './BasePage'

export class PostEditorPage extends BasePage {
  readonly contentTextarea = this.page.locator('textarea[placeholder*="share"], textarea').first()
  readonly saveDraftButton = this.getButton('Save Draft')
  readonly submitButton = this.getButton('Submit')
  readonly publishButton = this.getButton('Publish')
  readonly scheduleButton = this.getButton('Schedule')

  constructor(page: Page) {
    super(page)
  }

  async goto(workspaceId: string) {
    await this.page.goto(`/app/w/${workspaceId}/posts/new`)
    await this.waitForContentLoaded()
  }

  async gotoEdit(workspaceId: string, postId: string) {
    await this.page.goto(`/app/w/${workspaceId}/posts/${postId}/edit`)
    await this.waitForContentLoaded()
  }

  async fillContent(text: string) {
    await this.contentTextarea.fill(text)
  }

  async saveDraft() {
    await this.saveDraftButton.click()
  }

  async submit() {
    await this.submitButton.click()
  }

  async publish() {
    await this.publishButton.click()
  }

  async expectLoaded() {
    await expect(this.contentTextarea).toBeVisible({ timeout: 10_000 })
  }
}
