import { test as setup } from '@playwright/test'
import { createAllAuthStates } from '../helpers/auth.helper'

setup('regenerate auth storage states', async () => {
  await createAllAuthStates()
})
