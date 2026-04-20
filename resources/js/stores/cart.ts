import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { MarketItem } from '@/types/models'

const STORAGE_KEY = 'skinsarena_cart_v1'

function readFromStorage(): MarketItem[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return []
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return []
    return parsed as MarketItem[]
  } catch {
    return []
  }
}

function writeToStorage(items: MarketItem[]): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  } catch {
    /* ignore */
  }
}

export const useCartStore = defineStore('cart', () => {
  const items = ref<MarketItem[]>(readFromStorage())

  const count = computed(() => items.value.length)
  const total = computed(() =>
    items.value.reduce((sum, it) => sum + Number(it.price ?? 0), 0),
  )
  const ids = computed(() => new Set(items.value.map((it) => it.id)))

  function has(id: number): boolean {
    return ids.value.has(id)
  }

  function add(item: MarketItem): void {
    if (has(item.id)) return
    items.value = [...items.value, item]
    writeToStorage(items.value)
  }

  function remove(id: number): void {
    items.value = items.value.filter((it) => it.id !== id)
    writeToStorage(items.value)
  }

  function toggle(item: MarketItem): void {
    if (has(item.id)) {
      remove(item.id)
    } else {
      add(item)
    }
  }

  function clear(): void {
    items.value = []
    writeToStorage(items.value)
  }

  return { items, count, total, has, add, remove, toggle, clear }
})
