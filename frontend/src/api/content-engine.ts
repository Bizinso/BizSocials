import { get, post, put, del, getPaginated } from './client'
import type {
  ContentCategoryData, CreateContentCategoryRequest,
  HashtagGroupData, CreateHashtagGroupRequest,
  ShortLinkData, CreateShortLinkRequest, ShortLinkStatsData,
  RssFeedData, RssFeedItemData, CreateRssFeedRequest,
  EvergreenRuleData, EvergreenPostPoolData, CreateEvergreenRuleRequest,
} from '@/types/content-engine'
import type { PaginationParams } from '@/types/api'

const base = (wsId: string) => `/workspaces/${wsId}`

// Content Categories
export const contentCategoryApi = {
  list(workspaceId: string) {
    return get<ContentCategoryData[]>(`${base(workspaceId)}/content-categories`)
  },
  create(workspaceId: string, data: CreateContentCategoryRequest) {
    return post<ContentCategoryData>(`${base(workspaceId)}/content-categories`, data)
  },
  update(workspaceId: string, id: string, data: Partial<CreateContentCategoryRequest>) {
    return put<ContentCategoryData>(`${base(workspaceId)}/content-categories/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/content-categories/${id}`)
  },
  reorder(workspaceId: string, categoryIds: string[]) {
    return post<void>(`${base(workspaceId)}/content-categories/reorder`, { category_ids: categoryIds })
  },
}

// Hashtag Groups
export const hashtagGroupApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<HashtagGroupData>(`${base(workspaceId)}/hashtag-groups`, params as Record<string, unknown>)
  },
  create(workspaceId: string, data: CreateHashtagGroupRequest) {
    return post<HashtagGroupData>(`${base(workspaceId)}/hashtag-groups`, data)
  },
  update(workspaceId: string, id: string, data: Partial<CreateHashtagGroupRequest>) {
    return put<HashtagGroupData>(`${base(workspaceId)}/hashtag-groups/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/hashtag-groups/${id}`)
  },
}

// Link Shortener
export const shortLinkApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<ShortLinkData>(`${base(workspaceId)}/short-links`, params as Record<string, unknown>)
  },
  create(workspaceId: string, data: CreateShortLinkRequest) {
    return post<ShortLinkData>(`${base(workspaceId)}/short-links`, data)
  },
  get(workspaceId: string, id: string) {
    return get<ShortLinkData>(`${base(workspaceId)}/short-links/${id}`)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/short-links/${id}`)
  },
  stats(workspaceId: string, id: string) {
    return get<ShortLinkStatsData>(`${base(workspaceId)}/short-links/${id}/stats`)
  },
}

// RSS Feeds
export const rssFeedApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<RssFeedData>(`${base(workspaceId)}/rss-feeds`, params as Record<string, unknown>)
  },
  create(workspaceId: string, data: CreateRssFeedRequest) {
    return post<RssFeedData>(`${base(workspaceId)}/rss-feeds`, data)
  },
  get(workspaceId: string, id: string) {
    return get<RssFeedData>(`${base(workspaceId)}/rss-feeds/${id}`)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/rss-feeds/${id}`)
  },
  items(workspaceId: string, id: string, params?: PaginationParams) {
    return getPaginated<RssFeedItemData>(`${base(workspaceId)}/rss-feeds/${id}/items`, params as Record<string, unknown>)
  },
  fetch(workspaceId: string, id: string) {
    return post<void>(`${base(workspaceId)}/rss-feeds/${id}/fetch`)
  },
}

// Evergreen
export const evergreenApi = {
  list(workspaceId: string, params?: PaginationParams) {
    return getPaginated<EvergreenRuleData>(`${base(workspaceId)}/evergreen-rules`, params as Record<string, unknown>)
  },
  create(workspaceId: string, data: CreateEvergreenRuleRequest) {
    return post<EvergreenRuleData>(`${base(workspaceId)}/evergreen-rules`, data)
  },
  get(workspaceId: string, id: string) {
    return get<EvergreenRuleData>(`${base(workspaceId)}/evergreen-rules/${id}`)
  },
  update(workspaceId: string, id: string, data: Partial<CreateEvergreenRuleRequest>) {
    return put<EvergreenRuleData>(`${base(workspaceId)}/evergreen-rules/${id}`, data)
  },
  delete(workspaceId: string, id: string) {
    return del(`${base(workspaceId)}/evergreen-rules/${id}`)
  },
  buildPool(workspaceId: string, id: string) {
    return post<{ count: number }>(`${base(workspaceId)}/evergreen-rules/${id}/build-pool`)
  },
  pool(workspaceId: string, id: string, params?: PaginationParams) {
    return getPaginated<EvergreenPostPoolData>(`${base(workspaceId)}/evergreen-rules/${id}/pool`, params as Record<string, unknown>)
  },
}
