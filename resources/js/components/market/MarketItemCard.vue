<script setup lang="ts">
import { computed } from 'vue'
import type { MarketItem } from '@/types/models'
import { formatPrice, rarityRingClass, wearLabel, wearTextClass } from '@/utils/format'

const props = defineProps<{
  item: MarketItem
  inCart?: boolean
  compact?: boolean
}>()

defineEmits<{
  click: [item: MarketItem]
  addToCart: [item: MarketItem]
}>()

const ringClass = computed(() => rarityRingClass(props.item.rarity))
</script>

<template>
  <div
    class="group relative flex flex-col border border-border rounded-lg bg-surface p-3 transition-transform duration-200 hover:scale-[1.03] hover:border-border-hover hover:shadow-lg cursor-pointer"
    :class="[ringClass]"
    @click="$emit('click', item)"
  >
    <div class="aspect-square bg-input rounded-md mb-2 flex items-center justify-center overflow-hidden">
      <img
        v-if="item.image_url"
        :src="item.image_url"
        :alt="item.name"
        class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform"
        loading="lazy"
      />
      <span v-else class="text-text-muted text-xs">Нет фото</span>
    </div>

    <p
      class="text-xs text-text-primary line-clamp-2 mb-1"
      :class="compact ? 'min-h-[2rem]' : 'min-h-[2.5rem]'"
    >
      {{ item.name }}
    </p>

    <div class="flex items-center gap-1.5 mb-1">
      <span class="text-[10px] font-medium" :class="wearTextClass(item.wear)">
        {{ wearLabel(item.wear) }}
      </span>
      <span v-if="item.float_value" class="text-[10px] text-text-muted">
        {{ Number(item.float_value).toFixed(4) }}
      </span>
    </div>

    <p class="text-base font-bold text-primary">{{ formatPrice(item.price) }}</p>

    <div class="mt-2 flex items-center justify-between gap-2">
      <span v-if="item.seller" class="text-[10px] text-text-muted truncate">
        {{ item.seller.username }}
      </span>
      <button
        type="button"
        class="shrink-0 text-[11px] font-semibold rounded-md px-2 py-1 transition-colors border"
        :class="inCart
          ? 'bg-primary/10 border-primary text-primary'
          : 'bg-surface border-border text-text-secondary hover:text-text-primary hover:border-border-hover'"
        @click.stop="$emit('addToCart', item)"
      >
        {{ inCart ? 'В корзине' : 'В корзину' }}
      </button>
    </div>
  </div>
</template>
