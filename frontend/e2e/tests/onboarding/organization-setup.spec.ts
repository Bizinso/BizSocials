import { test, expect } from '@playwright/test'

test.describe('Onboarding — Organization Setup', () => {
  // These tests require a seeded user with:
  // - Email verified
  // - Tenant status = ACTIVE
  // - Onboarding current_step = 'organization_completed' (the step to complete)
  // - steps_completed includes ['account_created', 'email_verified']

  test('displays organization setup form', async ({ page }) => {
    // TODO: Login as onboarding user and navigate
    await page.goto('/onboarding/org')
    await expect(page.getByText('Set up your organization')).toBeVisible()
    await expect(page.locator('#org-name')).toBeVisible()
    await expect(page.locator('#timezone')).toBeVisible()
    await expect(page.locator('#industry')).toBeVisible()
    await expect(page.locator('#country')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Continue' })).toBeVisible()
  })

  test('shows validation errors for empty form', async ({ page }) => {
    await page.goto('/onboarding/org')
    await page.getByRole('button', { name: 'Continue' }).click()
    await expect(page.getByText('Organization name is required')).toBeVisible()
    await expect(page.getByText('Industry is required')).toBeVisible()
    await expect(page.getByText('Country is required')).toBeVisible()
  })

  test('completes organization setup and redirects to workspace', async ({ page }) => {
    // TODO: Login as onboarding user
    await page.goto('/onboarding/org')
    await page.locator('#org-name').fill('Acme Corp')
    await page.locator('#timezone').selectOption('UTC')
    await page.locator('#industry').selectOption('technology')
    await page.locator('#country').selectOption('US')
    await page.getByRole('button', { name: 'Continue' }).click()

    // Should redirect to workspace setup
    await expect(page).toHaveURL(/\/onboarding\/workspace/, { timeout: 10000 })
  })

  test('cannot re-submit after completion', async ({ page }) => {
    // TODO: Login as user who has already completed org setup
    // Navigating to /onboarding/org should either redirect or show completed state
    await page.goto('/onboarding/org')
    // The idempotent API will return success but not change data
  })

  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto('/onboarding/org')
    await expect(page).toHaveURL(/\/login/, { timeout: 5000 })
  })
})
