export interface SaleDetail {
  id: number
  product_id: number | null
  service_id: number | null
  quantity: number
  unit_price: number
}

export interface Sale {
  id: number
  client_id: number
  client: { id: number; name: string; tax_id: string } | null
  daily_sequence: number
  total: number | string
  created_at: string
  details: SaleDetail[]
}

export interface SaleItemPayload {
  product_id?: number | null
  service_id?: number | null
  quantity: number
}

export interface CreateSalePayload {
  client_id: number
  items: SaleItemPayload[]
}

export interface CartItem {
  type: 'product' | 'service'
  id: number
  name: string
  price: number
  quantity: number
  required_product_id?: number | null
}
