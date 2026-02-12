import { get, post, del } from './client'
import type {
  DataExportRequestData,
  DataDeletionRequestData,
  RequestExportRequest,
  RequestDeletionRequest,
} from '@/types/privacy'

export const privacyApi = {
  // Export requests
  listExportRequests() {
    return get<DataExportRequestData[]>('/privacy/export-requests')
  },

  requestExport(data?: RequestExportRequest) {
    return post<DataExportRequestData>('/privacy/export-requests', data)
  },

  downloadExportUrl(exportRequestId: string): string {
    return `/privacy/export-requests/${exportRequestId}/download`
  },

  // Deletion requests
  listDeletionRequests() {
    return get<DataDeletionRequestData[]>('/privacy/deletion-requests')
  },

  requestDeletion(data?: RequestDeletionRequest) {
    return post<DataDeletionRequestData>('/privacy/deletion-requests', data)
  },

  cancelDeletion(deletionRequestId: string) {
    return del(`/privacy/deletion-requests/${deletionRequestId}`)
  },
}
