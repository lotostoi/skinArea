<script setup lang="ts">
import { ref, watch } from 'vue'
import { fetchDeals } from '@/utils/deals'
import type { Deal } from '@/types/models'

const props = defineProps<{
  active: boolean
}>()

const items = ref<Deal[]>([])
const loading = ref(false)

const statusLabels: Record<string, string> = {
  created: 'Создана',
  paid: 'Оплачена',
  trade_sent: 'Трейд отправлен',
  trade_accepted: 'Трейд принят',
  completed: 'Завершена',
  cancelled: 'Отменена',
}

function statusLabel(s: string): string {
  return statusLabels[s] ?? s
}

function formatMoney(v: string): string {
  const n = Number(v)
  if (Number.isNaN(n)) {
    return v
  }
  return `${n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

function formatDate(iso: string | undefined): string {
  if (!iso) {
    return '—'
  }
  try {
    return new Date(iso).toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

async function load() {
  if (!props.active) {
    return
  }
  loading.value = true
  try {
    const res = await fetchDeals(1, 50)
    items.value = res.data
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

watch(
  () => props.active,
  (v: boolean) => {
    if (v) {
      void load()
    }
  },
  { immediate: true },
)
</script>

<template>
  <div v-show="active" class="space-y-6">
    <h2 class="text-xl font-bold uppercase tracking-wide text-text-primary md:text-2xl">Мои сделки</h2>
    <p class="text-sm text-text-secondary">
      Статус, сумма и предмет по каждой сделке. Отправка и отмена трейда скоро будут доступны.
    </p>

    <div class="overflow-x-auto rounded-xl border border-border">
      <table class="min-w-[720px] w-full border-collapse text-left text-sm">
        <thead>
          <tr
            class="border-b border-border bg-[#16161e] text-[11px] font-semibold uppercase tracking-wide text-text-muted"
          >
            <th class="px-4 py-3">ID</th>
            <th class="px-4 py-3">Предмет</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3">Сумма</th>
            <th class="px-4 py-3">Комиссия</th>
            <th class="px-4 py-3">Трейд</th>
            <th class="px-4 py-3">Создана</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-8 text-center text-text-muted">Загрузка…</td>
          </tr>
          <tr v-else-if="items.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-text-muted">Нет сделок</td>
          </tr>
          <tr
            v-for="d in items"
            :key="d.id"
            class="border-b border-border/80 transition-colors hover:bg-surface-hover/50"
          >
            <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ d.id }}</td>
            <td class="max-w-[240px] px-4 py-3 text-text-primary">
              <span class="line-clamp-2">{{ d.market_item?.name ?? '—' }}</span>
            </td>
            <td class="px-4 py-3 text-text-primary">{{ statusLabel(d.status) }}</td>
            <td class="px-4 py-3 font-semibold text-text-primary">{{ formatMoney(d.price) }}</td>
            <td class="px-4 py-3 text-text-secondary">{{ formatMoney(d.commission) }}</td>
            <td class="px-4 py-3 font-mono text-xs text-text-muted">
              {{ d.trade_offer_id ?? '—' }}
            </td>
            <td class="px-4 py-3 text-text-muted">{{ formatDate(d.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
