<script setup lang="ts">
import { ref, computed, watch } from 'vue'
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
const steamReportedTotal = ref<number | null>(null)
const steamUserId = ref<string | null>(null)
const steamAppId = ref<number | null>(null)
const steamContextId = ref<number | null>(null)

const steamPrivacySettingsUrl = 'https://steamcommunity.com/my/edit/settings'

const showInventoryPrivacyHint = computed(() => {
  if (!error.value) {
    return false
  }
  const t = error.value.toLowerCase()
  return t.includes('скрыт') || t.includes('публичн')
})

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
    steamReportedTotal.value =
      typeof res.meta.steam_reported_total === 'number' ? res.meta.steam_reported_total : null
    steamRawAssets.value =
      typeof res.meta.steam_raw_assets === 'number' ? res.meta.steam_raw_assets : null
    mappedItems.value =
      typeof res.meta.mapped_items === 'number' ? res.meta.mapped_items : null
    steamUserId.value = typeof res.meta.steam_user_id === 'string' ? res.meta.steam_user_id : null
    steamAppId.value = typeof res.meta.steam_app_id === 'number' ? res.meta.steam_app_id : null
    steamContextId.value = typeof res.meta.steam_context_id === 'number' ? res.meta.steam_context_id : null
  } catch (e: unknown) {
    const msg = (e as { response?: { data?: { message?: string } } }).response?.data?.message
    error.value = msg ?? 'Не удалось загрузить инвентарь'
    items.value = []
    steamReportedTotal.value = null
    steamRawAssets.value = null
    mappedItems.value = null
    steamUserId.value = null
    steamAppId.value = null
    steamContextId.value = null
  } finally {
    loading.value = false
  }
}

const steamInventoryJsonUrl = computed(() => {
  if (!steamUserId.value || steamAppId.value === null || steamContextId.value === null) {
    return null
  }

  return `https://steamcommunity.com/inventory/${steamUserId.value}/${steamAppId.value}/${steamContextId.value}?l=english&count=1`
})

watch(
  () => props.enabled,
  (v) => {
    if (v) void load()
  },
  { immediate: true },
)
</script>

<template>
  <div class="bg-surface border border-border rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-text-primary">Инвентарь Steam ({{ inventoryGameLabel }})</h3>
      <AppButton variant="secondary" size="sm" :loading="loading" @click="load">Обновить</AppButton>
    </div>
    <div
      v-if="loading"
      class="rounded-lg border border-border bg-input/40 py-14 text-center text-sm text-text-muted"
    >
      Загрузка инвентаря…
    </div>

    <template v-else-if="error">
      <p class="text-sm text-danger" :class="showInventoryPrivacyHint ? 'mb-2' : 'mb-4'">{{ error }}</p>
      <p v-if="showInventoryPrivacyHint" class="text-sm text-text-secondary mb-4">
        <a
          :href="steamPrivacySettingsUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary hover:text-primary-hover underline font-medium"
        >
          Открыть настройки конфиденциальности Steam
        </a>
        — в разделе «Конфиденциальность» для «Инвентарь» выберите «Открытый» (Public).
      </p>
    </template>
    <p v-else-if="items.length === 0" class="text-text-secondary text-sm space-y-2">
      <span v-if="steamRawAssets !== null && steamRawAssets === 0">
        Steam вернул пустой список предметов (0 строк). Это не всегда ошибка, но чаще помогает проверка:
        <br>
        1) правильный
        <code class="text-text-primary">SKINSARENA_STEAM_INVENTORY_APP_ID</code> (для Dota 2 —
        <code class="text-text-primary">570</code>),
        <br>
        2) Steam ID в профиле совпадает с аккаунтом Steam,
        <br>
        3) инвентарь Steam открыт (Public),
        <br>
        4) вход выполнен через
        <code class="text-text-primary">http://localhost:8080</code>, а не только через
        <code class="text-text-primary">5173</code> без прокси.
      </span>
      <span v-if="steamUserId" class="block text-text-muted">
        Диагностика: Steam ID
        <code class="text-text-primary">{{ steamUserId }}</code>,
        app/context
        <code class="text-text-primary">{{ steamAppId ?? '—' }}/{{ steamContextId ?? '—' }}</code>,
        Steam total
        <code class="text-text-primary">{{ steamReportedTotal ?? '—' }}</code>,
        assets в ответе
        <code class="text-text-primary">{{ steamRawAssets ?? '—' }}</code>.
      </span>
      <span v-if="steamInventoryJsonUrl" class="block">
        Быстрая проверка: откройте
        <a
          :href="steamInventoryJsonUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary hover:text-primary-hover underline"
        >
          прямой JSON Steam для текущего профиля
        </a>
        и сравните количество
        <code class="text-text-primary">assets</code>.
      </span>
      <span
        v-if="steamRawAssets !== null && steamRawAssets > 0 && mappedItems === 0"
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
      <span v-else-if="onlyTradableFilter && steamRawAssets === null">
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
