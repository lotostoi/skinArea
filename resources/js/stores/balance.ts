import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'
import type { Balance } from '@/types/models'
import { BalanceType } from '@/types/enums'

function isBalanceLike(v: unknown): v is Balance {
  return (
    v !== null &&
    typeof v === 'object' &&
    'type' in v &&
    'amount' in v &&
    typeof (v as { type: unknown }).type === 'string'
  )
}

function normalizeBalancesPayload(payload: unknown): Balance[] {
  if (payload == null) {
    return []
  }

  let inner: unknown = payload
  if (typeof inner === 'object' && !Array.isArray(inner) && inner !== null && 'data' in inner) {
    const nested = (inner as { data: unknown }).data
    if (nested !== undefined) {
      inner = nested
    }
  }

  if (Array.isArray(inner)) {
    return inner.filter(isBalanceLike)
  }

  if (typeof inner === 'object' && inner !== null) {
    return Object.values(inner).filter(isBalanceLike)
  }

  return []
}

function balanceTypeKey(type: string): string {
  return type.toLowerCase()
}

export const useBalanceStore = defineStore('balance', () => {
  const balances = ref<Balance[]>([])

  function formatAmount(value: string | number | undefined): string {
    if (value === undefined || value === null) {
      return '0.00'
    }
    if (typeof value === 'number') {
      return value.toFixed(2)
    }
    return String(value)
  }

  const mainBalance = computed(() =>
    formatAmount(
      balances.value.find((b) => balanceTypeKey(b.type) === BalanceType.Main)?.amount,
    ),
  )

  const holdBalance = computed(() =>
    formatAmount(
      balances.value.find((b) => balanceTypeKey(b.type) === BalanceType.Hold)?.amount,
    ),
  )

  async function fetchBalances(fallback?: unknown): Promise<void> {
    let list: Balance[] = []
    try {
      const { data: body } = await api.get<{ data?: unknown }>('/balance')
      list = normalizeBalancesPayload(body?.data ?? body)
    } catch {
      list = []
    }
    if (list.length === 0 && fallback !== undefined) {
      list = normalizeBalancesPayload(fallback)
    }
    balances.value = list
  }

  return { balances, mainBalance, holdBalance, fetchBalances }
})
