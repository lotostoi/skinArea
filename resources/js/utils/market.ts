import api from '@/utils/api'
import type { MarketItem, GameCase, CaseOpening } from '@/types/models'
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
    steam_user_id?: string
    steam_app_id?: number
    steam_context_id?: number
    inventory_game?: string
    only_tradable?: boolean
    steam_reported_total?: number | null
    steam_raw_assets?: number
    mapped_items?: number
  }
}

export interface CaseOpeningFeedEntry {
  id: number
  won_item_price: string
  status: string
  created_at: string
  user: { id: number | null; username: string | null; avatar_url: string | null }
  case: { id: number | null; name: string | null }
  item: { id: number | null; name: string | null; image_url: string | null; rarity: string | null; rarity_color: string | null }
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

export interface SiteSettingsPayload {
  show_demo_data: boolean
}

export async function fetchSiteSettings(): Promise<SiteSettingsPayload> {
  const { data } = await api.get<{ data: SiteSettingsPayload }>('/site', { skipAuth: true })
  return data.data
}

export async function fetchCases(): Promise<GameCase[]> {
  const { data } = await api.get<{ data: GameCase[] }>('/cases')
  return data.data
}

export async function fetchCaseLiveFeed(): Promise<CaseOpeningFeedEntry[]> {
  const { data } = await api.get<{ data: CaseOpeningFeedEntry[] }>('/cases/live', { skipAuth: true })
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

export async function openCase(
  caseId: number,
  payload: { quantity?: number; fast?: boolean } = {},
): Promise<CaseOpening | CaseOpening[]> {
  const { data } = await api.post<{ data: CaseOpening | CaseOpening[] }>(`/cases/${caseId}/open`, payload)
  return data.data
}

export interface CaseInventoryParams {
  page?: number
  per_page?: number
  status?: string
  sort?: 'created_at' | 'won_item_price'
  order?: 'asc' | 'desc'
  search?: string
  case_id?: number
}

export interface CaseInventorySummary {
  total_items: number
  total_value: string
  in_inventory_items: number
  in_inventory_value: string
  sold_items: number
  withdrawn_items: number
  used_in_upgrade_items: number
}

export async function fetchCaseInventory(
  params: CaseInventoryParams = {},
): Promise<PaginatedResponse<CaseOpening>> {
  const { data } = await api.get<PaginatedResponse<CaseOpening>>('/profile/case-inventory', {
    params,
  })
  return data
}

export async function fetchCaseInventorySummary(): Promise<CaseInventorySummary> {
  const { data } = await api.get<{ data: CaseInventorySummary }>('/profile/case-inventory/summary')
  return data.data
}

export async function sellCaseOpening(id: number): Promise<CaseOpening> {
  const { data } = await api.post<ApiResponse<CaseOpening>>(`/profile/case-inventory/${id}/sell`)
  return data.data
}

export async function withdrawCaseOpening(
  id: number,
  payload: { trade_url?: string } = {},
): Promise<CaseOpening> {
  const { data } = await api.post<ApiResponse<CaseOpening>>(
    `/profile/case-inventory/${id}/withdraw`,
    payload,
  )
  return data.data
}
