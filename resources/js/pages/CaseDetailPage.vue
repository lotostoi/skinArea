<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
import { fetchCase } from '@/utils/market'
import type { GameCase } from '@/types/models'
import { useBalanceStore } from '@/stores/balance'
import { showAppAlert } from '@/composables/appDialog'
import {
  formatPrice,
  rarityLabel,
  rarityRingClass,
  wearTextClass,
} from '@/utils/format'

const route = useRoute()
const router = useRouter()
const balance = useBalanceStore()

const gameCase = ref<GameCase | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const opening = ref(false)

const totalItems = computed(
  () =>
    gameCase.value?.levels?.reduce((sum, lvl) => sum + (lvl.items?.length ?? 0), 0) ?? 0,
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

function openCase(): void {
  if (!gameCase.value) return
  const price = Number(gameCase.value.price)
  const main = Number(balance.mainBalance)
  if (main < price) {
    showAppAlert(
      `На основном балансе недостаточно средств. Нужно ${formatPrice(price)}, доступно ${formatPrice(main)}. Пополните баланс (функция в разработке).`,
      { title: 'Недостаточно средств', variant: 'error' },
    )
    return
  }
  opening.value = true
  showAppAlert(
    'Открытие кейса с анимацией рулетки и выдача приза будут подключены в следующем релизе. В демо-версии действие недоступно.',
    { title: 'В разработке' },
  )
  setTimeout(() => {
    opening.value = false
  }, 200)
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
            :src="gameCase.image_url"
            :alt="gameCase.name"
            class="max-w-full max-h-full object-contain drop-shadow-xl"
          />
        </div>

        <div class="flex flex-col gap-5">
          <div>
            <p v-if="gameCase.category" class="text-xs uppercase tracking-wider text-text-muted mb-1">
              {{ gameCase.category.name }}
            </p>
            <h1 class="text-2xl md:text-3xl font-bold text-text-primary">{{ gameCase.name }}</h1>
            <p class="text-sm text-text-secondary mt-2">
              В этом кейсе {{ totalItems }} призов, распределённых по уровням редкости.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-md bg-body/50 border border-border p-3">
              <p class="text-xs text-text-muted mb-1">Цена открытия</p>
              <p class="text-lg font-bold text-primary">{{ formatPrice(gameCase.price) }}</p>
            </div>
            <div class="rounded-md bg-body/50 border border-border p-3">
              <p class="text-xs text-text-muted mb-1">Ваш баланс</p>
              <p class="text-lg font-bold text-text-primary">{{ formatPrice(balance.mainBalance) }}</p>
            </div>
          </div>

          <div class="flex gap-3 flex-wrap">
            <AppButton variant="primary" size="lg" :loading="opening" @click="openCase">
              Открыть за {{ formatPrice(gameCase.price) }}
            </AppButton>
            <AppButton
              variant="secondary"
              size="lg"
              @click="showAppAlert('Быстрая продажа выигранных скинов за баланс — в разработке.', { title: 'В разработке' })"
            >
              Быстрая продажа (x5)
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
  </div>
</template>
