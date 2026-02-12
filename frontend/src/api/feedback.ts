import { apiClient } from './client'
import type {
  FeedbackData,
  FeedbackCommentData,
  SubmitFeedbackRequest,
  AddFeedbackCommentRequest,
  VoteFeedbackRequest,
  RoadmapItemData,
  ReleaseNoteData,
  SubscribeChangelogRequest,
} from '@/types/feedback'
import type { PaginatedResponse } from '@/types/api'

export const feedbackApi = {
  // ─── Feedback ─────────────────────────────────────
  async list(params?: Record<string, unknown>): Promise<PaginatedResponse<FeedbackData>> {
    const { data } = await apiClient.get('/feedback', { params })
    return data
  },

  async submit(payload: SubmitFeedbackRequest): Promise<FeedbackData> {
    const { data } = await apiClient.post('/feedback', payload)
    return data
  },

  async getPopular(): Promise<FeedbackData[]> {
    const { data } = await apiClient.get('/feedback/popular')
    return data
  },

  async get(feedbackId: string): Promise<FeedbackData> {
    const { data } = await apiClient.get(`/feedback/${feedbackId}`)
    return data
  },

  async getComments(feedbackId: string): Promise<FeedbackCommentData[]> {
    const { data } = await apiClient.get(`/feedback/${feedbackId}/comments`)
    return data
  },

  async addComment(feedbackId: string, payload: AddFeedbackCommentRequest): Promise<FeedbackCommentData> {
    const { data } = await apiClient.post(`/feedback/${feedbackId}/comments`, payload)
    return data
  },

  async vote(feedbackId: string, payload?: VoteFeedbackRequest): Promise<void> {
    await apiClient.post(`/feedback/${feedbackId}/vote`, payload)
  },

  async removeVote(feedbackId: string): Promise<void> {
    await apiClient.delete(`/feedback/${feedbackId}/vote`)
  },

  // ─── Roadmap ──────────────────────────────────────
  async listRoadmap(params?: Record<string, unknown>): Promise<RoadmapItemData[]> {
    const { data } = await apiClient.get('/roadmap', { params })
    return data
  },

  async getRoadmapItem(itemId: string): Promise<RoadmapItemData> {
    const { data } = await apiClient.get(`/roadmap/${itemId}`)
    return data
  },

  // ─── Changelog ────────────────────────────────────
  async listChangelog(params?: Record<string, unknown>): Promise<PaginatedResponse<ReleaseNoteData>> {
    const { data } = await apiClient.get('/changelog', { params })
    return data
  },

  async getChangelogEntry(slug: string): Promise<ReleaseNoteData> {
    const { data } = await apiClient.get(`/changelog/${slug}`)
    return data
  },

  async subscribeChangelog(payload: SubscribeChangelogRequest): Promise<void> {
    await apiClient.post('/changelog/subscribe', payload)
  },

  async unsubscribeChangelog(payload: { email: string }): Promise<void> {
    await apiClient.post('/changelog/unsubscribe', payload)
  },
}
