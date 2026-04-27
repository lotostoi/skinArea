/**
 * Парсинг hex из админки (ColorPicker: обычно #rrggbb).
 */
export function parseCssHexToRgb(
  input: string | null | undefined,
): { r: number; g: number; b: number } | null {
  if (input == null || typeof input !== 'string') {
    return null
  }
  const s = input.trim()
  const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(s)
  if (!m) {
    return null
  }
  let h = m[1]
  if (h.length === 3) {
    h = h
      .split('')
      .map((c) => c + c)
      .join('')
  }
  const n = Number.parseInt(h, 16)
  if (!Number.isFinite(n)) {
    return null
  }
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 }
}

/**
 * Свечение вокруг PNG-обложки кейса (drop-shadow по альфе картинки).
 * Если цвет не задан — undefined, остаются классы Tailwind.
 */
export function gameCaseImageFilterStyle(
  hex: string | null | undefined,
): { filter: string } | undefined {
  const rgb = parseCssHexToRgb(hex)
  if (!rgb) {
    return undefined
  }
  const { r, g, b } = rgb
  return {
    filter: `drop-shadow(0 0 10px rgba(${r},${g},${b},0.28)) drop-shadow(0 12px 28px rgba(${r},${g},${b},0.4))`,
  }
}

export interface GameCaseCoverImgAttrsOptions {
  shadowColor: string | null | undefined
  tailwindFallback: string
  baseClass: string
}

/**
 * class + style для обложки: при заданном цвете — цветной drop-shadow, иначе класс Tailwind.
 */
export function gameCaseCoverImgAttrs(
  options: GameCaseCoverImgAttrsOptions,
): { class: string; style?: { filter: string } } {
  const custom = gameCaseImageFilterStyle(options.shadowColor)
  const cls = custom
    ? options.baseClass.trim()
    : `${options.baseClass} ${options.tailwindFallback}`.replace(/\s+/g, ' ').trim()
  return custom ? { class: cls, style: custom } : { class: cls }
}
