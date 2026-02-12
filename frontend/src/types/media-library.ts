export interface MediaFolderData {
  id: string
  workspace_id: string
  parent_id: string | null
  name: string
  slug: string
  color: string | null
  sort_order: number
  created_at: string
  updated_at: string
  children?: MediaFolderData[]
}

export interface MediaLibraryItemData {
  id: string
  workspace_id: string
  uploaded_by_user_id: string | null
  folder_id: string | null
  file_name: string
  original_name: string
  mime_type: string
  file_size: number
  url: string
  thumbnail_url: string | null
  alt_text: string | null
  width: number | null
  height: number | null
  duration: number | null
  tags: string[] | null
  metadata: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export interface CreateFolderRequest {
  name: string
  parent_id?: string
  color?: string
}

export interface MoveItemsRequest {
  item_ids: string[]
  folder_id: string | null
}
