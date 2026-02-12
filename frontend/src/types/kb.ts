export interface KBArticleData {
  id: string
  category_id: string
  category_name: string
  title: string
  slug: string
  excerpt: string | null
  content: string
  content_format: string
  featured_image: string | null
  article_type: string
  difficulty_level: string
  status: string
  is_featured: boolean
  view_count: number
  helpful_count: number
  not_helpful_count: number
  helpfulness_score: number
  meta_title: string | null
  meta_description: string | null
  published_at: string | null
  created_at: string
  updated_at: string
  tags: { id: string; name: string; slug: string }[]
}

export interface KBArticleSummaryData {
  id: string
  title: string
  slug: string
  excerpt: string | null
  category_id: string
  category_name: string
  article_type: string
  difficulty_level: string
  view_count: number
  is_featured: boolean
  published_at: string | null
}

export interface KBCategoryData {
  id: string
  name: string
  slug: string
  description: string | null
  icon: string | null
  color: string | null
  sort_order: number
  article_count: number
  parent_id: string | null
}

export interface KBSearchResultData {
  id: string
  title: string
  slug: string
  excerpt: string | null
  category_name: string
  article_type: string
  relevance_score: number
}

export interface KBFeedbackData {
  id: string
  article_id: string
  article_title: string
  is_helpful: boolean
  feedback_text: string | null
  feedback_category: string | null
  status: string
  reviewed_by: string | null
  reviewed_at: string | null
  admin_notes: string | null
  created_at: string
}

export interface SubmitKBFeedbackRequest {
  is_helpful: boolean
  category?: string
  comment?: string
  email?: string
}

export interface CreateKBArticleRequest {
  category_id: string
  title: string
  content: string
  excerpt?: string
  slug?: string
  content_format?: string
  article_type?: string
  difficulty_level?: string
  is_featured?: boolean
  featured_image?: string
  meta_title?: string
  meta_description?: string
  tag_ids?: string[]
}

export interface UpdateKBArticleRequest {
  category_id?: string
  title?: string
  content?: string
  excerpt?: string
  slug?: string
  article_type?: string
  difficulty_level?: string
  is_featured?: boolean
  featured_image?: string
  meta_title?: string
  meta_description?: string
  tag_ids?: string[]
}

export interface CreateKBCategoryRequest {
  name: string
  description?: string
  icon?: string
  color?: string
  parent_id?: string
  slug?: string
}

export interface UpdateKBCategoryRequest {
  name?: string
  description?: string
  icon?: string
  color?: string
  slug?: string
}
