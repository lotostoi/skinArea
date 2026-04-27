<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
import RouletteModal from '@/components/cases/RouletteModal.vue'
import { fetchCase, openCase as apiOpenCase } from '@/utils/market'
import type { CaseLevel, CaseOpening, GameCase } from '@/types/models'
import { useBalanceStore } from '@/stores/balance'
import { showAppAlert } from '@/composables/appDialog'
import {
  formatPrice,
  rarityLabel,
  rarityRingClass,
  wearTextClass,
} from '@/utils/format'
import { gameCaseCoverImgAttrs } from '@/utils/caseVisual'

const route = useRoute()
const router = useRouter()
const balance = useBalanceStore()

const gameCase = ref<GameCase | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const opening = ref(false)
const lastOpening = ref<CaseOpening | null>(null)
const batchResults = ref<CaseOpening[]>([])
const showRoulette = ref(false)
const showBatchResults = ref(false)
const selectedQuantity = ref(1)


const totalItems = computed(
  () =>
    gameCase.value?.levels?.reduce((sum: number, lvl: CaseLevel) => sum + (lvl.items?.length ?? 0), 0) ?? 0,
)

async function load(id: number): Promise<void> {
  loading.value = true
  error.value = null
  try {
    gameCase.value = await fetchCase(id)
  } catch {
    error.value = 'Кейс не найден или недоступен.'
    gameCase.value = null
  } finally {
    loading.value = false
  }
}

const openQuantities = [1, 2, 3, 5, 10]
const batchTotalWon = computed(() => {
  return batchResults.value.reduce((sum: number, item: CaseOpening) => sum + Number(item.won_item_price), 0)
})

async function handleOpen(fast = false): Promise<void> {
  if (!gameCase.value || opening.value) return

  const price = Number(gameCase.value.price)
  const totalPrice = price * selectedQuantity.value
  const main = Number(balance.mainBalance)
  const bonus = Number(balance.bonusBalance)
  const available = main + bonus

  if (available < totalPrice) {
    showAppAlert(
      `Недостаточно средств. Нужно ${formatPrice(totalPrice)}, доступно ${formatPrice(available)} (бонусный ${formatPrice(bonus)} + основной ${formatPrice(main)}).`,
      { title: 'Недостаточно средств', variant: 'error' },
    )
    return
  }

  opening.value = true
  try {
    const result = await apiOpenCase(gameCase.value.id, {
      quantity: selectedQuantity.value,
      fast,
    })
    await balance.fetchBalances()

    if (!Array.isArray(result)) {
      lastOpening.value = result
      batchResults.value = []
      showRoulette.value = !fast
      showBatchResults.value = false
      return
    }

    if (result.length === 1 && !fast) {
      lastOpening.value = result[0]
      batchResults.value = []
      showRoulette.value = true
      showBatchResults.value = false
      return
    }

    batchResults.value = result
    showBatchResults.value = true
    showRoulette.value = false
  } catch (e: unknown) {
    const msg =
      e instanceof Error
        ? e.message
        : 'Не удалось открыть кейс. Попробуйте ещё раз.'
    showAppAlert(msg, { title: 'Ошибка', variant: 'error' })
  } finally {
    opening.value = false
  }
}

function onRouletteClose(): void {
  showRoulette.value = false
}

onMounted(() => {
  const id = Number(route.params.id)
  if (Number.isFinite(id)) void load(id)
})

watch(
  () => route.params.id,
  (id: string | string[]) => {
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
        @click="router.push({ name: 'cases' })"
      >
        ← К списку кейсов
      </button>
    </div>

    <div v-if="loading" class="rounded-xl border border-border bg-surface p-12 text-center text-text-secondary">
      Загрузка…
    </div>

    <div v-else-if="error || !gameCase" class="rounded-xl border border-border bg-surface p-12 text-center">
      <p class="text-text-primary font-semibold">{{ error || 'Кейс не найден.' }}</p>
      <router-link :to="{ name: 'cases' }" class="mt-3 inline-block text-sm text-primary hover:text-primary-light">
        Вернуться к списку
      </router-link>
    </div>

    <template v-else>
      <section class="rounded-xl border border-border bg-surface p-6 grid gap-6 lg:grid-cols-[320px_1fr] mb-8">
        <div class="flex items-center justify-center aspect-[4/5] rounded-lg bg-input p-4 overflow-hidden">
          <img
            v-if="gameCase.image_url"
            v-bind="
              gameCaseCoverImgAttrs({
                shadowColor: gameCase.shadow_color,
                tailwindFallback: 'drop-shadow-xl',
                baseClass: 'max-w-full max-h-full object-contain',
              })
            "
            :src="gameCase.image_url"
            :alt="gameCase.name"
          />
        </div>

        <div class="flex flex-col gap-5">
          <div>
            <p v-if="gameCase.category" class="text-xs uppercase tracking-wider text-text-muted mb-1">
              {{ gameCase.category.name }}
            </p>
            <h1 class="text-2xl md:text-3xl font-bold text-text-primary">{{ gameCase.name }}</h1>
            <p v-if="gameCase.description" class="text-sm text-text-secondary mt-2 leading-relaxed">
              {{ gameCase.description }}
            </p>
            <p v-else class="text-sm text-text-secondary mt-2">
              В этом кейсе {{ totalItems }} призов, распределённых по уровням редкости.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-md bg-body/50 border border-border p-3">
              <p class="text-xs text-text-muted mb-1">Цена открытия</p>
              <p class="text-lg font-bold text-primary">{{ formatPrice(gameCase.price) }}</p>
            </div>
            <div class="rounded-md bg-body/50 border border-border p-3">
              <p class="text-xs text-text-muted mb-1">Доступно на открытие</p>
              <p class="text-lg font-bold text-text-primary">
                {{ formatPrice(Number(balance.mainBalance) + Number(balance.bonusBalance)) }}
              </p>
              <p class="text-[11px] text-text-muted">
                Бонусный: {{ formatPrice(balance.bonusBalance) }} · Основной: {{ formatPrice(balance.mainBalance) }}
              </p>
            </div>
          </div>

          <div class="flex gap-2 flex-wrap">
            <p class="w-full text-xs text-text-muted">Количество открытий</p>
            <button
              v-for="qty in openQuantities"
              :key="`qty-${qty}`"
              type="button"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors border"
              :class="
                selectedQuantity === qty
                  ? 'bg-primary text-body border-primary'
                  : 'bg-body border-border text-text-secondary hover:text-text-primary hover:border-border-hover'
              "
              @click="selectedQuantity = qty"
            >
              {{ qty }}
            </button>
          </div>

          <div class="rounded-md bg-body/40 border border-border p-3">
            <p class="text-xs text-text-muted mb-1">Итого к списанию</p>
            <p class="text-lg font-bold text-primary">{{ formatPrice(Number(gameCase.price) * selectedQuantity) }}</p>
            <p class="text-[11px] text-text-muted">
              Вы выбрали {{ selectedQuantity }} {{ selectedQuantity === 1 ? 'открытие' : selectedQuantity < 5 ? 'открытия' : 'открытий' }}
            </p>
          </div>

          <div class="flex gap-3 flex-wrap">
            <AppButton variant="primary" size="lg" :loading="opening" @click="handleOpen(false)">
              Открыть x{{ selectedQuantity }} за {{ formatPrice(Number(gameCase.price) * selectedQuantity) }}
            </AppButton>
            <AppButton variant="secondary" size="lg" :loading="opening" @click="handleOpen(true)">
              Открыть быстро x{{ selectedQuantity }}
            </AppButton>
          </div>

          <p class="text-[11px] text-text-muted leading-relaxed">
            Результат открытия рассчитывается на сервере на основе шансов уровней.
            Выигранный предмет попадёт в «Инвентарь кейсов» в вашем профиле.
          </p>
        </div>
      </section>

      <section class="space-y-6">
        <h2 class="text-lg font-semibold text-text-primary">Возможные призы и шансы</h2>

        <div
          v-for="level in gameCase.levels"
          :key="level.id"
          class="rounded-xl border border-border bg-surface p-5"
        >
          <div class="flex items-center justify-between gap-4 mb-4 flex-wrap">
            <div>
              <p class="text-xs text-text-muted">Уровень {{ level.level }}</p>
              <h3 class="text-base font-semibold text-text-primary">{{ level.name }}</h3>
            </div>
            <div class="rounded-md bg-body/40 border border-border px-3 py-1.5">
              <span class="text-sm font-bold text-primary">{{ Number(level.chance).toFixed(4) }}%</span>
            </div>
          </div>

          <div v-if="level.items?.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div
              v-for="prize in level.items"
              :key="prize.id"
              class="rounded-lg border border-border bg-body/30 p-3"
              :class="rarityRingClass(prize.rarity)"
            >
              <div class="aspect-square bg-input rounded-md mb-2 flex items-center justify-center overflow-hidden p-2">
                <img
                  v-if="prize.image_url"
                  :src="prize.image_url"
                  :alt="prize.name"
                  class="max-w-full max-h-full object-contain"
                  loading="lazy"
                />
              </div>
              <p class="text-xs text-text-primary line-clamp-2 min-h-[2rem]">{{ prize.name }}</p>
              <p class="text-[10px]" :class="wearTextClass(prize.wear)">{{ prize.wear }}</p>
              <p class="text-[10px] text-text-muted mb-1">{{ rarityLabel(prize.rarity) }}</p>
              <p class="text-sm font-bold text-primary">{{ formatPrice(prize.price) }}</p>
            </div>
          </div>
          <p v-else class="text-sm text-text-muted">Предметов нет.</p>
        </div>
      </section>
    </template>

    <RouletteModal
      v-if="showRoulette && lastOpening && gameCase"
      :opening="lastOpening"
      :game-case="gameCase"
      @close="onRouletteClose"
      @sold="(upd) => { lastOpening = upd }"
    />

    <Teleport to="body">
      <div
        v-if="showBatchResults"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
        role="dialog"
        aria-modal="true"
      >
        <div class="w-full max-w-5xl mx-4 rounded-2xl border border-border bg-surface p-6 flex flex-col gap-4 shadow-2xl">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-text-primary">Результаты открытия x{{ batchResults.length }}</h2>
              <p class="text-sm text-text-secondary">
                Суммарная стоимость: {{ formatPrice(batchTotalWon) }}
              </p>
            </div>
            <button
              type="button"
              class="px-4 py-2 rounded-md border border-border text-sm text-text-secondary hover:text-text-primary transition-colors"
              @click="showBatchResults = false"
            >
              Закрыть
            </button>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 max-h-[60vh] overflow-y-auto pr-1">
            <div
              v-for="openingItem in batchResults"
              :key="openingItem.id"
              class="rounded-lg border border-border bg-body/30 p-3"
              :style="openingItem.won_item?.rarity_color ? { boxShadow: `0 0 0 1px ${openingItem.won_item.rarity_color}50` } : {}"
            >
              <div class="aspect-square bg-input rounded-md mb-2 flex items-center justify-center overflow-hidden p-2">
                <img
                  v-if="openingItem.won_item?.image_url"
                  :src="openingItem.won_item.image_url"
                  :alt="openingItem.won_item?.name"
                  class="max-w-full max-h-full object-contain"
                  loading="lazy"
                />
              </div>
              <p class="text-xs text-text-primary line-clamp-2 min-h-[2rem]">{{ openingItem.won_item?.name ?? 'Предмет' }}</p>
              <p class="text-sm font-bold text-primary mt-1">{{ formatPrice(openingItem.won_item_price) }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
