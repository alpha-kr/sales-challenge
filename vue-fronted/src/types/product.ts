export interface Product {
  id: number
  name: string
  price: number
  stock: number
  created_at: string
  updated_at: string
}

export interface CreateProductPayload {
  name: string
  price: number
  stock: number
}

export type UpdateProductPayload = Partial<CreateProductPayload>
