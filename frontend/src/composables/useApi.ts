import { ref } from 'vue'
import { parseApiError, type AppError } from '@/utils/error-handler'

export function useApi<T>(apiFn: (...args: unknown[]) => Promise<T>) {
  const data = ref<T | null>(null) as { value: T | null }
  const loading = ref(false)
  const error = ref<AppError | null>(null)

  async function execute(...args: unknown[]) {
    loading.value = true
    error.value = null
    try {
      data.value = await apiFn(...args)
      return data.value
    } catch (e) {
      error.value = parseApiError(e)
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    data,
    loading,
    error,
    execute,
  }
}
