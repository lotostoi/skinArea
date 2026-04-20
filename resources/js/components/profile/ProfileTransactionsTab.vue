<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { fetchTransactions } from '@/utils/transactions'
import type { Transaction } from '@/types/models'

const items = ref<Transaction[]>([])
const loading = ref(false)
const filterId = ref('')
const filterSkin = ref('')
const txView = ref<'all' | 'holds'>('all')

const typeLabels: Record<string, string> = {
  deposit: 'Пополнение',
  withdrawal: 'Вывод',
  purchase: 'Покупка',
  sale: 'Продажа',
  case_open: 'Кейс',
  case_sell: 'Продажа из кейса',
  upgrade: 'Апгрейд',
}

function typeLabel(t: string): string {
  return typeLabels[t] ?? t
}

function formatMoney(v: string): string {
  const n = Number(v)
  if (Number.isNaN(n)) {
    return v
  }
  return `${n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

function formatDate(iso: string): string {
  try {
    return new Date(iso).toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

function metaString(m: Record<string, unknown> | null): string {
  if (!m) {
    return ''
  }
  return JSON.stringify(m).toLowerCase()
}

const filtered = computed(() => {
  let list = items.value
  if (txView.value === 'holds') {
    list = list.filter((t: Transaction) => t.type === 'purchase')
  }
  const idq = filterId.value.trim()
  if (idq !== '') {
    list = list.filter((t: Transaction) => String(t.id).includes(idq))
  }
  const skin = filterSkin.value.trim().toLowerCase()
  if (skin !== '') {
    list = list.filter((t: Transaction) => metaString(t.metadata).includes(skin))
  }
  return list
})

async function load() {
  loading.value = true
  try {
    const res = await fetchTransactions(1, 50)
    items.value = res.data
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold uppercase tracking-wide text-text-primary md:text-2xl">
      Транзакции
    </h2>

    <p class="text-sm text-text-secondary">
      Единая таблица операций по балансу. Вкладка «Удержания» — операции покупки (связанные с удержанием средств).
    </p>

    <div class="flex flex-wrap gap-1 border-b border-border">
      <button
        type="button"
        class="relative px-4 py-2.5 text-sm font-medium transition-colors"
        :class="
          txView === 'all'
            ? 'text-primary after:absolute after:bottom-0 after:left-2 after:right-2 after:h-0.5 after:rounded-full after:bg-primary'
            : 'text-text-secondary hover:text-text-primary'
        "
        @click="txView = 'all'"
      >
        Все транзакции
      </button>
      <button
        type="button"
        class="relative px-4 py-2.5 text-sm font-medium transition-colors"
        :class="
          txView === 'holds'
            ? 'text-primary after:absolute after:bottom-0 after:left-2 after:right-2 after:h-0.5 after:rounded-full after:bg-primary'
            : 'text-text-secondary hover:text-text-primary'
        "
        @click="txView = 'holds'"
      >
        Удержания
      </button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <input
        v-model="filterId"
        type="text"
        inputmode="numeric"
        placeholder="ID транзакции"
        class="w-full rounded-md border border-border bg-input px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:border-primary focus:outline-none sm:max-w-[200px]"
      />
      <input
        v-model="filterSkin"
        type="text"
        placeholder="Название скина"
        class="w-full rounded-md border border-border bg-input px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:border-primary focus:outline-none sm:max-w-[220px]"
      />
    </div>

    <div class="overflow-x-auto rounded-xl border border-border">
      <table class="min-w-[900px] w-full border-collapse text-left text-sm">
        <thead>
          <tr class="border-b border-border bg-[#16161e] text-[11px] font-semibold uppercase tracking-wide text-text-muted">
            <th class="px-4 py-3">Тип / дата</th>
            <th class="px-4 py-3">ID</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3">Сумма</th>
            <th class="px-4 py-3">Платёж. система</th>
            <th class="px-4 py-3">Куда</th>
            <th class="px-4 py-3">Баланс</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-8 text-center text-text-muted">Загрузка…</td>
          </tr>
          <tr v-else-if="filtered.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-text-muted">Нет транзакций</td>
          </tr>
          <tr
            v-for="t in filtered"
            :key="t.id"
            class="border-b border-border/80 transition-colors hover:bg-surface-hover/50"
          >
            <td class="px-4 py-3 align-top text-text-primary">
              <div class="font-medium">{{ typeLabel(t.type) }}</div>
              <div class="mt-0.5 text-xs text-text-muted">{{ formatDate(t.created_at) }}</div>
            </td>
            <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ t.id }}</td>
            <td class="px-4 py-3 text-text-muted">—</td>
            <td class="px-4 py-3 font-semibold text-text-primary">{{ formatMoney(t.amount) }}</td>
            <td class="px-4 py-3 text-text-muted">—</td>
            <td class="px-4 py-3 text-text-muted">—</td>
            <td class="px-4 py-3 text-text-secondary">{{ formatMoney(t.balance_after) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
