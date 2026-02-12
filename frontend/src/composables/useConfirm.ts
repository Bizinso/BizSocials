import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm'

export function useConfirm() {
  const confirm = usePrimeConfirm()

  function requireConfirmation(options: {
    message: string
    header?: string
    icon?: string
    acceptLabel?: string
    rejectLabel?: string
    onAccept: () => void
    onReject?: () => void
  }) {
    confirm.require({
      message: options.message,
      header: options.header || 'Confirm',
      icon: options.icon || 'pi pi-exclamation-triangle',
      acceptLabel: options.acceptLabel || 'Yes',
      rejectLabel: options.rejectLabel || 'No',
      accept: options.onAccept,
      reject: options.onReject,
    })
  }

  function confirmDelete(options: { message?: string; onAccept: () => void }) {
    requireConfirmation({
      message: options.message || 'Are you sure you want to delete this? This action cannot be undone.',
      header: 'Delete Confirmation',
      icon: 'pi pi-trash',
      acceptLabel: 'Delete',
      onAccept: options.onAccept,
    })
  }

  return { requireConfirmation, confirmDelete }
}
