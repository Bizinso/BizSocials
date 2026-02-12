import { get, post, put, del, upload, getPaginated } from './client'
import type {
  PostData,
  PostDetailData,
  PostTargetData,
  PostMediaData,
  CreatePostRequest,
  UpdatePostRequest,
  SchedulePostRequest,
  ApprovalDecisionData,
  ApprovePostRequest,
  RejectPostRequest,
} from '@/types/content'
import type { PaginationParams } from '@/types/api'

export const contentApi = {
  // Posts
  listPosts(workspaceId: string, params?: PaginationParams & { status?: string }) {
    return getPaginated<PostData>(`/workspaces/${workspaceId}/posts`, params as Record<string, unknown>)
  },

  getPost(workspaceId: string, postId: string) {
    return get<PostDetailData>(`/workspaces/${workspaceId}/posts/${postId}`)
  },

  createPost(workspaceId: string, data: CreatePostRequest) {
    return post<PostData>(`/workspaces/${workspaceId}/posts`, data)
  },

  updatePost(workspaceId: string, postId: string, data: UpdatePostRequest) {
    return put<PostData>(`/workspaces/${workspaceId}/posts/${postId}`, data)
  },

  deletePost(workspaceId: string, postId: string) {
    return del(`/workspaces/${workspaceId}/posts/${postId}`)
  },

  submitPost(workspaceId: string, postId: string) {
    return post<PostData>(`/workspaces/${workspaceId}/posts/${postId}/submit`)
  },

  schedulePost(workspaceId: string, postId: string, data: SchedulePostRequest) {
    return post<PostData>(`/workspaces/${workspaceId}/posts/${postId}/schedule`, data)
  },

  publishPost(workspaceId: string, postId: string) {
    return post<PostData>(`/workspaces/${workspaceId}/posts/${postId}/publish`)
  },

  cancelPost(workspaceId: string, postId: string) {
    return post<PostData>(`/workspaces/${workspaceId}/posts/${postId}/cancel`)
  },

  duplicatePost(workspaceId: string, postId: string) {
    return post<PostData>(`/workspaces/${workspaceId}/posts/${postId}/duplicate`)
  },

  // Media
  listMedia(workspaceId: string, postId: string) {
    return get<PostMediaData[]>(`/workspaces/${workspaceId}/posts/${postId}/media`)
  },

  uploadMedia(workspaceId: string, postId: string, formData: FormData) {
    return upload<PostMediaData>(`/workspaces/${workspaceId}/posts/${postId}/media`, formData)
  },

  updateMediaOrder(workspaceId: string, postId: string, order: Record<string, number>) {
    return put<void>(`/workspaces/${workspaceId}/posts/${postId}/media/order`, order)
  },

  deleteMedia(workspaceId: string, postId: string, mediaId: string) {
    return del(`/workspaces/${workspaceId}/posts/${postId}/media/${mediaId}`)
  },

  // Targets
  listTargets(workspaceId: string, postId: string) {
    return get<PostTargetData[]>(`/workspaces/${workspaceId}/posts/${postId}/targets`)
  },

  updateTargets(workspaceId: string, postId: string, data: { social_account_ids: string[] }) {
    return put<PostTargetData[]>(`/workspaces/${workspaceId}/posts/${postId}/targets`, data)
  },

  deleteTarget(workspaceId: string, postId: string, targetId: string) {
    return del(`/workspaces/${workspaceId}/posts/${postId}/targets/${targetId}`)
  },

  // Approvals
  listApprovals(workspaceId: string, params?: PaginationParams) {
    return getPaginated<PostData>(`/workspaces/${workspaceId}/approvals`, params as Record<string, unknown>)
  },

  approvePost(workspaceId: string, postId: string, data?: ApprovePostRequest) {
    return post<ApprovalDecisionData>(`/workspaces/${workspaceId}/posts/${postId}/approve`, data)
  },

  rejectPost(workspaceId: string, postId: string, data: RejectPostRequest) {
    return post<ApprovalDecisionData>(`/workspaces/${workspaceId}/posts/${postId}/reject`, data)
  },

  approvalHistory(workspaceId: string, postId: string) {
    return get<ApprovalDecisionData[]>(`/workspaces/${workspaceId}/posts/${postId}/approval-history`)
  },
}
