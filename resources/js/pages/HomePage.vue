<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/ui/AppButton.vue'

const auth = useAuthStore()
const router = useRouter()

const ASSET = '/images/playstore'

const marketMock = [
  { id: 1, name: 'AK-47 | Redline', wear: 'FT', price: '12 490 ₽', image: `${ASSET}/goods/csgo/weapon/ak47.png` },
  { id: 2, name: 'AWP | Atheris', wear: 'MW', price: '8 200 ₽', image: `${ASSET}/goods/csgo/weapon/awp.png` },
  { id: 3, name: 'M4A4', wear: 'FN', price: '54 900 ₽', image: `${ASSET}/goods/csgo/weapon/m4a4.png` },
  { id: 4, name: 'USP-S', wear: 'WW', price: '6 100 ₽', image: `${ASSET}/goods/csgo/weapon/usp.png` },
  { id: 5, name: 'Glock-18', wear: 'FN', price: '89 000 ₽', image: `${ASSET}/goods/csgo/weapon/glock.png` },
  { id: 6, name: 'Desert Eagle', wear: 'FN', price: '42 300 ₽', image: `${ASSET}/goods/csgo/weapon/deagle.png` },
]

const caseImages = [
  `${ASSET}/goods/csgo/case/chroma.png`,
  `${ASSET}/goods/csgo/case/hydra.png`,
  `${ASSET}/goods/csgo/case/glove.png`,
  `${ASSET}/goods/csgo/case/spectrum.png`,
  `${ASSET}/goods/csgo/case/gamma.png`,
  `${ASSET}/goods/csgo/case/falchion.png`,
  `${ASSET}/goods/csgo/case/revolver.png`,
  `${ASSET}/goods/csgo/case/wildfire.png`,
] as const

const casesMock = [
  { id: 1, title: 'Chroma', price: '249 ₽', image: caseImages[0] },
  { id: 2, title: 'Hydra', price: '199 ₽', image: caseImages[1] },
  { id: 3, title: 'Glove', price: '179 ₽', image: caseImages[2] },
  { id: 4, title: 'Spectrum', price: '219 ₽', image: caseImages[3] },
  { id: 5, title: 'Gamma', price: '159 ₽', image: caseImages[4] },
  { id: 6, title: 'Falchion', price: '229 ₽', image: caseImages[5] },
  { id: 7, title: 'Revolver', price: '189 ₽', image: caseImages[6] },
  { id: 8, title: 'Wildfire', price: '269 ₽', image: caseImages[7] },
]

const heroBannerImage = `${ASSET}/slider/csgo/sl-1.jpg`
const steamBannerImage = `${ASSET}/banner-2.jpg`

const marqueeStrip = [
  ...caseImages,
  ...marketMock.map((m) => m.image),
  `${ASSET}/goods/csgo/case/bravo.png`,
  `${ASSET}/goods/csgo/case/phoenix.png`,
  `${ASSET}/goods/csgo/case/spectrum2.png`,
  `${ASSET}/goods/csgo/case/chroma2.png`,
  `${ASSET}/goods/csgo/weapon/aug.png`,
  `${ASSET}/goods/csgo/case/knifecovert.png`,
] as const

function goCases(): void {
  if (auth.isAuthenticated) {
    void router.push({ name: 'cases' })
    return
  }
  auth.steamLogin()
}

function goUpgrade(): void {
  if (auth.isAuthenticated) {
    void router.push({ name: 'upgrade' })
    return
  }
  auth.steamLogin()
}
</script>

<template>
  <div class="space-y-14 pb-16 -mx-6 px-6">
    <div
      class="relative -mx-6 mb-10 overflow-hidden border-y border-border bg-sidebar/90 py-3 md:py-4 marquee-wrap"
      role="presentation"
    >
      <div class="marquee-track">
        <template v-for="dup in [0, 1]" :key="dup">
          <button
            v-for="(src, idx) in marqueeStrip"
            :key="`${dup}-${idx}`"
            type="button"
            class="marquee-item shrink-0 flex items-center justify-center px-2 md:px-4 opacity-90 hover:opacity-100 transition-opacity"
            @click="goCases"
          >
            <img
              :src="src"
              alt=""
              class="h-14 w-auto md:h-[4.5rem] max-w-[100px] md:max-w-[120px] object-contain drop-shadow-md pointer-events-none"
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
          <h2 class="text-xl md:text-2xl font-bold text-text-primary">Кейсы CS2</h2>
          <p class="text-sm text-text-secondary mt-1">Выбери кейс и открой его</p>
        </div>
        <router-link v-if="auth.isAuthenticated" to="/cases" class="text-sm font-semibold text-primary hover:text-primary-light shrink-0">
          Все кейсы →
        </router-link>
        <button v-else type="button" class="text-sm font-semibold text-primary hover:text-primary-light shrink-0" @click="auth.steamLogin">
          Войти и открыть →
        </button>
      </div>
      <div
        class="flex gap-4 overflow-x-auto pb-2 -mx-1 px-1 scroll-smooth snap-x snap-mandatory"
        style="scrollbar-width: thin"
      >
        <button
          v-for="c in casesMock"
          :key="c.id"
          type="button"
          class="snap-start shrink-0 w-[140px] sm:w-[160px] text-left group"
          @click="goCases"
        >
          <div
            class="aspect-[4/5] rounded-lg border border-border overflow-hidden bg-input flex items-center justify-center p-3 transition-transform duration-200 group-hover:scale-[1.03] group-hover:border-border-hover group-hover:shadow-lg"
          >
            <img
              :src="c.image"
              :alt="c.title"
              class="max-h-full max-w-full object-contain drop-shadow-md"
              width="160"
              height="200"
              loading="lazy"
            >
          </div>
          <p class="mt-2 text-sm font-semibold text-text-primary truncate">{{ c.title }}</p>
          <p class="text-sm font-bold text-primary">{{ c.price }}</p>
        </button>
      </div>
    </section>

    <section>
      <div class="flex items-end justify-between gap-4 mb-5">
        <div>
          <h2 class="text-xl md:text-2xl font-bold text-text-primary">Популярное на маркете</h2>
          <p class="text-sm text-text-secondary mt-1">Популярные лоты на площадке</p>
        </div>
        <router-link to="/market" class="text-sm font-semibold text-primary hover:text-primary-light shrink-0">
          В маркет →
        </router-link>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <router-link
          v-for="m in marketMock"
          :key="m.id"
          to="/market"
          class="group rounded-lg border border-border bg-surface overflow-hidden transition-all duration-200 hover:border-border-hover hover:bg-surface-hover hover:scale-[1.02]"
        >
          <div class="aspect-square relative overflow-hidden bg-input flex items-center justify-center p-2">
            <img
              :src="m.image"
              :alt="m.name"
              class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-200"
              width="200"
              height="200"
              loading="lazy"
            >
            <div
              class="pointer-events-none absolute inset-0 bg-linear-to-t from-body/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"
              aria-hidden="true"
            />
          </div>
          <div class="p-3">
            <p class="text-xs font-medium text-wear-ft">{{ m.wear }}</p>
            <p class="text-sm font-semibold text-text-primary line-clamp-2 group-hover:text-primary-light transition-colors">
              {{ m.name }}
            </p>
            <p class="text-sm font-bold text-primary mt-1">{{ m.price }}</p>
          </div>
        </router-link>
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
