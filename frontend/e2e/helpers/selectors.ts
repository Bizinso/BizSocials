import type { Page, Locator } from '@playwright/test'

/**
 * PrimeVue-aware selector helpers.
 * PrimeVue renders complex DOM — these helpers target common patterns.
 */
export const PV = {
  /** Select a dropdown option by visible text */
  async selectOption(page: Page, trigger: Locator, optionText: string) {
    await trigger.click()
    // PrimeVue Select renders overlay panel
    const overlay = page.locator('.p-select-overlay, .p-dropdown-panel')
    await overlay.waitFor({ state: 'visible' })
    await overlay.locator('.p-select-option, .p-dropdown-item').filter({ hasText: optionText }).first().click()
  },

  /** Get a DataTable row by text content */
  tableRow(page: Page, text: string): Locator {
    return page.locator('.p-datatable-tbody tr, .p-datatable-row-group').filter({ hasText: text }).first()
  },

  /** Click a paginator page */
  async goToPage(page: Page, pageNumber: number) {
    await page.locator(`.p-paginator-page`).filter({ hasText: String(pageNumber) }).click()
  },

  /** Get dialog by header text */
  dialog(page: Page, title?: string): Locator {
    if (title) {
      return page.locator('.p-dialog').filter({ hasText: title }).first()
    }
    return page.locator('.p-dialog').first()
  },

  /** Toggle a switch */
  async toggleSwitch(locator: Locator) {
    await locator.locator('.p-toggleswitch, .p-inputswitch').click()
  },

  /** Get toast message text */
  toastMessage(page: Page): Locator {
    return page.locator('.p-toast-message-text .p-toast-detail, .p-toast-message-content')
  },

  /** Confirm dialog — click accept or reject */
  async confirmAccept(page: Page) {
    await page.locator('.p-confirmdialog .p-confirmdialog-accept-button, .p-dialog .p-button').filter({ hasText: /yes|confirm|delete|ok/i }).first().click()
  },

  async confirmReject(page: Page) {
    await page.locator('.p-confirmdialog .p-confirmdialog-reject-button').click()
  },
}
