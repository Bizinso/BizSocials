import { apiClient } from './client'
import type {
  KBArticleData,
  KBArticleSummaryData,
  KBCategoryData,
  KBSearchResultData,
  SubmitKBFeedbackRequest,
} from '@/types/kb'
import type { PaginatedResponse } from '@/types/api'

export const kbApi = {
  // ─── Public Articles ──────────────────────────────
  async listArticles(params?: Record<string, unknown>): Promise<PaginatedResponse<KBArticleSummaryData>> {
    const { data } = await apiClient.get('/kb/articles', { params })
    return data
  },

  async getFeaturedArticles(): Promise<KBArticleSummaryData[]> {
    const { data } = await apiClient.get('/kb/articles/featured')
    return data
  },

  async getPopularArticles(): Promise<KBArticleSummaryData[]> {
    const { data } = await apiClient.get('/kb/articles/popular')
    return data
  },

  async getArticle(slug: string): Promise<KBArticleData> {
    const { data } = await apiClient.get(`/kb/articles/${slug}`)
    return data
  },

  async submitFeedback(articleId: string, payload: SubmitKBFeedbackRequest): Promise<void> {
    await apiClient.post(`/kb/articles/${articleId}/feedback`, payload)
  },

  // ─── Public Categories ────────────────────────────
  async listCategories(): Promise<KBCategoryData[]> {
    const { data } = await apiClient.get('/kb/categories')
    return data
  },

  async getCategoryTree(): Promise<KBCategoryData[]> {
    const { data } = await apiClient.get('/kb/categories/tree')
    return data
  },

  async getCategory(slug: string): Promise<KBCategoryData> {
    const { data } = await apiClient.get(`/kb/categories/${slug}`)
    return data
  },

  // ─── Search ───────────────────────────────────────
  async search(query: string): Promise<KBSearchResultData[]> {
    const { data } = await apiClient.get('/kb/search', { params: { q: query } })
    return data
  },

  async searchSuggestions(query: string): Promise<string[]> {
    const { data } = await apiClient.get('/kb/search/suggest', { params: { q: query } })
    return data
  },

  async popularSearchTerms(): Promise<string[]> {
    const { data } = await apiClient.get('/kb/search/popular')
    return data
  },
}
