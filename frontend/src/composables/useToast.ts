import { useToast as usePrimeToast } from 'primevue/usetoast'

export function useToast() {
  const toast = usePrimeToast()

  function success(message: string, detail?: string) {
    toast.add({ severity: 'success', summary: message, detail, life: 3000 })
  }

  function error(message: string, detail?: string) {
    toast.add({ severity: 'error', summary: message, detail, life: 5000 })
  }

  function warn(message: string, detail?: string) {
    toast.add({ severity: 'warn', summary: message, detail, life: 4000 })
  }

  function info(message: string, detail?: string) {
    toast.add({ severity: 'info', summary: message, detail, life: 3000 })
  }

  return { success, error, warn, info }
}
