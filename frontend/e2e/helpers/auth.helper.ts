import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { API_URL, AUTH_TOKEN_KEY, ACCOUNTS } from './constants'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

interface Account {
  email: string
  password: string
}

interface StorageState {
  cookies: never[]
  origins: {
    origin: string
    localStorage: { name: string; value: string }[]
  }[]
}

async function loginViaApi(account: Account, endpoint = '/auth/login'): Promise<string> {
  const res = await fetch(`${API_URL}${endpoint}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email: account.email, password: account.password }),
  })
  if (!res.ok) {
    const body = await res.text()
    throw new Error(`Login failed for ${account.email}: ${res.status} ${body}`)
  }
  const json = await res.json()
  return json.data?.token ?? json.token
}

function buildStorageState(token: string, tokenKey = AUTH_TOKEN_KEY): StorageState {
  return {
    cookies: [],
    origins: [
      {
        origin: 'http://localhost:3000',
        localStorage: [{ name: tokenKey, value: token }],
      },
    ],
  }
}

export async function createAuthState(
  account: Account,
  outputPath: string,
  endpoint = '/auth/login',
  tokenKey = AUTH_TOKEN_KEY,
) {
  const token = await loginViaApi(account, endpoint)
  const state = buildStorageState(token, tokenKey)
  const dir = path.dirname(outputPath)
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true })
  fs.writeFileSync(outputPath, JSON.stringify(state, null, 2))
}

export async function createAllAuthStates() {
  const authDir = path.resolve(__dirname, '../.auth')
  if (!fs.existsSync(authDir)) fs.mkdirSync(authDir, { recursive: true })

  await Promise.all([
    createAuthState(ACCOUNTS.owner, path.join(authDir, 'owner.json')),
    createAuthState(ACCOUNTS.admin, path.join(authDir, 'admin.json')),
    createAuthState(ACCOUNTS.member, path.join(authDir, 'member.json')),
    createAuthState(ACCOUNTS.superAdmin, path.join(authDir, 'superadmin.json'), '/admin/auth/login', 'admin_token'),
  ])
}
