import type { BalanceType, UserRole } from './enums'

export interface User {
  id: number
  steam_id: string
  username: string
  avatar_url: string | null
  trade_url: string | null
  /** Только у текущего пользователя в /user; у продавца в маркете не приходит */
  email?: string | null
  /** ISO8601 или null — только у текущего пользователя */
  email_verified_at?: string | null
  /** Ссылка на страницу Steam, где показывается и копируется trade URL */
  steam_trade_privacy_url: string
  role: UserRole
  balances?: Balance[]
}

export interface Balance {
  type: BalanceType
  amount: string
  updated_at: string
}

export interface MarketItem {
  id: number
  asset_id: string
  name: string
  image_url: string | null
  wear: string
  float_value: string | number | null
  rarity: string
  category: string
  price: string
  status: string
  created_at?: string
  seller?: Pick<User, 'id' | 'username' | 'avatar_url' | 'steam_id'>
}

export interface Deal {
  id: number
  buyer_id?: number
  seller_id?: number
  market_item_id?: number
  price: string
  commission: string
  status: string
  trade_offer_id: string | null
  cancelled_reason: string | null
  expires_at: string | null
  created_at?: string
  market_item?: MarketItem
}

export interface GameCaseCategory {
  id: number
  name: string
  sort_order: number
}

export interface GameCase {
  id: number
  name: string
  image_url: string
  price: string
  is_active: boolean
  is_featured_on_home?: boolean
  sort_order?: number
  category_id?: number | null
  category?: GameCaseCategory | null
  levels?: CaseLevel[]
}

export interface CaseLevel {
  id: number
  case_id: number
  level: number
  name: string
  chance: string
  items?: CaseItem[]
}

export interface CaseItem {
  id: number
  case_level_id: number
  name: string
  image_url: string
  price: string
  wear: string
  rarity: string
}

export interface CaseOpening {
  id: number
  user_id: number
  case_id: number
  case_item_id: number
  cost: string
  won_item_price: string
  status: string
  created_at: string
  case_item?: CaseItem
}

export interface Transaction {
  id: number
  type: string
  amount: string
  balance_after: string
  metadata: Record<string, unknown> | null
  created_at: string
}

export type SupportTicketStatus = 'open' | 'closed'

export interface SupportMessage {
  id: number
  body: string
  is_staff: boolean
  created_at: string
}

export interface SupportTicket {
  id: number
  subject: string | null
  status: SupportTicketStatus
  created_at: string
  updated_at: string
  messages?: SupportMessage[]
}
