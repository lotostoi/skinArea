<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyStateGraphic from '@/components/ui/EmptyStateGraphic.vue'
import { fetchMarketItems } from '@/utils/market'
import type { MarketItem } from '@/types/models'

const items = ref<MarketItem[]>([])
const loading = ref(true)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const error = ref<string | null>(null)

function formatPrice(p: string): string {
  return `${Number(p).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

async function load(p = page.value) {
  loading.value = true
  error.value = null
  try {
    const res = await fetchMarketItems(p, 24)
    items.value = res.data
    page.value = res.meta.current_page
    lastPage.value = res.meta.last_page
    total.value = res.meta.total
  } catch {
    error.value = 'Не удалось загрузить маркет'
    items.value = []
  } finally {
    loading.value = false
  }
}

function prevPage() {
  if (page.value > 1) void load(page.value - 1)
}

function nextPage() {
  if (page.value < lastPage.value) void load(page.value + 1)
}

onMounted(() => {
  void load(1)
})
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold text-text-primary">Маркетплейс</h1>
      <p v-if="total > 0" class="text-sm text-text-secondary">Лотов: {{ total }}</p>
    </div>

    <p v-if="error" class="text-danger text-sm mb-4">{{ error }}</p>

    <div
      v-if="loading"
      class="bg-surface border border-border rounded-xl p-12 text-center text-text-secondary"
    >
      Загрузка…
    </div>

    <div
      v-else-if="items.length === 0"
      class="bg-surface border border-border rounded-xl p-12 text-center"
    >
      <EmptyStateGraphic variant="market" />
      <h2 class="text-xl font-semibold mb-2 text-text-primary">Пока нет лотов</h2>
      <p class="text-text-secondary text-sm">Выставьте скины в профиле — они появятся здесь.</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
        <div
          v-for="it in items"
          :key="it.id"
          class="group border border-border rounded-lg bg-surface p-3 transition-transform hover:scale-[1.03] hover:border-border-hover hover:shadow-lg"
        >
          <div
            class="aspect-square bg-input rounded-md mb-2 flex items-center justify-center overflow-hidden"
          >
            <img
              v-if="it.image_url"
              :src="it.image_url"
              :alt="it.name"
              class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform"
            />
            <span v-else class="text-text-muted text-xs">Нет фото</span>
          </div>
          <p class="text-xs text-text-primary line-clamp-2 min-h-[2.5rem] mb-1">{{ it.name }}</p>
          <p class="text-[10px] text-text-muted mb-1">{{ it.wear }}</p>
          <p class="text-base font-bold text-primary">{{ formatPrice(it.price) }}</p>
          <p v-if="it.seller" class="text-[10px] text-text-muted truncate mt-1">
            {{ it.seller.username }}
          </p>
        </div>
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
</template>
