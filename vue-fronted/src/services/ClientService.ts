import { apiClient } from '@/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { Client, CreateClientPayload, UpdateClientPayload } from '@/types/client'

export class ClientService {
  async getAll(): Promise<Client[]> {
    const { data } = await apiClient.get<ApiSuccessResponse<Client[]>>('/clients')
    return data.data
  }

  async getById(id: number): Promise<Client> {
    const { data } = await apiClient.get<ApiSuccessResponse<Client>>(`/clients/${id}`)
    return data.data
  }

  async create(payload: CreateClientPayload): Promise<Client> {
    const { data } = await apiClient.post<ApiSuccessResponse<Client>>('/clients', payload)
    return data.data
  }

  async update(id: number, payload: UpdateClientPayload): Promise<Client> {
    const { data } = await apiClient.put<ApiSuccessResponse<Client>>(`/clients/${id}`, payload)
    return data.data
  }

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/clients/${id}`)
  }
}

export const clientService = new ClientService()
