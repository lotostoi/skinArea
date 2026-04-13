import api from '@/utils/api'
import type { MarketItem } from '@/types/models'
import type { PaginatedResponse } from '@/types/api'

export interface SteamInventoryItem {
  asset_id: string
  name: string
  image_url: string | null
  wear: string
  float_value: string | null
  rarity: string
  category: string
  tradable: boolean
}

export interface SteamInventoryApiResponse {
  data: SteamInventoryItem[]
  meta: {
    current_page: number
    last_page: number
    total: number
    steam_app_id?: number
    inventory_game?: string
    only_tradable?: boolean
    steam_reported_total?: number | null
    steam_raw_assets?: number
    mapped_items?: number
  }
}

export async function fetchSteamInventory(): Promise<SteamInventoryApiResponse> {
  const { data } = await api.get<SteamInventoryApiResponse>('/inventory/steam')
  return data
}

export async function fetchMarketItems(
  page = 1,
  perPage = 20,
): Promise<PaginatedResponse<MarketItem>> {
  const { data } = await api.get<PaginatedResponse<MarketItem>>('/market/items', {
    params: { page, per_page: perPage },
  })
  return data
}

export async function fetchProfileListings(
  page = 1,
  perPage = 20,
): Promise<PaginatedResponse<MarketItem>> {
  const { data } = await api.get<PaginatedResponse<MarketItem>>('/profile/listings', {
    params: { page, per_page: perPage },
  })
  return data
}

export async function fetchProfileSold(
  page = 1,
  perPage = 20,
): Promise<PaginatedResponse<MarketItem>> {
  const { data } = await api.get<PaginatedResponse<MarketItem>>('/profile/sold', {
    params: { page, per_page: perPage },
  })
  return data
}

export async function createMarketListing(payload: {
  asset_id: string
  price: number
}): Promise<MarketItem> {
  const { data } = await api.post<{ data: MarketItem }>('/market/items', payload)
  return data.data
}

export async function removeMarketListing(id: number): Promise<MarketItem> {
  const { data } = await api.delete<{ data: MarketItem }>(`/market/items/${id}`)
  return data.data
}
