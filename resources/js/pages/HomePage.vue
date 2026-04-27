<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/ui/AppButton.vue'
import MarketItemCard from '@/components/market/MarketItemCard.vue'
import CaseCardSkeleton from '@/components/ui/CaseCardSkeleton.vue'
import MarketItemCardSkeleton from '@/components/ui/MarketItemCardSkeleton.vue'
import { fetchMarketItems, fetchFeaturedCases, fetchSiteSettings } from '@/utils/market'
import type { MarketItem, GameCase } from '@/types/models'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@/utils/format'
import { gameCaseCoverImgAttrs } from '@/utils/caseVisual'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()

const ASSET = '/images/playstore'

const liveItems = ref<MarketItem[]>([])
const liveItemsLoading = ref(true)

const featuredCases = ref<GameCase[]>([])
const featuredCasesLoading = ref(true)
const featuredCasesError = ref(false)
/** Если false — не подставляем статичные картинки кейсов (витрина только из API). */
const showDemoData = ref(true)

const caseFallbackImages = [
  `${ASSET}/goods/csgo/case/chroma.png`,
  `${ASSET}/goods/csgo/case/hydra.png`,
  `${ASSET}/goods/csgo/case/glove.png`,
  `${ASSET}/goods/csgo/case/spectrum.png`,
  `${ASSET}/goods/csgo/case/gamma.png`,
  `${ASSET}/goods/csgo/case/falchion.png`,
  `${ASSET}/goods/csgo/case/revolver.png`,
  `${ASSET}/goods/csgo/case/wildfire.png`,
] as const

type HomeShowcaseCase = {
  id: number
  title: string
  price: string
  image: string
  live: boolean
  shadow_color: string | null
}

const casesFallback = [
  { id: 1, title: 'Chroma', price: '249 ₽', image: caseFallbackImages[0] },
  { id: 2, title: 'Hydra', price: '289 ₽', image: caseFallbackImages[1] },
  { id: 3, title: 'Glove', price: '349 ₽', image: caseFallbackImages[2] },
  { id: 4, title: 'Spectrum', price: '269 ₽', image: caseFallbackImages[3] },
  { id: 5, title: 'Gamma', price: '259 ₽', image: caseFallbackImages[4] },
  { id: 6, title: 'Falchion', price: '279 ₽', image: caseFallbackImages[5] },
  { id: 7, title: 'Revolver', price: '189 ₽', image: caseFallbackImages[6] },
  { id: 8, title: 'Wildfire', price: '329 ₽', image: caseFallbackImages[7] },
]

const marqueeSkeletonCount = 14

const marqueeStrip = [
  ...caseFallbackImages,
  `${ASSET}/goods/csgo/weapon/ak47.png`,
  `${ASSET}/goods/csgo/weapon/awp.png`,
  `${ASSET}/goods/csgo/weapon/m4a4.png`,
  `${ASSET}/goods/csgo/weapon/usp.png`,
  `${ASSET}/goods/csgo/weapon/glock.png`,
  `${ASSET}/goods/csgo/weapon/deagle.png`,
  `${ASSET}/goods/csgo/case/bravo.png`,
  `${ASSET}/goods/csgo/case/phoenix.png`,
  `${ASSET}/goods/csgo/case/spectrum2.png`,
  `${ASSET}/goods/csgo/case/chroma2.png`,
  `${ASSET}/goods/csgo/weapon/aug.png`,
  `${ASSET}/goods/csgo/case/knifecovert.png`,
] as const

const casesForShowcase = computed((): HomeShowcaseCase[] => {
  if (featuredCases.value.length > 0) {
    return featuredCases.value.map((c): HomeShowcaseCase => ({
      id: c.id,
      title: c.name,
      price: formatPrice(c.price),
      image: c.image_url || caseFallbackImages[0],
      shadow_color: c.shadow_color ?? null,
      live: true,
    }))
  }
  if (showDemoData.value) {
    return casesFallback.map(
      (c): HomeShowcaseCase => ({ ...c, shadow_color: null, live: false }),
    )
  }
  return []
})

type MarqueeItem = { src: string; alt: string; caseId: number | null; shadow_color?: string | null }

const marqueeFallbackItems = computed((): MarqueeItem[] =>
  marqueeStrip.map((src) => ({ src, alt: '', caseId: null })),
)

const marqueeItems = computed((): MarqueeItem[] => {
  if (featuredCases.value.length > 0) {
    return featuredCases.value.map((c) => ({
      src: c.image_url || caseFallbackImages[0],
      alt: c.name,
      caseId: c.id,
      shadow_color: c.shadow_color ?? null,
    }))
  }
  if (showDemoData.value) {
    return marqueeFallbackItems.value
  }
  return []
})

const heroBannerImage = `${ASSET}/slider/csgo/sl-1.jpg`
const steamBannerImage = `${ASSET}/banner-2.jpg`

function goCases(): void {
  void router.push({ name: 'cases' })
}

function goCase(id: number): void {
  void router.push({ name: 'case-detail', params: { id } })
}

function onMarqueeClick(item: MarqueeItem): void {
  if (item.caseId != null) {
    goCase(item.caseId)
    return
  }
  goCases()
}

function goUpgrade(): void {
  if (auth.isAuthenticated) {
    void router.push({ name: 'upgrade' })
    return
  }
  auth.steamLogin()
}

function openItem(it: MarketItem): void {
  void router.push({ name: 'market-item', params: { id: it.id } })
}

function toggleCart(it: MarketItem): void {
  cart.toggle(it)
}

onMounted(async () => {
  try {
    const site = await fetchSiteSettings()
    showDemoData.value = site.show_demo_data
  } catch {
    showDemoData.value = true
  }

  try {
    const res = await fetchMarketItems({ page: 1, per_page: 12 })
    liveItems.value = res.data
  } catch {
    liveItems.value = []
  } finally {
    liveItemsLoading.value = false
  }

  try {
    featuredCases.value = await fetchFeaturedCases()
  } catch {
    featuredCasesError.value = true
  } finally {
    featuredCasesLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-14 pb-16 -mx-6 px-6">
    <div
      class="relative -mx-6 mb-10 overflow-hidden border-y border-border bg-sidebar/90 py-3 md:py-4 marquee-wrap min-h-[4.5rem] md:min-h-[5.5rem]"
      :data-static-marquee="featuredCasesLoading ? 'true' : undefined"
      role="presentation"
    >
      <div v-if="featuredCasesLoading" class="marquee-track marquee-track--skeleton">
        <template v-for="dup in [0, 1]" :key="'sk-dup-' + dup">
          <div
            v-for="n in marqueeSkeletonCount"
            :key="`${dup}-${n}`"
            class="marquee-item shrink-0 flex items-center justify-center px-2 md:px-4"
          >
            <div
              class="h-14 w-[72px] md:h-[4.5rem] md:w-[100px] max-w-[100px] md:max-w-[120px] rounded-md border border-border bg-input animate-pulse"
              aria-hidden="true"
            />
          </div>
        </template>
      </div>
      <div v-else-if="marqueeItems.length === 0" class="px-4 py-2 text-center text-xs text-text-muted">
        Бегущая строка с кейсами появится после добавления популярных кейсов или при включении демо-витрины.
      </div>
      <div v-else class="marquee-track">
        <template v-for="dup in [0, 1]" :key="dup">
          <button
            v-for="(item, idx) in marqueeItems"
            :key="`${dup}-${idx}`"
            type="button"
            class="marquee-item shrink-0 flex items-center justify-center px-2 md:px-4 opacity-90 hover:opacity-100 transition-opacity"
            @click="onMarqueeClick(item)"
          >
            <img
              v-bind="
                gameCaseCoverImgAttrs({
                  shadowColor: item.shadow_color,
                  tailwindFallback: 'drop-shadow-md',
                  baseClass:
                    'h-14 w-auto md:h-[4.5rem] max-w-[100px] md:max-w-[120px] object-contain pointer-events-none',
                })
              "
              :src="item.src"
              :alt="item.alt"
              width="120"
              height="72"
              loading="lazy"
              draggable="false"
            >
          </button>
        </template>
      </div>
    </div>

    <section
      class="relative overflow-hidden rounded-xl border border-border bg-surface min-h-[320px] md:min-h-[380px] flex flex-col md:flex-row md:items-stretch"
    >
      <div
        class="absolute inset-0 opacity-90 bg-linear-to-br from-[#0f0f14] via-surface to-[#15101a] pointer-events-none"
        aria-hidden="true"
      />
      <div
        class="absolute inset-0 opacity-[0.12] pointer-events-none"
        style="
          background-image: radial-gradient(circle at 20% 30%, rgba(245, 158, 11, 0.35), transparent 45%),
            radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.25), transparent 40%);
        "
        aria-hidden="true"
      />
      <div class="relative z-10 flex-1 flex flex-col justify-center p-8 md:p-12 md:max-w-[52%]">
        <p class="text-xs uppercase tracking-[0.2em] text-text-muted mb-3">CS2 · P2P · Кейсы</p>
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-text-primary leading-tight">
          Маркет и кейсы <span class="text-primary">в одной витрине</span>
        </h1>
        <p class="mt-4 text-text-secondary text-base md:text-lg max-w-xl leading-relaxed">
          Покупай скины у игроков, открывай кейсы и делай апгрейды — всё в одном месте.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
          <router-link to="/market">
            <AppButton variant="secondary" size="lg">Маркетплейс</AppButton>
          </router-link>
          <AppButton variant="primary" size="lg" @click="goCases">Кейсы</AppButton>
          <AppButton variant="ghost" size="lg" @click="goUpgrade">Апгрейд</AppButton>
        </div>
        <p v-if="!auth.isAuthenticated" class="mt-6 text-sm text-text-muted">
          Вход через Steam — в шапке сайта.
        </p>
      </div>
      <div
        class="relative z-10 flex-1 min-h-[200px] md:min-h-0 border-t md:border-t-0 md:border-l border-border bg-body/40 flex items-center justify-center p-4 md:p-8"
      >
        <div class="w-full max-w-lg aspect-video rounded-lg border border-border overflow-hidden shadow-lg">
          <img
            :src="heroBannerImage"
            alt=""
            class="w-full h-full object-cover object-center"
            width="960"
            height="540"
            decoding="async"
          >
        </div>
      </div>
    </section>

    <section>
      <div class="flex items-end justify-between gap-4 mb-5">
        <div>
          <h2 class="text-xl md:text-2xl font-bold text-text-primary">Популярные кейсы</h2>
          <p class="text-sm text-text-secondary mt-1">
            Подборка для главной задаётся в админке
            <span v-if="featuredCases.length > 0" class="text-text-muted">· активные отмеченные кейсы</span>
            <span v-else-if="featuredCasesError && showDemoData" class="text-text-muted">· не удалось загрузить, показаны примеры</span>
            <span
              v-else-if="!showDemoData && featuredCases.length === 0 && !featuredCasesLoading"
              class="text-text-muted"
            >· демо-витрина отключена в настройках</span>
          </p>
        </div>
        <router-link v-if="auth.isAuthenticated" to="/cases" class="text-sm font-semibold text-primary hover:text-primary-light shrink-0">
          Все кейсы →
        </router-link>
        <button v-else type="button" class="text-sm font-semibold text-primary hover:text-primary-light shrink-0" @click="auth.steamLogin">
          Войти и открыть →
        </button>
      </div>
      <div
        class="flex gap-4 overflow-x-auto pb-2 -mx-1 px-1 scroll-smooth snap-x snap-mandatory min-h-[14.5rem] sm:min-h-[15.5rem]"
        style="scrollbar-width: thin"
      >
        <template v-if="featuredCasesLoading">
          <div
            v-for="n in 8"
            :key="'fc-sk-' + n"
            class="snap-start shrink-0 w-[140px] sm:w-[160px] h-full min-h-[13rem] flex flex-col"
          >
            <CaseCardSkeleton ribbon />
          </div>
        </template>
        <template v-else-if="casesForShowcase.length === 0">
          <div
            class="snap-start min-w-0 flex-1 rounded-lg border border-border bg-surface px-6 py-10 text-center text-text-secondary text-sm"
          >
            Популярных кейсов пока нет. Добавьте кейсы в админке или включите показ демо-данных.
          </div>
        </template>
        <template v-else>
          <button
            v-for="c in casesForShowcase"
            :key="c.id"
            type="button"
            class="snap-start shrink-0 w-[140px] sm:w-[160px] text-left group"
            @click="c.live ? goCase(c.id) : goCases()"
          >
            <div
              class="aspect-[4/5] rounded-lg border border-border overflow-hidden bg-input flex items-center justify-center p-3 transition-transform duration-200 group-hover:scale-[1.03] group-hover:border-border-hover group-hover:shadow-lg"
            >
              <img
                v-bind="
                  gameCaseCoverImgAttrs({
                    shadowColor: c.shadow_color,
                    tailwindFallback: 'drop-shadow-md',
                    baseClass: 'max-h-full max-w-full object-contain',
                  })
                "
                :src="c.image"
                :alt="c.title"
                width="160"
                height="200"
                loading="lazy"
              >
            </div>
            <p class="mt-2 text-sm font-semibold text-text-primary truncate">{{ c.title }}</p>
            <p class="text-sm font-bold text-primary">{{ c.price }}</p>
          </button>
        </template>
      </div>
    </section>

    <section>
      <div class="flex items-end justify-between gap-4 mb-5">
        <div>
          <h2 class="text-xl md:text-2xl font-bold text-text-primary">Популярное на маркете</h2>
          <p class="text-sm text-text-secondary mt-1">Актуальные лоты от продавцов</p>
        </div>
        <router-link to="/market" class="text-sm font-semibold text-primary hover:text-primary-light shrink-0">
          В маркет →
        </router-link>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 min-h-[26rem]">
        <template v-if="liveItemsLoading">
          <MarketItemCardSkeleton v-for="n in 12" :key="'mi-sk-' + n" compact />
        </template>
        <template v-else-if="liveItems.length === 0">
          <div class="col-span-full rounded-lg border border-border bg-surface p-8 text-center text-text-secondary">
            Пока нет активных лотов.
          </div>
        </template>
        <template v-else>
          <MarketItemCard
            v-for="it in liveItems"
            :key="it.id"
            :item="it"
            :in-cart="cart.has(it.id)"
            compact
            @click="openItem"
            @add-to-cart="toggleCart"
          />
        </template>
      </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="rounded-lg border border-border bg-surface p-6">
        <h3 class="font-semibold text-text-primary">P2P</h3>
        <p class="text-sm text-text-secondary mt-2 leading-relaxed">Покупка у игроков, сделки и статусы.</p>
      </div>
      <div class="rounded-lg border border-border bg-surface p-6">
        <h3 class="font-semibold text-text-primary">Steam</h3>
        <p class="text-sm text-text-secondary mt-2 leading-relaxed">Вход и обмен в экосистеме Counter-Strike 2.</p>
      </div>
      <div class="rounded-lg border border-border bg-surface p-6">
        <h3 class="font-semibold text-text-primary">Кейсы и апгрейд</h3>
        <p class="text-sm text-text-secondary mt-2 leading-relaxed">Отдельные разделы приложения.</p>
      </div>
    </section>

    <section class="rounded-xl border border-border overflow-hidden bg-sidebar">
      <a
        href="https://store.steampowered.com/app/730/CounterStrike_Global_Offensive/"
        target="_blank"
        rel="noopener noreferrer"
        class="block hover:opacity-95 transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-border-focus"
      >
        <img
          :src="steamBannerImage"
          alt="Counter-Strike в Steam"
          class="w-full h-auto max-h-[220px] md:max-h-[280px] object-cover object-center"
          width="1200"
          height="280"
          loading="lazy"
        >
      </a>
    </section>
  </div>
</template>

<style scoped>
.marquee-wrap {
  mask-image: linear-gradient(to right, transparent, black 6%, black 94%, transparent);
}

.marquee-track {
  display: flex;
  width: max-content;
  align-items: center;
  gap: 2rem;
  animation: sa-home-marquee 50s linear infinite;
}

.marquee-track--skeleton {
  animation: none;
  width: max-content;
}

.marquee-wrap[data-static-marquee='true'] .marquee-track {
  animation: none;
}

@media (prefers-reduced-motion: reduce) {
  .marquee-track {
    animation: none;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
    max-width: 100%;
  }
}

@keyframes sa-home-marquee {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-50%);
  }
}
</style>
