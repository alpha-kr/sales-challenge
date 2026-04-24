import { apiClient } from '@/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { DashboardStats } from '@/types/dashboard'

export class DashboardService {
  async getStats(): Promise<DashboardStats> {
    const { data } = await apiClient.get<ApiSuccessResponse<DashboardStats>>('/dashboard')
    return data.data
  }
}

export const dashboardService = new DashboardService()
