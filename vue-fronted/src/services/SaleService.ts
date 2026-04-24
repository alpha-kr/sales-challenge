import { apiClient } from '@/api'
import type { ApiSuccessResponse, PaginationMeta } from '@/types/api'
import type { Sale, CreateSalePayload } from '@/types/sale'

interface SaleFilters {
  client_id?: number
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export interface SalePage {
  data: Sale[]
  meta: PaginationMeta
}

export class SaleService {
  async getAll(filters?: SaleFilters): Promise<SalePage> {
    const { data } = await apiClient.get<ApiSuccessResponse<Sale[]>>('/sales', { params: filters })
    return { data: data.data, meta: data.meta as PaginationMeta }
  }

  async getOne(id: number): Promise<Sale> {
    const { data } = await apiClient.get<ApiSuccessResponse<Sale>>(`/sales/${id}`)
    return data.data
  }

  async create(payload: CreateSalePayload): Promise<Sale> {
    const { data } = await apiClient.post<ApiSuccessResponse<Sale>>('/sales', payload)
    return data.data
  }
}

export const saleService = new SaleService()
