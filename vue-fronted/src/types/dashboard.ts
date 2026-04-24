export interface ProductStock {
  name: string
  stock: number
}

export interface MonthlySales {
  month: string
  label: string
  total: number
  count: number
}

export interface SalesByCategory {
  name: string
  total: number
}

export interface DashboardStats {
  products_stock: ProductStock[]
  sales_by_month: MonthlySales[]
  sales_by_product: SalesByCategory[]
  sales_by_service: SalesByCategory[]
}
