import api from '@/utils/api'
import type { PaginatedResponse } from '@/types/api'
import type { SupportMessage, SupportTicket } from '@/types/models'

export async function fetchSupportTickets(
  page = 1,
  perPage = 20,
): Promise<PaginatedResponse<SupportTicket>> {
  const { data } = await api.get<PaginatedResponse<SupportTicket>>('/support/tickets', {
    params: { page, per_page: perPage },
  })
  return data
}

export async function createSupportTicket(subject?: string | null): Promise<SupportTicket> {
  const { data } = await api.post<{ data: SupportTicket }>('/support/tickets', {
    subject: subject?.trim() ? subject.trim() : null,
  })
  return data.data
}

export async function fetchSupportTicket(id: number): Promise<SupportTicket> {
  const { data } = await api.get<{ data: SupportTicket }>(`/support/tickets/${id}`)
  return data.data
}

export async function postSupportMessage(ticketId: number, body: string): Promise<SupportMessage> {
  const { data } = await api.post<{ data: SupportMessage }>(
    `/support/tickets/${ticketId}/messages`,
    { body },
  )
  return data.data
}
