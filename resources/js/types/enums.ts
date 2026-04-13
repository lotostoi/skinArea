export enum BalanceType {
  Main = 'main',
  Hold = 'hold',
}

export enum DealStatus {
  Created = 'created',
  Paid = 'paid',
  TradeSent = 'trade_sent',
  TradeAccepted = 'trade_accepted',
  Completed = 'completed',
  Cancelled = 'cancelled',
}

export enum MarketItemStatus {
  Active = 'active',
  Reserved = 'reserved',
  Sold = 'sold',
  Cancelled = 'cancelled',
}

export enum ItemWear {
  FN = 'FN',
  MW = 'MW',
  FT = 'FT',
  WW = 'WW',
  BS = 'BS',
}

export enum ItemRarity {
  Consumer = 'consumer',
  Industrial = 'industrial',
  MilSpec = 'mil_spec',
  Restricted = 'restricted',
  Classified = 'classified',
  Covert = 'covert',
  Contraband = 'contraband',
}

export enum ItemCategory {
  Knives = 'knives',
  Gloves = 'gloves',
  Pistols = 'pistols',
  Rifles = 'rifles',
  SMGs = 'smgs',
  Shotguns = 'shotguns',
  MachineGuns = 'machine_guns',
  Other = 'other',
}

export enum TransactionType {
  Deposit = 'deposit',
  Withdrawal = 'withdrawal',
  Purchase = 'purchase',
  Sale = 'sale',
  CaseOpen = 'case_open',
  CaseSell = 'case_sell',
  Upgrade = 'upgrade',
}

export enum CaseOpeningStatus {
  InInventory = 'in_inventory',
  Sold = 'sold',
  Withdrawn = 'withdrawn',
  UsedInUpgrade = 'used_in_upgrade',
}

export enum UserRole {
  User = 'user',
  Moderator = 'moderator',
  Admin = 'admin',
}
