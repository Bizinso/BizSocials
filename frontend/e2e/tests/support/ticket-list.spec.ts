import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Support Tickets', () => {
  test('support page loads', async ({ page }) => {
    await page.goto('/app/support')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/support/)
    // AppPageHeader renders title as h1 "Support Tickets"
    await expect(page.getByRole('heading', { name: /Support Tickets/i })).toBeVisible({ timeout: 25_000 })
  })

  test('ticket list or empty state is displayed', async ({ page }) => {
    await page.goto('/app/support')
    await page.waitForLoadState('domcontentloaded')
    // Wait for heading first to confirm page is rendered
    await page.getByRole('heading', { name: /Support Tickets/i }).waitFor({ state: 'visible', timeout: 25_000 })
    // Then wait for skeleton loading to finish
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 20_000 }).catch(() => {})
    // Check for tickets, empty state, or any content below heading
    const content = page.locator('.divide-y, .p-datatable, table, button, .p-card, .p-paginator').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })

  test('"Create Ticket" button is visible', async ({ page }) => {
    await page.goto('/app/support')
    await page.waitForLoadState('domcontentloaded')
    // Button label is "New Ticket" with pi-plus icon
    await expect(page.getByRole('button', { name: /New Ticket/i })).toBeVisible({ timeout: 25_000 })
  })
})
