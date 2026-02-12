import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Create Support Ticket', () => {
  test('create ticket page/dialog loads', async ({ page }) => {
    await page.goto('/app/support')
    await page.waitForLoadState('domcontentloaded')
    // Wait for page to fully render - button label is "New Ticket"
    const createBtn = page.getByRole('button', { name: /New Ticket/i })
    await expect(createBtn).toBeVisible({ timeout: 25_000 })
    await createBtn.click()

    // Should navigate to /app/support/new or open a dialog
    const dialog = page.locator('.p-dialog')
    const heading = page.getByRole('heading', { name: /Create|New|Subject/i })
    const newUrl = page.locator('form, textarea, input[name="subject"]')
    await expect(dialog.or(heading).or(newUrl).first()).toBeVisible({ timeout: 15_000 })
  })
})
