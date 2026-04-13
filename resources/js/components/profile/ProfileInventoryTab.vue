<script setup lang="ts">
import { ref } from 'vue'
import ActiveListingsPanel from '@/components/profile/ActiveListingsPanel.vue'
import SteamInventoryPanel from '@/components/profile/SteamInventoryPanel.vue'
import ProfileSoldPanel from '@/components/profile/ProfileSoldPanel.vue'
import type { SteamInventoryItem } from '@/utils/market'

type InventorySub = 'all' | 'active' | 'sold'

defineProps<{
  enabled: boolean
  hasTradeUrl: boolean
  steamPrivacyUrl: string
  steamInventoryKey: number
}>()

const emit = defineEmits<{
  listItem: [SteamInventoryItem]
  listingsChanged: []
}>()

const sub = ref<InventorySub>('all')
const listingsRef = ref<InstanceType<typeof ActiveListingsPanel> | null>(null)
const soldRef = ref<InstanceType<typeof ProfileSoldPanel> | null>(null)

const subTabs: { id: InventorySub; label: string }[] = [
  { id: 'all', label: 'Весь инвентарь' },
  { id: 'active', label: 'Активные' },
  { id: 'sold', label: 'Проданные' },
]

function reloadAfterListing() {
  void listingsRef.value?.reload()
  void soldRef.value?.reload()
}

defineExpose({ reloadAfterListing })
</script>

<template>
  <div v-show="enabled" class="space-y-6">
    <h2 class="text-xl font-bold uppercase tracking-wide text-text-primary md:text-2xl">Мой инвентарь</h2>

    <div class="flex flex-wrap gap-1 border-b border-border">
      <button
        v-for="t in subTabs"
        :key="t.id"
        type="button"
        class="relative px-4 py-2.5 text-sm font-medium transition-colors"
        :class="
          sub === t.id
            ? 'text-primary after:absolute after:bottom-0 after:left-2 after:right-2 after:h-0.5 after:rounded-full after:bg-primary'
            : 'text-text-secondary hover:text-text-primary'
        "
        @click="sub = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <div v-show="sub === 'active'">
      <ActiveListingsPanel
        ref="listingsRef"
        :enabled="enabled && sub === 'active'"
        @changed="emit('listingsChanged')"
      />
    </div>

    <div v-show="sub === 'sold'">
      <ProfileSoldPanel ref="soldRef" :enabled="enabled && sub === 'sold'" />
    </div>

    <div v-show="sub === 'all'">
      <SteamInventoryPanel
        v-if="hasTradeUrl"
        :key="steamInventoryKey"
        :enabled="enabled && sub === 'all'"
        @list-item="emit('listItem', $event)"
      />
      <div v-else class="rounded-xl border border-border bg-surface p-8 text-center">
        <h3 class="mb-2 text-lg font-semibold text-text-primary">Инвентарь Steam</h3>
        <p class="mb-4 text-sm text-text-secondary">
          Укажите trade URL в блоке выше, чтобы загрузить инвентарь.
        </p>
        <a
          :href="steamPrivacyUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-sm font-medium text-primary underline hover:text-primary-hover"
        >
          Открыть страницу trade URL в Steam
        </a>
      </div>
    </div>
  </div>
</template>
