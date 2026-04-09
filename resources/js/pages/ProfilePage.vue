<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useBalanceStore } from '@/stores/balance'
import AppButton from '@/components/ui/AppButton.vue'
import SteamInventoryPanel from '@/components/profile/SteamInventoryPanel.vue'
import ActiveListingsPanel from '@/components/profile/ActiveListingsPanel.vue'
import ListForSaleModal from '@/components/profile/ListForSaleModal.vue'
import { createMarketListing } from '@/utils/market'
import type { SteamInventoryItem } from '@/utils/market'

const auth = useAuthStore()
const balance = useBalanceStore()

const listingsRef = ref<InstanceType<typeof ActiveListingsPanel> | null>(null)
const inventoryKey = ref(0)

const modalOpen = ref(false)
const modalItem = ref<SteamInventoryItem | null>(null)
const listSubmitting = ref(false)

function onListItem(item: SteamInventoryItem) {
  modalItem.value = item
  modalOpen.value = true
}

async function onModalSubmit(price: number) {
  if (!modalItem.value) return
  listSubmitting.value = true
  try {
    await createMarketListing({
      asset_id: modalItem.value.asset_id,
      price,
    })
    modalOpen.value = false
    modalItem.value = null
    await listingsRef.value?.reload()
    inventoryKey.value += 1
  } catch (e: unknown) {
    const ax = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const msg = ax.response?.data?.message
    window.alert(msg ?? 'Не удалось выставить лот')
  } finally {
    listSubmitting.value = false
  }
}

function onListingsChanged() {
  inventoryKey.value += 1
}
</script>

<template>
  <div v-if="auth.user" class="space-y-6">
    <h1 class="text-2xl font-bold">Профиль</h1>

    <div class="bg-surface border border-border rounded-xl p-6">
      <div class="flex items-center gap-6">
        <img
          v-if="auth.user.avatar_url"
          :src="auth.user.avatar_url"
          :alt="auth.user.username"
          class="w-20 h-20 rounded-full border-2 border-border"
        />
        <div
          v-else
          class="w-20 h-20 rounded-full bg-secondary flex items-center justify-center text-2xl font-bold text-white"
        >
          {{ auth.user.username.charAt(0).toUpperCase() }}
        </div>

        <div>
          <h2 class="text-xl font-semibold">{{ auth.user.username }}</h2>
          <p class="text-text-muted text-sm mt-1">Steam ID: {{ auth.user.steam_id }}</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-surface border border-border rounded-xl p-6">
        <h3 class="text-sm font-medium text-text-secondary mb-2">Основной баланс</h3>
        <p class="text-3xl font-bold text-primary">{{ balance.mainBalance }} ₽</p>
      </div>

      <div class="bg-surface border border-border rounded-xl p-6">
        <h3 class="text-sm font-medium text-text-secondary mb-2">На удержании</h3>
        <p class="text-3xl font-bold text-warning">{{ balance.holdBalance }} ₽</p>
      </div>
    </div>

    <ActiveListingsPanel
      ref="listingsRef"
      :enabled="!!auth.user"
      @changed="onListingsChanged"
    />

    <SteamInventoryPanel
      :key="inventoryKey"
      :enabled="!!auth.user"
      @list-item="onListItem"
    />

    <div class="bg-surface border border-border rounded-xl p-6">
      <h3 class="text-lg font-semibold mb-4">Trade URL</h3>
      <p class="text-text-secondary text-sm mb-3">
        Ссылка для обмена нужна для получения и отправки предметов через Steam.
      </p>
      <div class="flex gap-3">
        <input
          type="text"
          :value="auth.user.trade_url ?? ''"
          placeholder="https://steamcommunity.com/tradeoffer/new/?partner=..."
          readonly
          class="flex-1 bg-input border border-border rounded-md px-4 py-2.5 text-sm text-text-primary placeholder-text-muted focus:border-border-focus focus:outline-none transition-colors"
        />
        <AppButton>Сохранить</AppButton>
      </div>
    </div>

    <ListForSaleModal
      :open="modalOpen"
      :item="modalItem"
      :submitting="listSubmitting"
      @close="modalOpen = false"
      @submit="onModalSubmit"
    />
  </div>
</template>
