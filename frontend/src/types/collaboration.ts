export interface ApprovalWorkflowData {
  id: string
  workspace_id: string
  name: string
  is_active: boolean
  is_default: boolean
  steps: ApprovalWorkflowStep[]
  created_at: string
}

export interface ApprovalWorkflowStep {
  order: number
  approver_user_ids: string[]
  require_all: boolean
}

export interface ApprovalStepDecisionData {
  id: string
  post_id: string
  workflow_id: string
  step_order: number
  approver_user_id: string
  approver_name?: string
  decision: 'approved' | 'rejected'
  comment: string | null
  decided_at: string
}

export interface WorkspaceTaskData {
  id: string
  workspace_id: string
  post_id: string | null
  title: string
  description: string | null
  assigned_to_user_id: string | null
  assigned_to_name?: string
  created_by_user_id: string
  created_by_name?: string
  status: 'todo' | 'in_progress' | 'done'
  due_date: string | null
  priority: 'low' | 'medium' | 'high'
  completed_at: string | null
  created_at: string
}

export interface PostNoteData {
  id: string
  post_id: string
  user_id: string
  user_name?: string
  content: string
  created_at: string
}

export interface PostRevisionData {
  id: string
  post_id: string
  user_id: string
  user_name?: string
  content_text: string | null
  content_variations: Record<string, unknown>[] | null
  hashtags: string[] | null
  revision_number: number
  change_summary: string | null
  created_at: string
}

export interface CreateWorkflowRequest {
  name: string
  steps: ApprovalWorkflowStep[]
  is_active?: boolean
}

export interface CreateTaskRequest {
  title: string
  description?: string
  assigned_to_user_id?: string
  post_id?: string
  due_date?: string
  priority?: string
}

export interface CreatePostNoteRequest {
  content: string
}
