export function formatPrice(price: string | number | null | undefined): string {
  const n = Number(price ?? 0)
  if (!Number.isFinite(n)) return '0 ₽'
  return `${n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

export function formatPriceShort(price: string | number | null | undefined): string {
  const n = Number(price ?? 0)
  if (!Number.isFinite(n)) return '0 ₽'
  return `${n.toLocaleString('ru-RU', { maximumFractionDigits: 0 })} ₽`
}

export const WEAR_LABELS: Record<string, string> = {
  FN: 'Прямо с завода',
  MW: 'Немного поношенное',
  FT: 'После полевых испытаний',
  WW: 'Поношенное',
  BS: 'Закалённое в боях',
}

export function wearLabel(wear: string | null | undefined): string {
  if (!wear) return ''
  return `${wear} · ${WEAR_LABELS[wear] ?? ''}`.trim()
}

export function wearTextClass(wear: string | null | undefined): string {
  switch (wear) {
    case 'FN':
      return 'text-wear-fn'
    case 'MW':
      return 'text-wear-mw'
    case 'FT':
      return 'text-wear-ft'
    case 'WW':
      return 'text-wear-ww'
    case 'BS':
      return 'text-wear-bs'
    default:
      return 'text-text-muted'
  }
}

export const RARITY_LABELS: Record<string, string> = {
  consumer_grade: 'Ширпотреб',
  industrial_grade: 'Промышленное',
  mil_spec: 'Армейское',
  restricted: 'Запрещённое',
  classified: 'Засекреченное',
  covert: 'Тайное',
  contraband: 'Контрабанда',
}

export function rarityLabel(rarity: string | null | undefined): string {
  if (!rarity) return ''
  return RARITY_LABELS[rarity] ?? rarity
}

export function rarityRingClass(rarity: string | null | undefined): string {
  switch (rarity) {
    case 'consumer_grade':
      return 'shadow-[0_0_0_1px_rgba(176,195,217,0.35),0_6px_20px_-10px_rgba(176,195,217,0.5)]'
    case 'industrial_grade':
      return 'shadow-[0_0_0_1px_rgba(94,152,217,0.4),0_6px_20px_-10px_rgba(94,152,217,0.55)]'
    case 'mil_spec':
      return 'shadow-[0_0_0_1px_rgba(75,105,255,0.4),0_8px_22px_-10px_rgba(75,105,255,0.55)]'
    case 'restricted':
      return 'shadow-[0_0_0_1px_rgba(136,71,255,0.45),0_10px_24px_-10px_rgba(136,71,255,0.6)]'
    case 'classified':
      return 'shadow-[0_0_0_1px_rgba(211,44,230,0.45),0_12px_26px_-10px_rgba(211,44,230,0.6)]'
    case 'covert':
      return 'shadow-[0_0_0_1px_rgba(235,75,75,0.5),0_14px_30px_-10px_rgba(235,75,75,0.6)]'
    case 'contraband':
      return 'shadow-[0_0_0_1px_rgba(228,174,57,0.55),0_14px_32px_-10px_rgba(228,174,57,0.7)]'
    default:
      return ''
  }
}

export const CATEGORY_LABELS: Record<string, string> = {
  knives: 'Ножи',
  gloves: 'Перчатки',
  pistols: 'Пистолеты',
  rifles: 'Винтовки',
  smgs: 'ПП',
  heavy: 'Тяжёлое',
  other: 'Прочее',
}

export function categoryLabel(category: string | null | undefined): string {
  if (!category) return ''
  return CATEGORY_LABELS[category] ?? category
}
