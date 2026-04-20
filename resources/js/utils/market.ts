import api from '@/utils/api'
import type { MarketItem, GameCase } from '@/types/models'
import type { PaginatedResponse, ApiResponse } from '@/types/api'

export interface MarketFilters {
  page?: number
  per_page?: number
  category?: string | null
  wear?: string | null
  price_min?: number | null
  price_max?: number | null
  search?: string | null
  sort?: 'price_asc' | 'price_desc' | 'newest' | null
}

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
  filters: MarketFilters = {},
): Promise<PaginatedResponse<MarketItem>> {
  const params: Record<string, unknown> = {
    page: filters.page ?? 1,
    per_page: filters.per_page ?? 24,
  }
  if (filters.category) params.category = filters.category
  if (filters.wear) params.wear = filters.wear
  if (filters.price_min != null) params.price_min = filters.price_min
  if (filters.price_max != null) params.price_max = filters.price_max
  if (filters.search) params.search = filters.search
  const { data } = await api.get<PaginatedResponse<MarketItem>>('/market/items', { params })
  return data
}

export async function fetchMarketItem(id: number): Promise<MarketItem> {
  const { data } = await api.get<ApiResponse<MarketItem>>(`/market/items/${id}`)
  return data.data
}

export async function fetchCases(): Promise<GameCase[]> {
  const { data } = await api.get<{ data: GameCase[] }>('/cases')
  return data.data
}

/** Публичный список: активные кейсы с галочкой «на главной» (без авторизации). */
export async function fetchFeaturedCases(): Promise<GameCase[]> {
  const { data } = await api.get<{ data: GameCase[] }>('/cases/featured', { skipAuth: true })
  return data.data
}

export async function fetchCase(id: number): Promise<GameCase> {
  const { data } = await api.get<ApiResponse<GameCase>>(`/cases/${id}`)
  return data.data
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

export interface PurchaseCartDeal {
  id: number
  market_item_id: number
  price: string
  commission: string
  status: string
  expires_at: string | null
  created_at: string
}

export async function purchaseCart(marketItemIds: number[]): Promise<PurchaseCartDeal[]> {
  const { data } = await api.post<{ data: PurchaseCartDeal[] }>('/cart/purchase', {
    market_item_ids: marketItemIds,
  })
  return data.data
}
