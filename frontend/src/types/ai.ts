export interface GenerateCaptionRequest {
  topic: string
  platform: string
  tone?: string
}

export interface GenerateCaptionResponse {
  caption: string
}

export interface SuggestHashtagsRequest {
  content: string
  platform: string
  count?: number
}

export interface SuggestHashtagsResponse {
  hashtags: string[]
}

export interface ImproveContentRequest {
  content: string
  instruction: string
}

export interface ImproveContentResponse {
  content: string
}

export interface GenerateIdeasRequest {
  topic: string
  platform: string
  count?: number
}

export interface GenerateIdeasResponse {
  ideas: string[]
}
