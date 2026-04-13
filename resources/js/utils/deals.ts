import api from '@/utils/api'
import type { Deal } from '@/types/models'
import type { PaginatedResponse } from '@/types/api'

export async function fetchDeals(page = 1, perPage = 20): Promise<PaginatedResponse<Deal>> {
  const { data } = await api.get<PaginatedResponse<Deal>>('/deals', {
    params: { page, per_page: perPage },
  })
  return data
}
