export interface ApiResponse<T> {
  data: T
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface ApiError {
  message: string
  errors: Record<string, string[]>
}

export interface AuthExchangeResponse {
  data: {
    token: string
    token_type: string
    user: import('./models').User
  }
}
