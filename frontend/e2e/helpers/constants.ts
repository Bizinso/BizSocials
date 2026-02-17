export const BASE_URL = 'http://localhost:3000'
export const API_URL = 'http://localhost:8080/api/v1'
export const AUTH_TOKEN_KEY = 'auth_token'

export const ACCOUNTS = {
  owner: { email: 'john.owner@acme.example.com', password: 'password' },
  admin: { email: 'jane.admin@acme.example.com', password: 'password' },
  member: { email: 'bob.member@acme.example.com', password: 'password' },
  superAdmin: { email: 'admin@bizinso.com', password: 'BizS0c!als@2026!' },
} as const

export const TIMEOUTS = {
  navigation: 10_000,
  api: 15_000,
  animation: 500,
} as const

let emailCounter = 0
export function uniqueEmail(): string {
  emailCounter++
  return `e2e-test-${Date.now()}-${emailCounter}@test.example.com`
}
