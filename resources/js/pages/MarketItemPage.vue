<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
import { fetchMarketItem, purchaseCart } from '@/utils/market'
import type { MarketItem } from '@/types/models'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useBalanceStore } from '@/stores/balance'
import { showAppAlert } from '@/composables/appDialog'
import { extractApiErrorMessage } from '@/utils/apiErrors'
import {
  categoryLabel,
  formatPrice,
  rarityLabel,
  rarityRingClass,
  wearLabel,
  wearTextClass,
} from '@/utils/format'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const balanceStore = useBalanceStore()

const item = ref<MarketItem | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const buying = ref(false)

const inCart = computed(() => (item.value ? cart.has(item.value.id) : false))
const ringClass = computed(() => rarityRingClass(item.value?.rarity))

async function load(id: number): Promise<void> {
  loading.value = true
  error.value = null
  try {
    item.value = await fetchMarketItem(id)
  } catch {
    error.value = 'Лот не найден или снят с продажи.'
    item.value = null
  } finally {
    loading.value = false
  }
}

async function buyNow(): Promise<void> {
  if (!auth.isAuthenticated) {
    auth.steamLogin()
    return
  }
  if (!item.value || buying.value) {
    return
  }
  buying.value = true
  try {
    await purchaseCart([item.value.id])
    cart.remove(item.value.id)
    await balanceStore.fetchBalances()
    showAppAlert(
      'Покупка оформлена. Средства удержаны на 7 дней до завершения трейда.',
      { title: 'Покупка принята', variant: 'success' },
    )
    await load(item.value.id)
  } catch (e: unknown) {
    const message = extractApiErrorMessage(e, 'Не удалось оформить покупку. Попробуйте позже.')
    showAppAlert(message, { title: 'Ошибка покупки', variant: 'error' })
  } finally {
    buying.value = false
  }
}

function toggleCart(): void {
  if (!item.value) return
  cart.toggle(item.value)
}

onMounted(() => {
  const id = Number(route.params.id)
  if (Number.isFinite(id)) void load(id)
})

watch(
  () => route.params.id,
  (id) => {
    const n = Number(id)
    if (Number.isFinite(n)) void load(n)
  },
)
</script>

<template>
  <div>
    <div class="mb-6 flex items-center gap-3">
      <button
        type="button"
        class="text-sm text-text-secondary hover:text-text-primary transition-colors"
        @click="router.back()"
      >
        ← Назад
      </button>
    </div>

    <div v-if="loading" class="rounded-xl border border-border bg-surface p-12 text-center text-text-secondary">
      Загрузка…
    </div>

    <div v-else-if="error || !item" class="rounded-xl border border-border bg-surface p-12 text-center">
      <p class="text-text-primary font-semibold">{{ error || 'Лот не найден.' }}</p>
      <router-link to="/market" class="mt-3 inline-block text-sm text-primary hover:text-primary-light">
        Вернуться в маркет
      </router-link>
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-[440px_1fr]">
      <div
        class="rounded-xl border border-border bg-surface p-6 flex items-center justify-center aspect-square"
        :class="ringClass"
      >
        <img
          v-if="item.image_url"
          :src="item.image_url"
          :alt="item.name"
          class="max-w-full max-h-full object-contain"
        />
        <span v-else class="text-text-muted">Нет изображения</span>
      </div>

      <div class="space-y-5">
        <div>
          <p class="text-xs uppercase tracking-wider text-text-muted mb-1">
            {{ categoryLabel(item.category) }}
          </p>
          <h1 class="text-2xl font-bold text-text-primary">{{ item.name }}</h1>
        </div>

        <div class="rounded-xl border border-border bg-surface p-5 space-y-4">
          <div class="flex items-end gap-3 flex-wrap">
            <p class="text-3xl font-bold text-primary">{{ formatPrice(item.price) }}</p>
          </div>

          <div class="flex gap-3 flex-wrap">
            <AppButton
              variant="primary"
              size="md"
              class="min-w-[180px]"
              :disabled="buying"
              @click="buyNow"
            >
              {{ buying ? 'Оформляем…' : 'Купить' }}
            </AppButton>
            <AppButton
              :variant="inCart ? 'secondary' : 'secondary'"
              size="md"
              class="min-w-[180px]"
              @click="toggleCart"
            >
              {{ inCart ? 'В корзине' : 'В корзину' }}
            </AppButton>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="rounded-xl border border-border bg-surface p-4">
            <p class="text-xs text-text-muted mb-1">Редкость</p>
            <p class="text-sm font-semibold text-text-primary">{{ rarityLabel(item.rarity) }}</p>
          </div>
          <div class="rounded-xl border border-border bg-surface p-4">
            <p class="text-xs text-text-muted mb-1">Износ</p>
            <p class="text-sm font-semibold" :class="wearTextClass(item.wear)">
              {{ wearLabel(item.wear) }}
            </p>
          </div>
          <div v-if="item.float_value != null" class="rounded-xl border border-border bg-surface p-4">
            <p class="text-xs text-text-muted mb-1">Float</p>
            <p class="text-sm font-mono text-text-primary">{{ Number(item.float_value).toFixed(6) }}</p>
          </div>
          <div class="rounded-xl border border-border bg-surface p-4">
            <p class="text-xs text-text-muted mb-1">Asset ID</p>
            <p class="text-xs font-mono text-text-secondary truncate" :title="item.asset_id">{{ item.asset_id }}</p>
          </div>
        </div>

        <div v-if="item.seller" class="rounded-xl border border-border bg-surface p-4 flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-secondary/20 flex items-center justify-center text-secondary font-semibold">
            {{ item.seller.username?.charAt(0)?.toUpperCase() || '?' }}
          </div>
          <div>
            <p class="text-xs text-text-muted">Продавец</p>
            <p class="text-sm font-semibold text-text-primary">{{ item.seller.username }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
