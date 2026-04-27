<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import EmptyStateGraphic from '@/components/ui/EmptyStateGraphic.vue'
import { fetchCaseInventory } from '@/utils/market'
import type { CaseOpening } from '@/types/models'
import { formatPrice } from '@/utils/format'
import { showAppAlert } from '@/composables/appDialog'

const loading = ref(true)
const availableItems = ref<CaseOpening[]>([])
const selectedIds = ref<number[]>([])

const selectedItems = computed(() =>
  availableItems.value.filter((item: CaseOpening) => selectedIds.value.includes(item.id)),
)

const selectedTotal = computed(() =>
  selectedItems.value.reduce((sum: number, item: CaseOpening) => sum + Number(item.won_item_price), 0),
)

async function load(): Promise<void> {
  loading.value = true
  try {
    const response = await fetchCaseInventory({ status: 'in_inventory', per_page: 40 })
    availableItems.value = response.data
  } finally {
    loading.value = false
  }
}

function toggleSelection(openingId: number): void {
  if (selectedIds.value.includes(openingId)) {
    selectedIds.value = selectedIds.value.filter((id: number) => id !== openingId)
    return
  }

  if (selectedIds.value.length >= 4) {
    showAppAlert('Для апгрейда можно выбрать максимум 4 предмета.', { title: 'Лимит выбора', variant: 'info' })
    return
  }

  selectedIds.value.push(openingId)
}

function startUpgrade(): void {
  showAppAlert('Логика апгрейда будет подключена следующим шагом. Выбранные предметы уже готовы к интеграции.', {
    title: 'Апгрейд в работе',
    variant: 'info',
  })
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-text-primary">Апгрейд</h1>
      <p class="text-sm text-text-secondary mt-1">Выберите до 4 предметов из инвентаря кейсов.</p>
    </div>

    <div class="rounded-xl border border-border bg-surface p-4 flex items-center justify-between gap-3 flex-wrap">
      <p class="text-sm text-text-secondary">
        Выбрано: <span class="text-text-primary font-semibold">{{ selectedItems.length }}</span>
      </p>
      <p class="text-sm text-text-secondary">
        Сумма ставки: <span class="text-primary font-semibold">{{ formatPrice(selectedTotal) }}</span>
      </p>
      <button
        type="button"
        class="px-4 py-2 rounded-md bg-primary text-body text-sm font-semibold hover:bg-primary-hover transition-colors disabled:opacity-50"
        :disabled="selectedItems.length === 0"
        @click="startUpgrade"
      >
        Начать апгрейд
      </button>
    </div>

    <div v-if="loading" class="bg-surface border border-border rounded-xl p-12 text-center">
      <p class="text-text-secondary">Загрузка предметов...</p>
    </div>

    <div v-else-if="availableItems.length === 0" class="bg-surface border border-border rounded-xl p-12 text-center">
      <EmptyStateGraphic variant="upgrade" />
      <h2 class="text-xl font-semibold mb-2 text-text-primary">Нет доступных предметов</h2>
      <p class="text-text-secondary">Откройте кейс и получите предметы для апгрейда.</p>
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
      <button
        v-for="item in availableItems"
        :key="item.id"
        type="button"
        class="rounded-xl border bg-surface p-3 text-left transition-colors"
        :class="selectedIds.includes(item.id) ? 'border-primary' : 'border-border hover:border-border-hover'"
        @click="toggleSelection(item.id)"
      >
        <div class="aspect-square bg-body/40 rounded-md flex items-center justify-center p-2 overflow-hidden mb-2">
          <img
            v-if="item.won_item?.image_url"
            :src="item.won_item.image_url"
            :alt="item.won_item.name"
            class="max-w-full max-h-full object-contain"
            loading="lazy"
          />
        </div>
        <p class="text-xs text-text-primary line-clamp-2 min-h-[2rem]">{{ item.won_item?.name ?? 'Предмет' }}</p>
        <p class="text-sm font-bold text-primary mt-1">{{ formatPrice(item.won_item_price) }}</p>
      </button>
    </div>
  </div>
</template>
