import api from '@/utils/api'
import type { PaginatedResponse } from '@/types/api'
import type { Transaction } from '@/types/models'

export async function fetchTransactions(
  page = 1,
  perPage = 20,
): Promise<PaginatedResponse<Transaction>> {
  const { data } = await api.get<PaginatedResponse<Transaction>>('/transactions', {
    params: { page, per_page: perPage },
  })
  return data
}
