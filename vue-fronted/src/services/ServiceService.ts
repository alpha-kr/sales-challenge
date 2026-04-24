import { apiClient } from '@/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { Service, CreateServicePayload, UpdateServicePayload } from '@/types/service'

export class ServiceService {
  async getAll(): Promise<Service[]> {
    const { data } = await apiClient.get<ApiSuccessResponse<Service[]>>('/services')
    return data.data
  }

  async getById(id: number): Promise<Service> {
    const { data } = await apiClient.get<ApiSuccessResponse<Service>>(`/services/${id}`)
    return data.data
  }

  async create(payload: CreateServicePayload): Promise<Service> {
    const { data } = await apiClient.post<ApiSuccessResponse<Service>>('/services', payload)
    return data.data
  }

  async update(id: number, payload: UpdateServicePayload): Promise<Service> {
    const { data } = await apiClient.put<ApiSuccessResponse<Service>>(`/services/${id}`, payload)
    return data.data
  }

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/services/${id}`)
  }
}

export const serviceService = new ServiceService()
