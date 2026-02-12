import type { Page } from '@playwright/test'

/**
 * Wait for PrimeVue loading indicators to disappear.
 */
export async function waitForLoadingToFinish(page: Page, timeout = 15_000) {
  await Promise.all([
    page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout }).catch(() => {}),
    page.locator('.p-progress-spinner').first().waitFor({ state: 'detached', timeout }).catch(() => {}),
    page.locator('[data-loading="true"]').first().waitFor({ state: 'detached', timeout }).catch(() => {}),
  ])
}

/**
 * Wait for API response to complete (network idle approach).
 */
export async function waitForApiResponse(page: Page, urlPattern: string | RegExp, timeout = 10_000) {
  return page.waitForResponse(
    (response) => {
      const url = response.url()
      if (typeof urlPattern === 'string') return url.includes(urlPattern)
      return urlPattern.test(url)
    },
    { timeout },
  )
}
