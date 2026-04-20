<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
import CaseCardSkeleton from '@/components/ui/CaseCardSkeleton.vue'
import EmptyStateGraphic from '@/components/ui/EmptyStateGraphic.vue'
import { fetchCases } from '@/utils/market'
import type { GameCase } from '@/types/models'
import { formatPrice } from '@/utils/format'

const router = useRouter()

const cases = ref<GameCase[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

type SortKey = 'default' | 'price_asc' | 'price_desc' | 'name_asc'
const sort = ref<SortKey>('default')
const categoryFilter = ref<number | null>(null)

const skeletonPlaceholders = 15

const groupedCategories = computed(() => {
  const map = new Map<number, { id: number; name: string; order: number }>()
  for (const c of cases.value) {
    const cat = c.category
    if (!cat) continue
    if (!map.has(cat.id)) {
      map.set(cat.id, { id: cat.id, name: cat.name, order: cat.sort_order ?? 0 })
    }
  }
  return Array.from(map.values()).sort((a, b) => a.order - b.order || a.name.localeCompare(b.name))
})

const filteredSortedCases = computed(() => {
  let list = cases.value.slice()
  if (categoryFilter.value !== null) {
    list = list.filter((c) => c.category_id === categoryFilter.value)
  }
  switch (sort.value) {
    case 'price_asc':
      list.sort((a, b) => Number(a.price) - Number(b.price))
      break
    case 'price_desc':
      list.sort((a, b) => Number(b.price) - Number(a.price))
      break
    case 'name_asc':
      list.sort((a, b) => a.name.localeCompare(b.name))
      break
    default:
      list.sort(
        (a, b) =>
          (a.category?.sort_order ?? 0) - (b.category?.sort_order ?? 0) ||
          (a.sort_order ?? 0) - (b.sort_order ?? 0),
      )
  }
  return list
})

function goCase(c: GameCase): void {
  void router.push({ name: 'case-detail', params: { id: c.id } })
}

onMounted(async () => {
  loading.value = true
  try {
    cases.value = await fetchCases()
  } catch {
    error.value = 'Не удалось загрузить кейсы'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Кейсы</h1>
        <p class="text-sm text-text-secondary mt-1">Открывайте кейсы и получайте скины CS2.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-text-secondary">Сортировка:</label>
        <select
          v-model="sort"
          class="bg-input border border-border rounded-md px-3 py-2 text-sm text-text-primary focus:border-border-focus focus:outline-none"
        >
          <option value="default">По умолчанию</option>
          <option value="price_asc">Цена ↑</option>
          <option value="price_desc">Цена ↓</option>
          <option value="name_asc">По названию</option>
        </select>
      </div>
    </div>

    <div v-if="groupedCategories.length > 1" class="flex flex-wrap items-center gap-2">
      <button
        type="button"
        class="text-sm px-3 py-1.5 rounded-md border transition-colors"
        :class="categoryFilter === null
          ? 'border-primary text-primary bg-primary/10'
          : 'border-border text-text-secondary hover:text-text-primary hover:border-border-hover'"
        @click="categoryFilter = null"
      >
        Все категории
      </button>
      <button
        v-for="c in groupedCategories"
        :key="c.id"
        type="button"
        class="text-sm px-3 py-1.5 rounded-md border transition-colors"
        :class="categoryFilter === c.id
          ? 'border-primary text-primary bg-primary/10'
          : 'border-border text-text-secondary hover:text-text-primary hover:border-border-hover'"
        @click="categoryFilter = c.id"
      >
        {{ c.name }}
      </button>
    </div>

    <p v-if="error" class="text-danger text-sm">{{ error }}</p>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 min-h-[28rem]">
      <template v-if="loading">
        <div v-for="n in skeletonPlaceholders" :key="'sk-' + n" class="min-h-0 flex">
          <CaseCardSkeleton class="flex-1" />
        </div>
      </template>

      <template v-else-if="filteredSortedCases.length === 0">
        <div class="col-span-full bg-surface border border-border rounded-xl p-12 text-center">
          <EmptyStateGraphic variant="cases" />
          <h2 class="text-xl font-semibold mb-2 text-text-primary">Кейсов пока нет</h2>
          <p class="text-text-secondary">Администратор добавит их в ближайшее время.</p>
        </div>
      </template>

      <template v-else>
        <button
          v-for="c in filteredSortedCases"
          :key="c.id"
          type="button"
          class="group relative flex flex-col rounded-lg border border-border bg-surface p-3 text-left transition-transform duration-200 hover:scale-[1.03] hover:border-border-hover hover:shadow-lg min-h-0"
          @click="goCase(c)"
        >
          <div class="aspect-[4/5] bg-input rounded-md mb-3 flex items-center justify-center overflow-hidden p-3">
            <img
              v-if="c.image_url"
              :src="c.image_url"
              :alt="c.name"
              class="max-w-full max-h-full object-contain drop-shadow-md group-hover:scale-105 transition-transform"
              loading="lazy"
            />
          </div>
          <p class="text-sm font-semibold text-text-primary truncate mb-1">{{ c.name }}</p>
          <p v-if="c.category" class="text-[11px] text-text-muted mb-2">{{ c.category.name }}</p>
          <div class="mt-auto flex items-center justify-between">
            <p class="text-base font-bold text-primary">{{ formatPrice(c.price) }}</p>
            <AppButton variant="primary" size="sm">Открыть</AppButton>
          </div>
        </button>
      </template>
    </div>
  </div>
</template>
