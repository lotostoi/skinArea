<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import { fetchSteamInventory, type SteamInventoryItem } from '@/utils/market'

const props = defineProps<{
  enabled: boolean
}>()

const emit = defineEmits<{
  listItem: [item: SteamInventoryItem]
}>()

const items = ref<SteamInventoryItem[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const inventoryGameLabel = ref('Counter-Strike 2')
const onlyTradableFilter = ref(true)
const steamRawAssets = ref<number | null>(null)
const mappedItems = ref<number | null>(null)

async function load() {
  if (!props.enabled) return
  loading.value = true
  error.value = null
  try {
    const res = await fetchSteamInventory()
    items.value = res.data
    if (res.meta.inventory_game) {
      inventoryGameLabel.value = res.meta.inventory_game
    }
    if (typeof res.meta.only_tradable === 'boolean') {
      onlyTradableFilter.value = res.meta.only_tradable
    }
    steamRawAssets.value =
      typeof res.meta.steam_raw_assets === 'number' ? res.meta.steam_raw_assets : null
    mappedItems.value =
      typeof res.meta.mapped_items === 'number' ? res.meta.mapped_items : null
  } catch (e: unknown) {
    const msg = (e as { response?: { data?: { message?: string } } }).response?.data?.message
    error.value = msg ?? 'Не удалось загрузить инвентарь'
    items.value = []
    steamRawAssets.value = null
    mappedItems.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.enabled) void load()
})

watch(
  () => props.enabled,
  (v) => {
    if (v) void load()
  },
)
</script>

<template>
  <div class="bg-surface border border-border rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-text-primary">Инвентарь Steam ({{ inventoryGameLabel }})</h3>
      <AppButton variant="secondary" size="sm" :loading="loading" @click="load">Обновить</AppButton>
    </div>
    <p v-if="error" class="text-sm text-danger mb-4">{{ error }}</p>
    <p v-else-if="!loading && items.length === 0" class="text-text-secondary text-sm space-y-2">
      <span v-if="steamRawAssets !== null && steamRawAssets === 0">
        По ответу Steam предметов в этом инвентаре нет (0 строк). Проверьте
        <code class="text-text-primary">SKINSARENA_STEAM_INVENTORY_APP_ID</code> (Dota: 570), Steam ID в профиле
        совпадает с аккаунтом в Steam, инвентарь публичный. Заходите на сайт через
        <code class="text-text-primary">http://localhost:8080</code>, не только на порт 5173 без прокси.
      </span>
      <span
        v-else-if="steamRawAssets !== null && steamRawAssets > 0 && mappedItems === 0"
        class="block text-warning"
      >
        Steam отдал {{ steamRawAssets }} строк, в список не попало ни одной (часто: включён только tradable, а
        вещи неторгуемые — тогда
        <code class="text-text-primary">SKINSARENA_STEAM_INVENTORY_ONLY_TRADABLE=false</code> и
        <code class="text-text-primary">config:clear</code>). Либо запросы шли не на ваш бэкенд: откройте
        <code class="text-text-primary">localhost:8080</code> или задайте
        <code class="text-text-primary">BACKEND_URL=http://127.0.0.1:8080</code> для
        <code class="text-text-primary">npm run dev</code>.
      </span>
      <span v-else-if="onlyTradableFilter">
        В списке только предметы, которые можно обменять (tradable). Если у вас в этой игре только неторгуемые
        вещи, список будет пустым. Для теста:
        <code class="text-text-primary">SKINSARENA_STEAM_INVENTORY_ONLY_TRADABLE=false</code> и
        <code class="text-text-primary">php artisan config:clear</code> (в Docker:
        <code class="text-text-primary">docker compose exec app php artisan config:clear</code>).
      </span>
      <span v-else>Не удалось показать предметы. Обновите страницу или проверьте ответ API в инструментах браузера.</span>
    </p>
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
      <div
        v-for="it in items"
        :key="it.asset_id"
        class="border border-border rounded-lg p-3 bg-input hover:border-border-hover transition-colors"
      >
        <div class="aspect-square bg-input rounded-md mb-2 flex items-center justify-center overflow-hidden">
          <img
            v-if="it.image_url"
            :src="it.image_url"
            :alt="it.name"
            class="max-w-full max-h-full object-contain"
          />
          <span v-else class="text-text-muted text-xs">Нет фото</span>
        </div>
        <p class="text-xs text-text-primary line-clamp-2 min-h-[2.5rem] mb-1">{{ it.name }}</p>
        <p class="text-[10px] text-text-muted mb-2">{{ it.wear }}</p>
        <AppButton
          size="sm"
          class="w-full"
          :disabled="!it.tradable"
          :title="it.tradable ? '' : 'Предмет не торгуемый — на маркет не выставить'"
          @click="emit('listItem', it)"
        >
          {{ it.tradable ? 'На продажу' : 'Не торгуемый' }}
        </AppButton>
      </div>
    </div>
  </div>
</template>
