import { test, expect } from '@playwright/test'

test.describe('Owner Access', () => {
  test.use({ storageState: 'e2e/.auth/owner.json' })

  test('owner can access workspace dashboard', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {})
    const card = page.locator('.cursor-pointer').first()
    await card.waitFor({ state: 'visible', timeout: 15_000 })
    await card.click()
    await page.waitForURL(/\/app\/w\//, { timeout: 10_000 })
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {})
    await expect(page.getByText('Overview of your workspace activity')).toBeVisible({ timeout: 15_000 })
  })

  test('owner can access posts page', async ({ page }) => {
    await page.goto('/app/dashboard')
    await page.waitForLoadState('domcontentloaded')
    await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 15_000 }).catch(() => {})
    await page.locator('.cursor-pointer').first().click()
    await page.waitForURL(/\/app\/w\//)
    const wsId = page.url().match(/\/app\/w\/([^/]+)/)?.[1]
    await page.goto(`/app/w/${wsId}/posts`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByRole('heading', { name: 'Posts' })).toBeVisible({ timeout: 10_000 })
  })

  test('owner can access settings', async ({ page }) => {
    await page.goto('/app/settings/profile')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/profile/)
  })

  test('owner can access billing', async ({ page }) => {
    await page.goto('/app/billing')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/billing/)
  })

  test('owner can access team management', async ({ page }) => {
    await page.goto('/app/settings/team')
    await page.waitForLoadState('domcontentloaded')
    await expect(page).toHaveURL(/\/settings\/team/)
  })
})
