import { get, post, put, del, getPaginated } from './client'
import type { MediaLibraryItemData, MediaFolderData, CreateFolderRequest, MoveItemsRequest } from '@/types/media-library'
import type { PaginationParams } from '@/types/api'

const base = (wsId: string) => `/workspaces/${wsId}`

export const mediaLibraryApi = {
  list(workspaceId: string, params?: PaginationParams & { folder_id?: string; type?: string; search?: string }) {
    return getPaginated<MediaLibraryItemData>(`${base(workspaceId)}/media-library`, params as Record<string, unknown>)
  },

  get(workspaceId: string, itemId: string) {
    return get<MediaLibraryItemData>(`${base(workspaceId)}/media-library/${itemId}`)
  },

  upload(workspaceId: string, file: File, folderId?: string, altText?: string) {
    const formData = new FormData()
    formData.append('file', file)
    if (folderId) formData.append('folder_id', folderId)
    if (altText) formData.append('alt_text', altText)
    return post<MediaLibraryItemData>(`${base(workspaceId)}/media-library`, formData)
  },

  update(workspaceId: string, itemId: string, data: { alt_text?: string; tags?: string[] }) {
    return put<MediaLibraryItemData>(`${base(workspaceId)}/media-library/${itemId}`, data)
  },

  delete(workspaceId: string, itemId: string) {
    return del(`${base(workspaceId)}/media-library/${itemId}`)
  },

  listFolders(workspaceId: string) {
    return get<MediaFolderData[]>(`${base(workspaceId)}/media-library-folders`)
  },

  createFolder(workspaceId: string, data: CreateFolderRequest) {
    return post<MediaFolderData>(`${base(workspaceId)}/media-library-folders`, data)
  },

  moveItems(workspaceId: string, data: MoveItemsRequest) {
    return post<void>(`${base(workspaceId)}/media-library/move`, data)
  },
}
