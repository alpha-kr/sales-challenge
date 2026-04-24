export interface ApiSuccessResponse<T> {
  success: true
  data: T
  message: string
  meta?: PaginationMeta
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ApiErrorResponse {
  success: false
  error: {
    code: ApiErrorCode
    message: string
    details?: Record<string, string[]>
  }
}

export type ApiErrorCode =
  | 'INTERNAL_ERROR'
  | 'UNAUTHORIZED'
  | 'FORBIDDEN'
  | 'RESOURCE_NOT_FOUND'
  | 'VALIDATION_FAILED'
  | 'INSUFFICIENT_STOCK'
  | 'DAILY_LIMIT_REACHED'
  | 'SERVICE_DEPENDENCY_FAILED'
  | 'TAX_ID_ALREADY_EXISTS'
