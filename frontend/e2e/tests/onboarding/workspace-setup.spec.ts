import { test, expect } from '@playwright/test'

test.describe('Onboarding — Workspace Setup', () => {
  // These tests require a seeded user with:
  // - Email verified
  // - Tenant status = ACTIVE
  // - Onboarding steps_completed includes ['account_created', 'email_verified', 'organization_completed']

  test('displays workspace creation form', async ({ page }) => {
    // TODO: Login as onboarding user and navigate
    await page.goto('/onboarding/workspace')
    await expect(page.getByText('Create your workspace')).toBeVisible()
    await expect(page.locator('#workspace-name')).toBeVisible()
    await expect(page.locator('#purpose')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Create workspace' })).toBeVisible()
  })

  test('shows validation errors for empty form', async ({ page }) => {
    await page.goto('/onboarding/workspace')
    await page.getByRole('button', { name: 'Create workspace' }).click()
    await expect(page.getByText('Workspace name is required')).toBeVisible()
    await expect(page.getByText('Purpose is required')).toBeVisible()
  })

  test('completes workspace creation and redirects to dashboard', async ({ page }) => {
    // TODO: Login as onboarding user
    await page.goto('/onboarding/workspace')
    await page.locator('#workspace-name').fill('Marketing Team')
    await page.locator('#purpose').selectOption('marketing')
    await page.getByLabel('Auto-approve').check()
    await page.getByRole('button', { name: 'Create workspace' }).click()

    // Should redirect to dashboard
    await expect(page).toHaveURL(/\/app\/dashboard/, { timeout: 10000 })
  })

  test('cannot re-submit after completion', async ({ page }) => {
    // TODO: Login as user who has already created workspace
    // Navigating to /onboarding/workspace should return existing workspace
    await page.goto('/onboarding/workspace')
  })

  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto('/onboarding/workspace')
    await expect(page).toHaveURL(/\/login/, { timeout: 5000 })
  })
})
