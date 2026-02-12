import { post } from './client'
import type {
  GenerateCaptionRequest,
  GenerateCaptionResponse,
  SuggestHashtagsRequest,
  SuggestHashtagsResponse,
  ImproveContentRequest,
  ImproveContentResponse,
  GenerateIdeasRequest,
  GenerateIdeasResponse,
} from '@/types/ai'

export const aiApi = {
  generateCaption(workspaceId: string, data: GenerateCaptionRequest) {
    return post<GenerateCaptionResponse>(`/workspaces/${workspaceId}/ai-assist/caption`, data)
  },

  suggestHashtags(workspaceId: string, data: SuggestHashtagsRequest) {
    return post<SuggestHashtagsResponse>(`/workspaces/${workspaceId}/ai-assist/hashtags`, data)
  },

  improveContent(workspaceId: string, data: ImproveContentRequest) {
    return post<ImproveContentResponse>(`/workspaces/${workspaceId}/ai-assist/improve`, data)
  },

  generateIdeas(workspaceId: string, data: GenerateIdeasRequest) {
    return post<GenerateIdeasResponse>(`/workspaces/${workspaceId}/ai-assist/ideas`, data)
  },
}
