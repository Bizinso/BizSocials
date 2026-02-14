import { ApiHelper } from './api.helper'

/**
 * Test Data Helper
 * 
 * Provides convenient methods for seeding and cleaning up test data in E2E tests.
 */
export class TestDataHelper {
  constructor(private api: ApiHelper) {}

  /**
   * Create a complete test user with workspace
   */
  async createUserWithWorkspace(options: {
    email: string
    password: string
    name: string
    workspaceName?: string
    role?: 'owner' | 'admin' | 'member'
  }) {
    const result = await this.api.createTestUser({
      email: options.email,
      password: options.password,
      name: options.name,
      with_workspace: true,
      workspace_name: options.workspaceName || 'Test Workspace',
      role: options.role || 'owner',
    }) as any

    return {
      user: result.user,
      workspace: result.workspace,
    }
  }

  /**
   * Seed posts for a workspace
   */
  async seedPosts(workspaceId: string, userId: string, count: number = 10, status?: 'draft' | 'scheduled' | 'published') {
    return this.api.createTestPosts({
      workspace_id: workspaceId,
      user_id: userId,
      count,
      status,
    })
  }

  /**
   * Seed inbox items for a workspace
   */
  async seedInboxItems(workspaceId: string, count: number = 10, platform?: 'facebook' | 'instagram' | 'twitter') {
    return this.api.createTestInboxItems({
      workspace_id: workspaceId,
      count,
      platform,
    })
  }

  /**
   * Seed support tickets
   */
  async seedTickets(tenantId: string, userId: string, count: number = 5) {
    return this.api.createTestTickets({
      tenant_id: tenantId,
      user_id: userId,
      count,
    })
  }

  /**
   * Create a social account for testing
   */
  async createSocialAccount(
    workspaceId: string,
    platform: 'facebook' | 'instagram' | 'twitter' | 'linkedin',
    accountName: string = `Test ${platform} Account`,
  ) {
    return this.api.createTestSocialAccount({
      workspace_id: workspaceId,
      platform,
      account_name: accountName,
    })
  }

  /**
   * Cleanup test data by email pattern
   */
  async cleanup(emailPattern: string = 'e2e-test-%') {
    return this.api.cleanupTestData({
      email_pattern: emailPattern,
    })
  }

  /**
   * Cleanup specific workspace data
   */
  async cleanupWorkspace(workspaceId: string) {
    return this.api.cleanupTestData({
      workspace_id: workspaceId,
    })
  }

  /**
   * Reset entire test database (use with caution!)
   */
  async resetDatabase() {
    return this.api.resetTestDatabase()
  }
}

/**
 * Create a test data helper from an API helper
 */
export function createTestDataHelper(api: ApiHelper): TestDataHelper {
  return new TestDataHelper(api)
}
