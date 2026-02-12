import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

test.describe('Team Members', () => {
  test('team members page loads', async ({ page }) => {
    await page.goto('/app/settings/team')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/team/)
  })

  test('page has team-related content', async ({ page }) => {
    await page.goto('/app/settings/team')
    await page.waitForLoadState('domcontentloaded')
    // Auto-wait for any content element
    const content = page.locator('.divide-y, .p-datatable, table, h1, h2, .p-card, button').first()
    await expect(content).toBeVisible({ timeout: 15_000 })
  })

  test('invite button is visible', async ({ page }) => {
    await page.goto('/app/settings/team')
    await page.waitForLoadState('domcontentloaded')
    const inviteBtn = page.getByRole('button', { name: /Invite/i })
    await expect(inviteBtn).toBeVisible({ timeout: 15_000 })
  })
})
