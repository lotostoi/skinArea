<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import type { CaseItem, CaseOpening, GameCase } from '@/types/models'
import { formatPrice } from '@/utils/format'
import { sellCaseOpening } from '@/utils/market'
import { useBalanceStore } from '@/stores/balance'
import { showAppAlert } from '@/composables/appDialog'

const props = defineProps<{
  opening: CaseOpening
  gameCase: GameCase
}>()

const emit = defineEmits<{
  close: []
  sold: [opening: CaseOpening]
}>()

const balance = useBalanceStore()
const selling = ref(false)
const soldSuccess = ref(false)
const animating = ref(true)
const wheelRef = ref<HTMLElement | null>(null)
const wheelItems = ref<CaseItem[]>([])
const wheelTransition = ref('none')
const wheelRotation = ref(0)
const winningSegmentIndex = ref(0)

const WHEEL_SEGMENTS = 16
const ORBIT_RADIUS_PX = 215

const wonItem = computed<CaseItem | null>(() => props.opening.won_item ?? null)

const allItems = computed<CaseItem[]>(() => {
  const pool: CaseItem[] = []
  for (const level of props.gameCase.levels ?? []) {
    if (level.items) pool.push(...level.items)
  }
  return pool
})

const segmentAngle = computed<number>(() => 360 / Math.max(wheelItems.value.length, 1))

const wheelStyle = computed<Record<string, string>>(() => ({
  transition: wheelTransition.value,
  transform: `rotate(${wheelRotation.value}deg)`,
}))

const winningItem = computed<CaseItem | null>(() => {
  return wheelItems.value[winningSegmentIndex.value] ?? wonItem.value ?? null
})

const animationDuration = computed<number>(() => {
  return (Number(import.meta.env.VITE_CASE_ANIM_SECONDS) || 4) * 1000
})

function buildWheelItems(): void {
  const fallbackPool = wonItem.value ? [wonItem.value] : []
  const pool = allItems.value.length > 0 ? allItems.value : fallbackPool

  if (pool.length === 0) {
    wheelItems.value = []
    animating.value = false
    return
  }

  const generated: CaseItem[] = []
  for (let i = 0; i < WHEEL_SEGMENTS; i++) {
    generated.push(pool[Math.floor(Math.random() * pool.length)])
  }

  winningSegmentIndex.value = Math.floor(Math.random() * WHEEL_SEGMENTS)
  if (wonItem.value) {
    generated[winningSegmentIndex.value] = wonItem.value
  }

  wheelItems.value = generated
}

function startAnimation(): void {
  const el = wheelRef.value
  if (!el || wheelItems.value.length === 0) {
    animating.value = false
    return
  }

  animating.value = true
  wheelTransition.value = 'none'
  wheelRotation.value = Math.floor(Math.random() * 360)

  const fullSpins = 7 + Math.floor(Math.random() * 2)
  const targetRotation =
    fullSpins * 360 - winningSegmentIndex.value * segmentAngle.value

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      wheelTransition.value = `transform ${animationDuration.value}ms cubic-bezier(0.12, 0.95, 0.24, 1)`
      wheelRotation.value = targetRotation
    })
  })

  setTimeout(() => {
    animating.value = false
  }, animationDuration.value + 100)
}

function itemOrbitStyle(index: number): Record<string, string> {
  const angle = index * segmentAngle.value

  return {
    transform: `rotate(${angle}deg) translateY(-${ORBIT_RADIUS_PX}px) rotate(${-angle}deg)`,
  }
}

function itemCardStyle(item: CaseItem, index: number): Record<string, string> {
  const style: Record<string, string> = {}
  const isWinner = !animating.value && index === winningSegmentIndex.value

  if (item.rarity_color) {
    style.borderColor = item.rarity_color
    style.boxShadow = isWinner
      ? `0 0 24px ${item.rarity_color}b3, 0 0 32px ${item.rarity_color}66`
      : `0 0 12px ${item.rarity_color}40`
  }

  return style
}

onMounted(() => {
  nextTick(() => {
    buildWheelItems()
    startAnimation()
  })
})

function onKeydown(e: KeyboardEvent): void {
  if (e.key === 'Escape' && !animating.value) emit('close')
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))

async function handleSell(): Promise<void> {
  if (selling.value) return
  selling.value = true
  try {
    const updated = await sellCaseOpening(props.opening.id)
    await balance.fetchBalances()
    soldSuccess.value = true
    emit('sold', updated)
    setTimeout(() => emit('close'), 1200)
  } catch {
    selling.value = false
  }
}

function handleInventoryStub(): void {
  showAppAlert('Функция отправки в инвентарь временно недоступна. Используйте продажу предмета.', {
    title: 'Скоро будет доступно',
    variant: 'info',
  })
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
      role="dialog"
      aria-modal="true"
    >
      <div class="w-full max-w-5xl mx-4 rounded-2xl border border-border bg-surface p-6 flex flex-col gap-6 shadow-2xl" style="animation: modalIn 0.22s ease">
        <div class="relative rounded-2xl border border-border bg-body/40 p-5 md:p-7 overflow-hidden">
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_42%,rgba(245,158,11,0.28),rgba(15,15,20,0.05)_44%,rgba(15,15,20,0.2)_100%)]" />
          <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(255,255,255,0))]" />

          <div class="relative mx-auto w-full max-w-[620px] aspect-square">
            <div class="absolute inset-[6%] rounded-full border border-border/70 bg-[#171722] shadow-[inset_0_0_50px_rgba(0,0,0,0.35)]" />
            <div class="absolute inset-[10%] rounded-full border border-border/70 bg-[#111118] overflow-hidden">
              <div
                ref="wheelRef"
                class="absolute inset-0 will-change-transform"
                :style="wheelStyle"
              >
                <div
                  v-for="(item, idx) in wheelItems"
                  :key="`segment-${idx}-${item.id}`"
                  class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"
                  :style="itemOrbitStyle(idx)"
                >
                  <div
                    class="w-24 h-28 rounded-lg border bg-[#13131d]/95 px-2 py-1.5 flex flex-col items-center justify-between gap-1"
                    :style="itemCardStyle(item, idx)"
                  >
                    <img
                      v-if="item.image_url"
                      :src="item.image_url"
                      :alt="item.name"
                      class="h-12 w-14 object-contain"
                      loading="lazy"
                    />
                    <p class="text-[10px] leading-tight text-center text-text-secondary line-clamp-2 min-h-[2rem]">
                      {{ item.name }}
                    </p>
                    <p class="text-[11px] font-bold text-primary">{{ formatPrice(item.price) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="absolute left-1/2 top-[5.5%] -translate-x-1/2 z-20 pointer-events-none">
              <div class="w-0 h-0 border-l-[15px] border-r-[15px] border-t-[24px] border-l-transparent border-r-transparent border-t-primary drop-shadow-[0_0_14px_rgba(245,158,11,0.65)]" />
            </div>

            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10 rounded-full w-24 h-24 border border-border bg-[#0f0f16]/95 shadow-[0_0_30px_rgba(0,0,0,0.45)] flex items-center justify-center">
              <div class="w-16 h-16 rounded-full border border-border/80 bg-body/70 flex items-center justify-center">
                <span class="text-[11px] uppercase tracking-wider text-text-muted font-semibold">Case</span>
              </div>
            </div>
          </div>

          <div class="relative mt-5 text-center">
            <p v-if="animating" class="text-sm text-text-secondary uppercase tracking-[0.16em]">
              Открываем кейс...
            </p>
            <p v-else class="text-xs text-text-muted uppercase tracking-[0.18em]">
              Рулетка остановилась
            </p>
          </div>
        </div>

        <Transition name="fade">
          <div v-if="!animating && winningItem" class="flex flex-col items-center gap-4">
            <p class="text-sm text-text-secondary uppercase tracking-widest">Вам выпало</p>
            <div
              class="flex items-center gap-5 rounded-xl border p-5 bg-body/40 w-full max-w-md"
              :style="winningItem.rarity_color ? { borderColor: winningItem.rarity_color, boxShadow: `0 0 24px ${winningItem.rarity_color}66` } : {}"
            >
              <img
                v-if="winningItem.image_url"
                :src="winningItem.image_url"
                :alt="winningItem.name"
                class="w-24 h-24 object-contain flex-none"
              />
              <div class="min-w-0">
                <p class="text-base font-semibold text-text-primary line-clamp-2">{{ winningItem.name }}</p>
                <p class="text-xs text-text-muted mt-0.5">{{ winningItem.wear }} · {{ winningItem.rarity }}</p>
                <p class="text-xl font-bold text-primary mt-2">{{ formatPrice(winningItem.price) }}</p>
              </div>
            </div>

            <p v-if="soldSuccess" class="text-sm text-success font-semibold">
              Продано! Баланс пополнен на {{ formatPrice(opening.won_item_price) }}
            </p>

            <div v-if="!soldSuccess" class="flex gap-3 flex-wrap justify-center">
              <button
                type="button"
                class="px-5 py-2.5 rounded-md bg-body border border-border text-sm text-text-primary hover:bg-hover transition-colors"
                @click="handleInventoryStub"
              >
                В инвентарь
              </button>
              <button
                type="button"
                class="px-5 py-2.5 rounded-md bg-primary text-body text-sm font-semibold hover:bg-primary-hover transition-colors disabled:opacity-50"
                :disabled="selling"
                @click="handleSell"
              >
                {{ selling ? 'Продажа...' : `Продать за ${formatPrice(opening.won_item_price)}` }}
              </button>
            </div>
          </div>
        </Transition>

      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes modalIn {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
