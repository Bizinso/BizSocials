import { API_URL } from './constants'

export class ApiHelper {
  constructor(private token: string) {}

  private async request<T = unknown>(method: string, path: string, body?: unknown): Promise<T> {
    const res = await fetch(`${API_URL}${path}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${this.token}`,
      },
      body: body ? JSON.stringify(body) : undefined,
    })
    if (!res.ok) {
      const text = await res.text()
      throw new Error(`API ${method} ${path} failed: ${res.status} ${text}`)
    }
    const json = await res.json()
    return json.data ?? json
  }

  get<T = unknown>(path: string) {
    return this.request<T>('GET', path)
  }

  post<T = unknown>(path: string, body?: unknown) {
    return this.request<T>('POST', path, body)
  }

  put<T = unknown>(path: string, body?: unknown) {
    return this.request<T>('PUT', path, body)
  }

  del<T = unknown>(path: string) {
    return this.request<T>('DELETE', path)
  }

  async listWorkspaces(): Promise<{ id: string; name: string }[]> {
    return this.get('/workspaces')
  }

  async createWorkspace(data: { name: string; description?: string }) {
    return this.post('/workspaces', data)
  }

  async createPost(workspaceId: string, data: { content?: string; status?: string }) {
    return this.post(`/workspaces/${workspaceId}/posts`, data)
  }

  async deletePost(workspaceId: string, postId: string) {
    return this.del(`/workspaces/${workspaceId}/posts/${postId}`)
  }
}

export async function getApiHelper(email: string, password: string, endpoint = '/auth/login'): Promise<ApiHelper> {
  const res = await fetch(`${API_URL}${endpoint}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  })
  if (!res.ok) throw new Error(`API login failed: ${res.status}`)
  const json = await res.json()
  const token = json.data?.token ?? json.token
  return new ApiHelper(token)
}
