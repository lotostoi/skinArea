<script setup lang="ts">
import { ref, watch } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import { fetchProfileListings, removeMarketListing } from '@/utils/market'
import type { MarketItem } from '@/types/models'

const props = defineProps<{
  enabled: boolean
}>()

const emit = defineEmits<{
  changed: []
}>()

const items = ref<MarketItem[]>([])
const loading = ref(false)
const removingId = ref<number | null>(null)

async function load() {
  if (!props.enabled) return
  loading.value = true
  try {
    const res = await fetchProfileListings(1, 50)
    items.value = res.data
  } finally {
    loading.value = false
  }
}

async function cancel(id: number) {
  removingId.value = id
  try {
    await removeMarketListing(id)
    await load()
    emit('changed')
  } finally {
    removingId.value = null
  }
}

function formatPrice(p: string): string {
  return `${Number(p).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

watch(
  () => props.enabled,
  (v) => {
    if (v) void load()
  },
  { immediate: true },
)

defineExpose({ reload: load })
</script>

<template>
  <div class="bg-surface border border-border rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-text-primary">Активные продажи</h3>
      <AppButton variant="secondary" size="sm" :loading="loading" @click="load">Обновить</AppButton>
    </div>
    <p v-if="!loading && items.length === 0" class="text-text-secondary text-sm">Нет активных лотов.</p>
    <ul v-else class="space-y-3">
      <li
        v-for="it in items"
        :key="it.id"
        class="flex flex-wrap items-center gap-3 border border-border rounded-lg p-3"
      >
        <div class="w-14 h-14 bg-input rounded-md shrink-0 flex items-center justify-center overflow-hidden">
          <img
            v-if="it.image_url"
            :src="it.image_url"
            :alt="it.name"
            class="max-w-full max-h-full object-contain"
          />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-text-primary line-clamp-1">{{ it.name }}</p>
          <p class="text-xs text-text-muted">{{ it.wear }} · {{ it.asset_id }}</p>
        </div>
        <p class="text-sm font-bold text-primary whitespace-nowrap">{{ formatPrice(it.price) }}</p>
        <AppButton
          variant="danger"
          size="sm"
          :loading="removingId === it.id"
          @click="cancel(it.id)"
        >
          Снять
        </AppButton>
      </li>
    </ul>
  </div>
</template>
