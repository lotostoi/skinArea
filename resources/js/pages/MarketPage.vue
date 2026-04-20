<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import EmptyStateGraphic from '@/components/ui/EmptyStateGraphic.vue'
import MarketItemCard from '@/components/market/MarketItemCard.vue'
import MarketItemCardSkeleton from '@/components/ui/MarketItemCardSkeleton.vue'
import CartDrawer from '@/components/market/CartDrawer.vue'
import { fetchMarketItems, purchaseCart, type MarketFilters } from '@/utils/market'
import type { MarketItem } from '@/types/models'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useBalanceStore } from '@/stores/balance'
import { showAppAlert } from '@/composables/appDialog'
import { CATEGORY_LABELS } from '@/utils/format'
import { extractApiErrorMessage } from '@/utils/apiErrors'

const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const balanceStore = useBalanceStore()

const checkoutLoading = ref(false)

async function handleCheckout(): Promise<void> {
  if (!auth.isAuthenticated) {
    auth.steamLogin()
    return
  }
  if (cart.count === 0 || checkoutLoading.value) {
    return
  }
  checkoutLoading.value = true
  try {
    const ids = cart.items.map((it) => it.id)
    const deals = await purchaseCart(ids)
    cart.clear()
    cartOpen.value = false
    await balanceStore.fetchBalances()
    await load(page.value)
    showAppAlert(
      `Покупка оформлена. Сделок: ${deals.length}. Средства удержаны на 7 дней до завершения трейдов.`,
      { title: 'Покупка принята', variant: 'success' },
    )
  } catch (e: unknown) {
    const message = extractApiErrorMessage(e, 'Не удалось оформить покупку. Попробуйте позже.')
    showAppAlert(message, { title: 'Ошибка покупки', variant: 'error' })
  } finally {
    checkoutLoading.value = false
  }
}

const items = ref<MarketItem[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const cartOpen = ref(false)

const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = 24

const skeletonPlaceholders = 15

const filters = ref<MarketFilters>({
  category: null,
  wear: null,
  price_min: null,
  price_max: null,
  search: null,
})

const searchInput = ref('')
const priceMinInput = ref('')
const priceMaxInput = ref('')

const categories = Object.entries(CATEGORY_LABELS).map(([value, label]) => ({ value, label }))
const wears = [
  { value: 'FN', label: 'Прямо с завода (FN)' },
  { value: 'MW', label: 'Немного поношенное (MW)' },
  { value: 'FT', label: 'После полевых испытаний (FT)' },
  { value: 'WW', label: 'Поношенное (WW)' },
  { value: 'BS', label: 'Закалённое в боях (BS)' },
]

const hasActiveFilters = computed(
  () =>
    filters.value.category ||
    filters.value.wear ||
    filters.value.price_min != null ||
    filters.value.price_max != null ||
    !!filters.value.search,
)

async function load(targetPage = 1): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const res = await fetchMarketItems({
      ...filters.value,
      page: targetPage,
      per_page: perPage,
    })
    items.value = res.data
    page.value = res.meta.current_page
    lastPage.value = res.meta.last_page
    total.value = res.meta.total
  } catch {
    error.value = 'Не удалось загрузить маркет. Попробуйте ещё раз.'
    items.value = []
  } finally {
    loading.value = false
  }
}

function applyFilters(): void {
  filters.value.search = searchInput.value.trim() || null
  filters.value.price_min = priceMinInput.value ? Number(priceMinInput.value) : null
  filters.value.price_max = priceMaxInput.value ? Number(priceMaxInput.value) : null
  void load(1)
}

function resetFilters(): void {
  filters.value = { category: null, wear: null, price_min: null, price_max: null, search: null }
  searchInput.value = ''
  priceMinInput.value = ''
  priceMaxInput.value = ''
  void load(1)
}

function selectCategory(value: string | null): void {
  filters.value.category = value
  void load(1)
}

function selectWear(value: string | null): void {
  filters.value.wear = value
  void load(1)
}

function openItem(item: MarketItem): void {
  void router.push({ name: 'market-item', params: { id: item.id } })
}

function toggleCart(item: MarketItem): void {
  cart.toggle(item)
}

function prevPage(): void {
  if (page.value > 1) void load(page.value - 1)
}

function nextPage(): void {
  if (page.value < lastPage.value) void load(page.value + 1)
}

onMounted(() => {
  void load(1)
})

watch(
  () => filters.value.search,
  () => {
    /* debounce via Apply button */
  },
)
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Маркетплейс</h1>
        <p v-if="total > 0" class="text-sm text-text-secondary mt-1">Лотов: {{ total }}</p>
      </div>
      <button
        type="button"
        class="flex items-center gap-2 rounded-md border border-border bg-surface px-4 py-2 text-sm text-text-primary transition-colors hover:border-border-hover hover:bg-surface-hover"
        @click="cartOpen = true"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
          <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
        </svg>
        Корзина
        <span
          v-if="cart.count"
          class="min-w-[20px] rounded-full bg-primary px-1.5 text-xs font-semibold text-text-on-primary"
        >
          {{ cart.count }}
        </span>
      </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
      <aside class="space-y-4">
        <div class="rounded-xl border border-border bg-surface p-4 space-y-3">
          <h2 class="text-sm font-semibold text-text-primary">Поиск</h2>
          <AppInput
            v-model="searchInput"
            placeholder="Название скина…"
            @keydown.enter="applyFilters"
          />
        </div>

        <div class="rounded-xl border border-border bg-surface p-4 space-y-3">
          <h2 class="text-sm font-semibold text-text-primary">Цена, ₽</h2>
          <div class="flex items-center gap-2">
            <AppInput v-model="priceMinInput" type="number" placeholder="от" />
            <AppInput v-model="priceMaxInput" type="number" placeholder="до" />
          </div>
          <AppButton size="sm" variant="primary" class="w-full" @click="applyFilters">
            Применить
          </AppButton>
        </div>

        <div class="rounded-xl border border-border bg-surface p-4 space-y-2">
          <h2 class="text-sm font-semibold text-text-primary">Категория</h2>
          <div class="flex flex-col gap-1">
            <button
              type="button"
              class="text-left text-sm px-2 py-1.5 rounded-md transition-colors"
              :class="filters.category === null
                ? 'bg-primary/10 text-primary'
                : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'"
              @click="selectCategory(null)"
            >
              Все
            </button>
            <button
              v-for="c in categories"
              :key="c.value"
              type="button"
              class="text-left text-sm px-2 py-1.5 rounded-md transition-colors"
              :class="filters.category === c.value
                ? 'bg-primary/10 text-primary'
                : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'"
              @click="selectCategory(c.value)"
            >
              {{ c.label }}
            </button>
          </div>
        </div>

        <div class="rounded-xl border border-border bg-surface p-4 space-y-2">
          <h2 class="text-sm font-semibold text-text-primary">Износ</h2>
          <div class="flex flex-col gap-1">
            <button
              type="button"
              class="text-left text-sm px-2 py-1.5 rounded-md transition-colors"
              :class="filters.wear === null
                ? 'bg-primary/10 text-primary'
                : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'"
              @click="selectWear(null)"
            >
              Любой
            </button>
            <button
              v-for="w in wears"
              :key="w.value"
              type="button"
              class="text-left text-sm px-2 py-1.5 rounded-md transition-colors"
              :class="filters.wear === w.value
                ? 'bg-primary/10 text-primary'
                : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'"
              @click="selectWear(w.value)"
            >
              {{ w.label }}
            </button>
          </div>
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="w-full text-sm text-text-muted hover:text-text-primary transition-colors"
          @click="resetFilters"
        >
          Сбросить фильтры
        </button>
      </aside>

      <div class="min-w-0">
        <p v-if="error" class="text-danger text-sm mb-4">{{ error }}</p>

        <div class="min-h-[32rem]">
          <div
            v-if="loading"
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8"
          >
            <MarketItemCardSkeleton v-for="n in skeletonPlaceholders" :key="'sk-' + n" />
          </div>

          <template v-else-if="items.length === 0">
            <div class="bg-surface border border-border rounded-xl p-12 text-center">
              <EmptyStateGraphic variant="market" />
              <h2 class="text-xl font-semibold mb-2 text-text-primary">Пока нет лотов</h2>
              <p class="text-text-secondary text-sm">Попробуйте изменить фильтры или зайдите позже.</p>
            </div>
          </template>

          <template v-else>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
              <MarketItemCard
                v-for="it in items"
                :key="it.id"
                :item="it"
                :in-cart="cart.has(it.id)"
                @click="openItem"
                @add-to-cart="toggleCart"
              />
            </div>

            <div v-if="lastPage > 1" class="flex items-center justify-center gap-4">
              <AppButton variant="secondary" :disabled="page <= 1 || loading" @click="prevPage">
                Назад
              </AppButton>
              <span class="text-sm text-text-secondary">{{ page }} / {{ lastPage }}</span>
              <AppButton variant="secondary" :disabled="page >= lastPage || loading" @click="nextPage">
                Вперёд
              </AppButton>
            </div>
          </template>
        </div>
      </div>
    </div>

    <CartDrawer
      :open="cartOpen"
      :loading="checkoutLoading"
      @close="cartOpen = false"
      @checkout="handleCheckout"
    />
  </div>
</template>
