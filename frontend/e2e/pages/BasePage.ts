import { type Page, type Locator, expect } from '@playwright/test'

export abstract class BasePage {
  constructor(protected page: Page) {}

  async waitForContentLoaded() {
    await Promise.all([
      this.page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {}),
      this.page.locator('.p-progress-spinner').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {}),
    ])
  }

  async expectToast(text: string | RegExp) {
    const toast = this.page.locator('.p-toast-message')
    await expect(toast.first()).toBeVisible({ timeout: 5_000 })
    await expect(toast.first()).toContainText(text)
  }

  async confirmDialogAccept() {
    const dialog = this.page.locator('.p-confirmdialog, .p-dialog')
    await dialog.first().waitFor({ state: 'visible' })
    await dialog.first().locator('.p-button').filter({ hasText: /yes|confirm|delete|ok/i }).first().click()
  }

  async confirmDialogReject() {
    const dialog = this.page.locator('.p-confirmdialog, .p-dialog')
    await dialog.first().waitFor({ state: 'visible' })
    await dialog.first().locator('.p-button').filter({ hasText: /no|cancel/i }).first().click()
  }

  getButton(label: string): Locator {
    return this.page.locator('.p-button, button').filter({ hasText: label }).first()
  }

  getInput(id: string): Locator {
    return this.page.locator(`#${id}`)
  }

  async selectOption(trigger: Locator, optionText: string) {
    await trigger.click()
    const overlay = this.page.locator('.p-select-overlay, .p-dropdown-panel')
    await overlay.waitFor({ state: 'visible' })
    await overlay.locator('.p-select-option, .p-dropdown-item').filter({ hasText: optionText }).first().click()
  }
}
