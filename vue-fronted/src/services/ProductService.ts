import { apiClient } from '@/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { Product, CreateProductPayload, UpdateProductPayload } from '@/types/product'

export class ProductService {
  async getAll(): Promise<Product[]> {
    const { data } = await apiClient.get<ApiSuccessResponse<Product[]>>('/products')
    return data.data
  }

  async getById(id: number): Promise<Product> {
    const { data } = await apiClient.get<ApiSuccessResponse<Product>>(`/products/${id}`)
    return data.data
  }

  async create(payload: CreateProductPayload): Promise<Product> {
    const { data } = await apiClient.post<ApiSuccessResponse<Product>>('/products', payload)
    return data.data
  }

  async update(id: number, payload: UpdateProductPayload): Promise<Product> {
    const { data } = await apiClient.put<ApiSuccessResponse<Product>>(`/products/${id}`, payload)
    return data.data
  }

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/products/${id}`)
  }
}

export const productService = new ProductService()
