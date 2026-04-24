import type { Product } from './product'

export interface Service {
  id: number
  name: string
  price: number
  disabled_at: string | null
  required_product: Product | null
  created_at: string
  updated_at: string
}

export interface CreateServicePayload {
  name: string
  price: number
  required_product_id?: number | null
}

export type UpdateServicePayload = Partial<CreateServicePayload>
