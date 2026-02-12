export interface TeamData {
  id: string
  workspace_id: string
  name: string
  description: string | null
  is_default: boolean
  member_count: number
  created_at: string
}

export interface TeamDetailData extends TeamData {
  members: TeamMemberData[]
}

export interface TeamMemberData {
  id: string
  user_id: string
  name: string
  email: string
  avatar_url: string | null
  joined_at: string
}

export interface CreateTeamRequest {
  name: string
  description?: string | null
  is_default?: boolean
}

export interface UpdateTeamRequest {
  name?: string
  description?: string | null
  is_default?: boolean
}

export interface AddTeamMemberRequest {
  user_id: string
}
