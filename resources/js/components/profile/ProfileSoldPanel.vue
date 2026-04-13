<script setup lang="ts">
import { ref, watch } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import { fetchProfileSold } from '@/utils/market'
import type { MarketItem } from '@/types/models'

const props = defineProps<{
  enabled: boolean
}>()

const items = ref<MarketItem[]>([])
const loading = ref(false)

async function load() {
  if (!props.enabled) {
    return
  }
  loading.value = true
  try {
    const res = await fetchProfileSold(1, 50)
    items.value = res.data
  } finally {
    loading.value = false
  }
}

function formatPrice(p: string): string {
  return `${Number(p).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

watch(
  () => props.enabled,
  (v: boolean) => {
    if (v) {
      void load()
    }
  },
  { immediate: true },
)

defineExpose({ reload: load })
</script>

<template>
  <div class="rounded-xl border border-border bg-surface p-6">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-text-primary">Проданные предметы</h3>
      <AppButton variant="secondary" size="sm" :loading="loading" @click="load">Обновить</AppButton>
    </div>
    <div
      v-if="loading"
      class="rounded-lg border border-border bg-input/40 py-10 text-center text-sm text-text-muted"
    >
      Загрузка…
    </div>
    <p v-else-if="items.length === 0" class="text-sm text-text-secondary">Нет проданных лотов.</p>
    <ul v-else class="space-y-3">
      <li
        v-for="it in items"
        :key="it.id"
        class="flex flex-wrap items-center gap-3 rounded-lg border border-border p-3"
      >
        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-md bg-input">
          <img
            v-if="it.image_url"
            :src="it.image_url"
            :alt="it.name"
            class="max-h-full max-w-full object-contain"
          />
        </div>
        <div class="min-w-0 flex-1">
          <p class="line-clamp-1 text-sm font-medium text-text-primary">{{ it.name }}</p>
          <p class="text-xs text-text-muted">{{ it.wear }} · {{ it.asset_id }}</p>
        </div>
        <p class="whitespace-nowrap text-sm font-bold text-primary">{{ formatPrice(it.price) }}</p>
      </li>
    </ul>
  </div>
</template>
