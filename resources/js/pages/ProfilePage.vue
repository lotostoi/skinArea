<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ProfileCabinetTopCard from '@/components/profile/ProfileCabinetTopCard.vue'
import ProfileInventoryTab from '@/components/profile/ProfileInventoryTab.vue'
import ProfileTransactionsTab from '@/components/profile/ProfileTransactionsTab.vue'
import ProfileDealsTab from '@/components/profile/ProfileDealsTab.vue'
import ProfileSettingsTab from '@/components/profile/ProfileSettingsTab.vue'
import CaseInventoryTab from '@/components/profile/CaseInventoryTab.vue'
import ListForSaleModal from '@/components/profile/ListForSaleModal.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { createMarketListing } from '@/utils/market'
import type { SteamInventoryItem } from '@/utils/market'
import { showAppAlert } from '@/composables/appDialog'

type CabinetTab = 'transactions' | 'inventory' | 'deals' | 'settings' | 'case-inventory'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const activeTab = ref<CabinetTab>('inventory')

// Start as true (show spinner) so the first render is NEVER blank.
// Set to false only when we have confirmed auth state.
const isReady = ref(auth.user !== null)

onMounted(async () => {
  const localToken = localStorage.getItem('auth_token')

  if (!localToken) {
    await router.replace({ name: 'home' })
    return
  }

  // User not in Pinia yet — could be timing issue between guard and render.
  // Sync token and load user.
  if (!auth.user) {
    if (!auth.token) {
      auth.$patch({ token: localToken })
    }
    await auth.loadUser()
  }

  if (!auth.user) {
    await router.replace({ name: 'home' })
    return
  }

  isReady.value = true

  if (route.query.email_verified === '1') {
    await auth.loadUser()
    await router.replace({ path: route.path, query: {} })
  }
})

const tabs: { id: CabinetTab; label: string }[] = [
  { id: 'inventory', label: 'Мой инвентарь' },
  { id: 'case-inventory', label: 'Кейсы' },
  { id: 'transactions', label: 'Транзакции' },
  { id: 'deals', label: 'Мои сделки' },
  { id: 'settings', label: 'Настройки' },
]

const inventoryTabRef = ref<InstanceType<typeof ProfileInventoryTab> | null>(null)
const steamInventoryKey = ref(0)

const modalOpen = ref(false)
const modalItem = ref<SteamInventoryItem | null>(null)
const listSubmitting = ref(false)

const hasTradeUrl = computed(
  () => !!auth.user?.trade_url && auth.user.trade_url.trim().length > 0,
)

const steamPrivacyUrl = computed(() => {
  if (auth.user?.steam_trade_privacy_url) {
    return auth.user.steam_trade_privacy_url
  }
  if (auth.user?.steam_id) {
    return `https://steamcommunity.com/profiles/${encodeURIComponent(auth.user.steam_id)}/tradeoffers/privacy`
  }
  return 'https://steamcommunity.com/my/tradeoffers/privacy'
})

function onListItem(item: SteamInventoryItem) {
  modalItem.value = item
  modalOpen.value = true
}

async function onModalSubmit(price: number) {
  if (!modalItem.value) {
    return
  }
  listSubmitting.value = true
  try {
    await createMarketListing({
      asset_id: modalItem.value.asset_id,
      price,
    })
    modalOpen.value = false
    modalItem.value = null
    inventoryTabRef.value?.reloadAfterListing()
    steamInventoryKey.value += 1
  } catch (e: unknown) {
    const ax = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const msg = ax.response?.data?.message
    const firstErr = ax.response?.data?.errors
    const flat = firstErr ? Object.values(firstErr).flat()[0] : undefined
    showAppAlert((typeof flat === 'string' ? flat : null) ?? msg ?? 'Не удалось выставить лот', {
      variant: 'error',
      title: 'Выставление на маркет',
    })
  } finally {
    listSubmitting.value = false
  }
}

function onListingsChanged() {
  steamInventoryKey.value += 1
}
</script>

<template>
  <div v-if="!isReady" class="flex justify-center py-24">
    <AppSpinner size="lg" />
  </div>

  <div v-else-if="auth.user" class="relative pb-10">
    <nav class="mb-3 text-xs text-text-muted">
      <router-link to="/" class="transition-colors hover:text-text-secondary">Главная</router-link>
      <span class="mx-1.5 text-border">/</span>
      <span class="text-text-secondary">Личный кабинет</span>
    </nav>

    <div class="mb-8">
      <ProfileCabinetTopCard />
    </div>

    <div class="mb-6 flex flex-wrap gap-1 border-b border-border">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="relative px-4 py-3 text-sm font-medium transition-colors"
        :class="
          activeTab === tab.id
            ? 'text-primary after:absolute after:bottom-0 after:left-2 after:right-2 after:h-0.5 after:rounded-full after:bg-primary'
            : 'text-text-secondary hover:text-text-primary'
        "
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <div class="min-h-[320px]">
      <ProfileInventoryTab
        v-if="activeTab === 'inventory'"
        ref="inventoryTabRef"
        :has-trade-url="hasTradeUrl"
        :steam-privacy-url="steamPrivacyUrl"
        :steam-inventory-key="steamInventoryKey"
        @list-item="onListItem"
        @listings-changed="onListingsChanged"
      />

      <ProfileTransactionsTab v-if="activeTab === 'transactions'" />

      <CaseInventoryTab v-if="activeTab === 'case-inventory'" />

      <ProfileDealsTab v-if="activeTab === 'deals'" />

      <ProfileSettingsTab v-if="activeTab === 'settings'" />
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
