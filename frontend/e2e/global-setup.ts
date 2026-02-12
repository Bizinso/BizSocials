import { createAllAuthStates } from './helpers/auth.helper'
import { API_URL } from './helpers/constants'

export default async function globalSetup() {
  // Verify backend is reachable
  try {
    const res = await fetch(`${API_URL}/health`, { method: 'GET' })
    if (!res.ok) {
      console.warn(`⚠ Backend health check returned ${res.status} — tests may fail`)
    }
  } catch {
    console.warn('⚠ Backend not reachable — make sure Docker is running')
  }

  // Create auth storage states for all test accounts
  console.log('Creating auth storage states...')
  await createAllAuthStates()
  console.log('Auth storage states created')
}
