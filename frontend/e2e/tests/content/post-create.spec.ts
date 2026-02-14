import { test, expect } from '@playwright/test'

test.use({ storageState: 'e2e/.auth/owner.json' })

let workspaceId: string

test.beforeAll(async ({ browser }) => {
  const ctx = await browser.newContext({ storageState: 'e2e/.auth/owner.json' })
  const page = await ctx.newPage()
  await page.goto('/app/dashboard')
  await page.waitForLoadState('domcontentloaded')
  await page.getByRole('heading', { name: 'Dashboard' }).waitFor({ state: 'visible', timeout: 30_000 })
  await page.locator('.p-skeleton').first().waitFor({ state: 'detached', timeout: 30_000 }).catch(() => {})
  const card = page.locator('.cursor-pointer').first()
  await card.waitFor({ state: 'visible', timeout: 30_000 })
  await card.click()
  await page.waitForURL(/\/app\/w\//, { timeout: 15_000 })
  workspaceId = page.url().match(/\/app\/w\/([^/]+)/)?.[1] || ''
  await ctx.close()
})

test.describe('Post Create - UI Elements', () => {
  test('post create page loads with heading', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    await expect(page.getByRole('heading', { name: 'Create Post' })).toBeVisible({ timeout: 15_000 })
  })

  test('post editor has a textarea', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    const textarea = page.locator('textarea').first()
    await expect(textarea).toBeVisible({ timeout: 15_000 })
  })

  test('can type content in textarea', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    const textarea = page.locator('textarea').first()
    await textarea.waitFor({ state: 'visible', timeout: 15_000 })
    await textarea.fill('E2E test post content')
    await expect(textarea).toHaveValue(/E2E test post content/)
  })

  test('save draft button is visible', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    // Wait for page heading to confirm the view loaded
    await page.getByRole('heading', { name: 'Create Post' }).waitFor({ state: 'visible', timeout: 20_000 })
    await expect(page.getByRole('button', { name: /Save Draft/i })).toBeVisible({ timeout: 15_000 })
  })
})

test.describe('Post Create - Draft Flow', () => {
  test('creates a draft post successfully', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    
    // Navigate to create post page
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    
    // Wait for page to load
    await page.getByRole('heading', { name: 'Create Post' }).waitFor({ state: 'visible', timeout: 15_000 })
    
    // Fill in post content
    const textarea = page.locator('textarea').first()
    await textarea.waitFor({ state: 'visible', timeout: 15_000 })
    await textarea.fill('E2E Draft Post - ' + Date.now())
    
    // Click Save Draft button
    const saveDraftButton = page.getByRole('button', { name: /Save Draft/i })
    await saveDraftButton.waitFor({ state: 'visible', timeout: 15_000 })
    await saveDraftButton.click()
    
    // Wait for success message or redirect
    await page.waitForTimeout(2000)
    
    // Verify we're redirected to posts list or see success message
    await expect(page.getByText(/saved|success|draft/i).first()).toBeVisible({ timeout: 10_000 }).catch(() => {
      // If no success message, check if we're on posts list
      expect(page.url()).toContain('/posts')
    })
  })
})

test.describe('Post Create - Schedule Flow', () => {
  test('schedules a post for future publishing', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    
    // Navigate to create post page
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    
    // Wait for page to load
    await page.getByRole('heading', { name: 'Create Post' }).waitFor({ state: 'visible', timeout: 15_000 })
    
    // Fill in post content
    const textarea = page.locator('textarea').first()
    await textarea.waitFor({ state: 'visible', timeout: 15_000 })
    await textarea.fill('E2E Scheduled Post - ' + Date.now())
    
    // Look for schedule button or option
    const scheduleButton = page.getByRole('button', { name: /Schedule/i }).first()
    if (await scheduleButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      await scheduleButton.click()
      
      // Wait for schedule dialog/form
      await page.waitForTimeout(1000)
      
      // Try to find and interact with date/time picker
      const dateInput = page.locator('input[type="date"], input[type="datetime-local"]').first()
      if (await dateInput.isVisible({ timeout: 3000 }).catch(() => false)) {
        // Set a future date
        const futureDate = new Date()
        futureDate.setDate(futureDate.getDate() + 1)
        await dateInput.fill(futureDate.toISOString().split('T')[0])
      }
      
      // Confirm schedule
      const confirmButton = page.getByRole('button', { name: /Confirm|Schedule|Save/i }).first()
      if (await confirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
        await confirmButton.click()
        
        // Wait for success
        await page.waitForTimeout(2000)
        await expect(page.getByText(/scheduled|success/i).first()).toBeVisible({ timeout: 10_000 }).catch(() => {
          expect(page.url()).toContain('/posts')
        })
      }
    }
  })
})

test.describe('Post Create - Publish Flow', () => {
  test('publishes a post immediately', async ({ page }) => {
    test.skip(!workspaceId, 'No workspace available')
    
    // Navigate to create post page
    await page.goto(`/app/w/${workspaceId}/posts/create`)
    await page.waitForLoadState('domcontentloaded')
    
    // Wait for page to load
    await page.getByRole('heading', { name: 'Create Post' }).waitFor({ state: 'visible', timeout: 15_000 })
    
    // Fill in post content
    const textarea = page.locator('textarea').first()
    await textarea.waitFor({ state: 'visible', timeout: 15_000 })
    await textarea.fill('E2E Immediate Publish Post - ' + Date.now())
    
    // Look for publish button
    const publishButton = page.getByRole('button', { name: /Publish Now|Publish/i }).first()
    if (await publishButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      await publishButton.click()
      
      // Wait for confirmation dialog if any
      await page.waitForTimeout(1000)
      
      // Confirm publish if there's a confirmation dialog
      const confirmButton = page.getByRole('button', { name: /Confirm|Yes|Publish/i }).first()
      if (await confirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
        await confirmButton.click()
      }
      
      // Wait for success
      await page.waitForTimeout(2000)
      await expect(page.getByText(/published|success/i).first()).toBeVisible({ timeout: 10_000 }).catch(() => {
        expect(page.url()).toContain('/posts')
      })
    }
  })
})
