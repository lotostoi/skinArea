<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { CaseOpening } from '@/types/models'
import {
  fetchCaseInventory,
  fetchCaseInventorySummary,
  sellCaseOpening,
  withdrawCaseOpening,
  type CaseInventorySummary,
} from '@/utils/market'
import { useBalanceStore } from '@/stores/balance'
import { formatPrice } from '@/utils/format'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { showAppAlert } from '@/composables/appDialog'

type FilterStatus = 'all' | 'in_inventory' | 'sold' | 'withdrawn' | 'used_in_upgrade'
type SortField = 'created_at' | 'won_item_price'
type SortOrder = 'desc' | 'asc'
type CasesInnerTab = 'inventory' | 'history'

const balance = useBalanceStore()
const router = useRouter()

const openings = ref<CaseOpening[]>([])
const summary = ref<CaseInventorySummary | null>(null)
const loading = ref(false)
const summaryLoading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const activeFilter = ref<FilterStatus>('all')
const search = ref('')
const sort = ref<SortField>('created_at')
const order = ref<SortOrder>('desc')
const activeInnerTab = ref<CasesInnerTab>('inventory')
const selectedOpening = ref<CaseOpening | null>(null)
const sellingIds = ref<Set<number>>(new Set())
const withdrawingIds = ref<Set<number>>(new Set())
const sellingAll = ref(false)

const filters: { id: FilterStatus; label: string }[] = [
  { id: 'all', label: 'Все' },
  { id: 'in_inventory', label: 'Доступные' },
  { id: 'sold', label: 'Проданные' },
  { id: 'withdrawn', label: 'Выведенные' },
  { id: 'used_in_upgrade', label: 'Апгрейд' },
]

const summaryCards = computed(() => {
  const data = summary.value
  if (!data) {
    return []
  }

  return [
    { id: 'total', label: 'Всего предметов', value: String(data.total_items), tone: 'text-text-primary' },
    { id: 'inventory', label: 'В инвентаре', value: String(data.in_inventory_items), tone: 'text-success' },
    { id: 'inventory-value', label: 'Стоимость инвентаря', value: formatPrice(data.in_inventory_value), tone: 'text-primary' },
    { id: 'total-value', label: 'Выиграно всего', value: formatPrice(data.total_value), tone: 'text-info' },
  ]
})

const historyRows = computed(() => {
  return [...openings.value]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
})

async function loadSummary(): Promise<void> {
  summaryLoading.value = true
  try {
    summary.value = await fetchCaseInventorySummary()
  } finally {
    summaryLoading.value = false
  }
}

async function load(page = 1): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const params: Record<string, unknown> = {
      page,
      per_page: 20,
      sort: sort.value,
      order: order.value,
    }
    if (activeFilter.value !== 'all') {
      params.status = activeFilter.value
    }
    if (search.value.trim() !== '') {
      params.search = search.value.trim()
    }
    const result = await fetchCaseInventory(params)
    openings.value = result.data
    currentPage.value = result.meta.current_page
    lastPage.value = result.meta.last_page
    total.value = result.meta.total
  } catch {
    error.value = 'Не удалось загрузить инвентарь.'
  } finally {
    loading.value = false
  }
}

watch(activeFilter, () => {
  currentPage.value = 1
  void load(1)
})

watch([sort, order], () => {
  currentPage.value = 1
  void load(1)
})

watch(search, () => {
  currentPage.value = 1
  void load(1)
})

onMounted(async () => {
  await Promise.all([load(), loadSummary()])
})

async function sell(opening: CaseOpening): Promise<void> {
  if (sellingIds.value.has(opening.id)) return
  sellingIds.value.add(opening.id)
  try {
    const updated = await sellCaseOpening(opening.id)
    await balance.fetchBalances()
    const idx = openings.value.findIndex((o: CaseOpening) => o.id === opening.id)
    if (idx !== -1) openings.value[idx] = updated
    if (selectedOpening.value?.id === opening.id) {
      selectedOpening.value = updated
    }
    await loadSummary()
  } finally {
    sellingIds.value.delete(opening.id)
  }
}

async function withdraw(opening: CaseOpening): Promise<void> {
  if (withdrawingIds.value.has(opening.id)) return
  withdrawingIds.value.add(opening.id)
  try {
    const updated = await withdrawCaseOpening(opening.id)
    const idx = openings.value.findIndex((o: CaseOpening) => o.id === opening.id)
    if (idx !== -1) openings.value[idx] = updated
    if (selectedOpening.value?.id === opening.id) {
      selectedOpening.value = updated
    }
    await loadSummary()
    showAppAlert('Запрос на вывод отправлен в Steam.', { title: 'Готово', variant: 'success' })
  } catch {
    showAppAlert('Не удалось вывести предмет. Проверьте trade URL в профиле и попробуйте снова.', {
      title: 'Ошибка вывода',
      variant: 'error',
    })
  } finally {
    withdrawingIds.value.delete(opening.id)
  }
}

async function sellAllAvailable(): Promise<void> {
  if (sellingAll.value) {
    return
  }

  const available = openings.value.filter((item: CaseOpening) => item.status === 'in_inventory')
  if (available.length === 0) {
    showAppAlert('Нет доступных предметов для продажи.', { title: 'Продажа', variant: 'info' })
    return
  }

  sellingAll.value = true
  let sold = 0
  let failed = 0

  for (const opening of available) {
    try {
      await sell(opening)
      sold += 1
    } catch {
      failed += 1
    }
  }

  await loadSummary()
  sellingAll.value = false

  if (failed > 0) {
    showAppAlert(`Продано: ${sold}. Ошибок: ${failed}.`, { title: 'Продажа завершена', variant: 'info' })
    return
  }

  showAppAlert(`Продано предметов: ${sold}.`, { title: 'Готово', variant: 'success' })
}

function statusLabel(status: string): string {
  switch (status) {
    case 'in_inventory': return 'В инвентаре'
    case 'sold': return 'Продан'
    case 'withdrawn': return 'Выведен'
    case 'used_in_upgrade': return 'Апгрейд'
    default: return status
  }
}

function statusClass(status: string): string {
  switch (status) {
    case 'in_inventory': return 'bg-success/20 text-success'
    case 'sold': return 'bg-text-muted/20 text-text-muted'
    case 'withdrawn': return 'bg-info/20 text-info'
    case 'used_in_upgrade': return 'bg-purple-500/20 text-purple-300'
    default: return 'bg-body text-text-secondary'
  }
}

function sourceLabel(item: CaseOpening): string {
  if (item.source === 'upgrade') {
    return 'Получен через апгрейд'
  }

  return 'Открыт в кейсе'
}

function formatDateTime(value: string): string {
  return new Date(value).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function openOpeningDetails(item: CaseOpening): void {
  selectedOpening.value = item
}
</script>

<template>
  <div class="space-y-5">
    <div class="rounded-xl border border-border bg-surface p-4">
      <div class="mb-3 flex items-center justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold text-text-primary">Сводка по разделу «Кейсы»</h3>
          <p class="text-xs text-text-muted">История выигрышей, текущий склад и связь с апгрейдом.</p>
        </div>
        <button
          type="button"
          class="rounded-md border border-border px-3 py-1.5 text-xs text-text-secondary transition-colors hover:border-border-hover hover:text-text-primary"
          @click="loadSummary"
        >
          Обновить
        </button>
      </div>

      <div v-if="summaryLoading && !summary" class="flex justify-center py-5">
        <AppSpinner size="sm" />
      </div>

      <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.id"
          class="rounded-lg border border-border bg-body/40 p-3"
        >
          <p class="text-xs text-text-muted">{{ card.label }}</p>
          <p class="mt-1 text-lg font-semibold" :class="card.tone">{{ card.value }}</p>
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-border bg-surface p-4">
      <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="f in filters"
            :key="f.id"
            type="button"
            class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors border"
            :class="
              activeFilter === f.id
                ? 'bg-primary text-body border-primary'
                : 'bg-body border-border text-text-secondary hover:text-text-primary hover:border-border-hover'
            "
            @click="activeFilter = f.id"
          >
            {{ f.label }}
          </button>
        </div>

        <div class="flex gap-2 flex-wrap">
          <button
            type="button"
            class="px-3 py-1.5 rounded-md text-sm border border-border text-text-secondary hover:text-text-primary hover:border-border-hover transition-colors"
            @click="router.push({ name: 'cases' })"
          >
            Каталог кейсов
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-md text-sm border border-border text-text-secondary hover:text-text-primary hover:border-border-hover transition-colors"
            @click="router.push({ name: 'upgrade' })"
          >
            История и апгрейд
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-md text-sm border border-primary text-primary hover:bg-primary/10 transition-colors disabled:opacity-60"
            :disabled="sellingAll || activeInnerTab !== 'inventory'"
            @click="sellAllAvailable"
          >
            {{ sellingAll ? 'Продажа...' : 'Продать все доступные' }}
          </button>
        </div>
      </div>

      <div class="mb-4 grid gap-3 md:grid-cols-3">
        <input
          v-model="search"
          type="text"
          class="h-10 rounded-md border border-border bg-body px-3 text-sm text-text-primary placeholder:text-text-muted focus:border-primary focus:outline-none"
          placeholder="Поиск по кейсу или предмету"
        />

        <select
          v-model="sort"
          class="h-10 rounded-md border border-border bg-body px-3 text-sm text-text-primary focus:border-primary focus:outline-none"
        >
          <option value="created_at">Сортировка: по дате</option>
          <option value="won_item_price">Сортировка: по цене</option>
        </select>

        <select
          v-model="order"
          class="h-10 rounded-md border border-border bg-body px-3 text-sm text-text-primary focus:border-primary focus:outline-none"
        >
          <option value="desc">Порядок: по убыванию</option>
          <option value="asc">Порядок: по возрастанию</option>
        </select>
      </div>

      <div class="mb-2 rounded-md border border-border bg-body/40 px-3 py-2 text-xs text-text-muted">
        Источник записи сейчас: открытия кейсов. Записи из апгрейдов будут добавляться в этот же список.
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <AppSpinner size="md" />
      </div>

      <div v-else-if="error" class="rounded-xl border border-border bg-surface p-8 text-center">
        <p class="text-text-secondary">{{ error }}</p>
        <button type="button" class="mt-3 text-sm text-primary hover:text-primary-light" @click="load(currentPage)">
          Повторить
        </button>
      </div>

      <div v-else-if="openings.length === 0" class="rounded-xl border border-border bg-surface p-12 text-center">
        <p class="text-text-secondary">Нет предметов по выбранным фильтрам.</p>
        <p class="mt-1 text-sm text-text-muted">Измените фильтры или откройте новый кейс.</p>
      </div>

      <div v-else>
        <p class="text-xs text-text-muted mb-4">Всего по фильтру: {{ total }}</p>

        <div class="mb-4 flex items-center gap-2">
          <button
            type="button"
            class="rounded-md border px-3 py-1.5 text-sm transition-colors"
            :class="
              activeInnerTab === 'inventory'
                ? 'border-primary bg-primary text-body'
                : 'border-border bg-body text-text-secondary hover:text-text-primary'
            "
            @click="activeInnerTab = 'inventory'"
          >
            Склад и действия
          </button>
          <button
            type="button"
            class="rounded-md border px-3 py-1.5 text-sm transition-colors"
            :class="
              activeInnerTab === 'history'
                ? 'border-primary bg-primary text-body'
                : 'border-border bg-body text-text-secondary hover:text-text-primary'
            "
            @click="activeInnerTab = 'history'"
          >
            История открытий
          </button>
        </div>

        <div v-if="activeInnerTab === 'history'" class="rounded-lg border border-border bg-body/40 p-3">
          <div class="mb-2 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-text-primary">История открытий кейсов</h4>
            <span class="text-xs text-text-muted">Записи на странице: {{ historyRows.length }}</span>
          </div>

          <div v-if="historyRows.length === 0" class="text-xs text-text-muted">
            История пока пуста.
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="row in historyRows"
              :key="`history-${row.id}`"
              class="flex items-center justify-between gap-3 rounded-md border border-border/70 bg-surface px-3 py-2"
            >
              <div class="min-w-0">
                <p class="truncate text-xs font-medium text-text-primary">
                  {{ row.won_item?.name ?? 'Предмет' }}
                </p>
                <p class="truncate text-[11px] text-text-muted">
                  {{ row.case?.name ?? 'Кейс' }} · {{ sourceLabel(row) }}
                </p>
              </div>
              <div class="shrink-0 text-right space-y-1">
                <p class="text-xs font-semibold text-primary">{{ formatPrice(row.won_item_price) }}</p>
                <p class="text-[11px] text-text-muted">{{ formatDateTime(row.created_at) }}</p>
                <button
                  type="button"
                  class="text-[11px] text-primary transition-colors hover:text-primary-light"
                  @click="openOpeningDetails(row)"
                >
                  Открыть детали
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
          <div
            v-for="item in openings"
            :key="item.id"
            class="rounded-xl border border-border bg-surface p-3 flex flex-col gap-2 transition-opacity"
            :class="item.status === 'in_inventory' ? 'opacity-100' : 'opacity-85'"
            :style="
              item.won_item?.rarity_color
                ? { boxShadow: `0 0 0 1px ${item.won_item.rarity_color}40` }
                : {}
            "
          >
            <div class="aspect-square bg-body/40 rounded-md flex items-center justify-center p-2 overflow-hidden">
              <img
                v-if="item.won_item?.image_url"
                :src="item.won_item.image_url"
                :alt="item.won_item?.name"
                class="max-w-full max-h-full object-contain"
                loading="lazy"
              />
            </div>

            <p class="text-xs text-text-primary font-medium line-clamp-2 min-h-[2.5rem] leading-tight">
              {{ item.won_item?.name ?? 'Предмет' }}
            </p>

            <p class="text-xs text-text-muted">{{ item.case?.name ?? '' }}</p>

            <p class="text-sm font-bold text-primary">{{ formatPrice(item.won_item_price) }}</p>

            <div class="space-y-1 text-[11px] text-text-muted">
              <p>Получен: {{ formatDateTime(item.created_at) }}</p>
              <p>Источник: {{ sourceLabel(item) }}</p>
              <p v-if="item.status === 'used_in_upgrade'" class="text-purple-300">
                Предмет использован в апгрейде
              </p>
            </div>

            <span
              class="self-start text-[10px] px-1.5 py-0.5 rounded font-medium"
              :class="statusClass(item.status)"
            >
              {{ statusLabel(item.status) }}
            </span>

            <div v-if="item.status === 'in_inventory'" class="mt-auto grid gap-2">
              <button
                type="button"
                class="text-xs px-3 py-1.5 rounded-md bg-primary text-body font-semibold hover:bg-primary-hover transition-colors disabled:opacity-50"
                :disabled="sellingIds.has(item.id) || withdrawingIds.has(item.id)"
                @click="sell(item)"
              >
                {{ sellingIds.has(item.id) ? 'Продажа...' : `Продать за ${formatPrice(item.won_item_price)}` }}
              </button>

              <button
                type="button"
                class="text-xs px-3 py-1.5 rounded-md bg-body border border-border text-text-secondary hover:text-text-primary transition-colors disabled:opacity-50"
                :disabled="withdrawingIds.has(item.id) || sellingIds.has(item.id)"
                @click="withdraw(item)"
              >
                {{ withdrawingIds.has(item.id) ? 'Вывод...' : 'Вывести в Steam' }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="lastPage > 1" class="mt-6 flex gap-2 justify-center flex-wrap">
          <button
            v-for="p in lastPage"
            :key="p"
            type="button"
            class="min-w-[36px] h-9 px-3 rounded-md text-sm font-medium border transition-colors"
            :class="
              p === currentPage
                ? 'bg-primary text-body border-primary'
                : 'bg-body border-border text-text-secondary hover:text-text-primary'
            "
            @click="load(p)"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="selectedOpening"
      class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 px-4"
      @click.self="selectedOpening = null"
    >
      <div class="w-full max-w-lg rounded-xl border border-border bg-surface p-4">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div>
            <h4 class="text-base font-semibold text-text-primary">Открытие #{{ selectedOpening.id }}</h4>
            <p class="text-xs text-text-muted">{{ formatDateTime(selectedOpening.created_at) }}</p>
          </div>
          <button
            type="button"
            class="rounded-md border border-border px-2 py-1 text-xs text-text-secondary transition-colors hover:text-text-primary"
            @click="selectedOpening = null"
          >
            Закрыть
          </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-[100px_1fr]">
          <div class="aspect-square rounded-md bg-body/40 p-2">
            <img
              v-if="selectedOpening.won_item?.image_url"
              :src="selectedOpening.won_item.image_url"
              :alt="selectedOpening.won_item?.name"
              class="h-full w-full object-contain"
            />
          </div>
          <div class="space-y-1 text-sm">
            <p class="font-medium text-text-primary">{{ selectedOpening.won_item?.name ?? 'Предмет' }}</p>
            <p class="text-text-muted">Кейс: {{ selectedOpening.case?.name ?? '—' }}</p>
            <p class="text-text-muted">Источник: {{ sourceLabel(selectedOpening) }}</p>
            <p class="text-text-muted">Статус: {{ statusLabel(selectedOpening.status) }}</p>
            <p class="font-semibold text-primary">Стоимость: {{ formatPrice(selectedOpening.won_item_price) }}</p>
          </div>
        </div>

        <div v-if="selectedOpening.status === 'in_inventory'" class="mt-4 grid gap-2 sm:grid-cols-2">
          <button
            type="button"
            class="rounded-md bg-primary px-3 py-2 text-sm font-semibold text-body transition-colors hover:bg-primary-hover disabled:opacity-50"
            :disabled="sellingIds.has(selectedOpening.id) || withdrawingIds.has(selectedOpening.id)"
            @click="sell(selectedOpening)"
          >
            {{ sellingIds.has(selectedOpening.id) ? 'Продажа...' : `Продать за ${formatPrice(selectedOpening.won_item_price)}` }}
          </button>
          <button
            type="button"
            class="rounded-md border border-border bg-body px-3 py-2 text-sm text-text-secondary transition-colors hover:text-text-primary disabled:opacity-50"
            :disabled="withdrawingIds.has(selectedOpening.id) || sellingIds.has(selectedOpening.id)"
            @click="withdraw(selectedOpening)"
          >
            {{ withdrawingIds.has(selectedOpening.id) ? 'Вывод...' : 'Вывести в Steam' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
