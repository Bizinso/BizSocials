import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : 2,
  reporter: [['html', { open: 'never' }], ['list']],
  timeout: 60_000,
  expect: { timeout: 15_000 },

  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  globalSetup: './global-setup.ts',

  projects: [
    // Public pages — no auth needed
    {
      name: 'public',
      use: { ...devices['Desktop Chrome'] },
      testDir: './tests/public',
    },
    // Auth flows — no pre-existing auth (login, register, logout, guards)
    // Run serially to avoid hitting backend rate limits
    {
      name: 'auth',
      use: { ...devices['Desktop Chrome'] },
      testDir: './tests/auth',
      fullyParallel: false,
    },
    // Regenerate auth tokens after auth tests (which may create additional tokens)
    {
      name: 'auth-setup',
      testMatch: /auth\.setup\.ts/,
      testDir: './tests',
      dependencies: ['auth'],
    },
    // App tests — owner user authenticated
    {
      name: 'app',
      use: {
        ...devices['Desktop Chrome'],
        storageState: './e2e/.auth/owner.json',
      },
      testDir: './tests',
      testIgnore: ['**/auth/**', '**/public/**', '**/admin/**', '**/rbac/**'],
      dependencies: ['auth-setup'],
    },
    // Admin panel tests — super admin authenticated
    {
      name: 'admin',
      use: {
        ...devices['Desktop Chrome'],
        storageState: './e2e/.auth/superadmin.json',
      },
      testDir: './tests/admin',
      dependencies: ['auth-setup'],
    },
    // RBAC tests — manages own auth per test
    {
      name: 'rbac',
      use: { ...devices['Desktop Chrome'] },
      testDir: './tests/rbac',
      dependencies: ['auth-setup'],
    },
  ],

  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
    timeout: 30_000,
  },
})
