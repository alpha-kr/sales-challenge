import { apiClient } from '@/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { AuthUser, LoginCredentials } from '@/types/auth'

export class AuthService {
  async getCsrfCookie(): Promise<void> {
    await apiClient.get('/sanctum/csrf-cookie', { baseURL: '/' })
  }

  async login(credentials: LoginCredentials): Promise<void> {
    await this.getCsrfCookie()
    await apiClient.post('/auth/login', credentials)
  }

  async logout(): Promise<void> {
    await apiClient.post('/auth/logout')
  }

  async getCurrentUser(): Promise<AuthUser> {
    const { data } = await apiClient.get<ApiSuccessResponse<AuthUser>>('/auth/user')
    return data.data
  }
}

export const authService = new AuthService()
