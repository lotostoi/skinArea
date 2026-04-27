<script setup lang="ts">
import { ref, watch, withDefaults } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import type { SteamInventoryItem } from '@/utils/market'

const props = withDefaults(
  defineProps<{
    open: boolean
    item: SteamInventoryItem | null
    submitting?: boolean
  }>(),
  { submitting: false },
)

const emit = defineEmits<{
  close: []
  submit: [price: number]
}>()

const price = ref('')
const error = ref<string | null>(null)
const bypassExtensionConfirmed = ref(false)

watch(
  () => props.open,
  (v: boolean) => {
    if (v) {
      price.value = ''
      error.value = null
      bypassExtensionConfirmed.value = false
    }
  },
)

function onSubmit() {
  error.value = null
  const n = Number(price.value.replace(',', '.'))
  if (!Number.isFinite(n) || n < 0.01) {
    error.value = 'Укажите цену не меньше 0.01'
    return
  }
  if (!bypassExtensionConfirmed.value) {
    error.value = 'Подтвердите, что согласны выставить предмет без расширения'
    return
  }
  emit('submit', n)
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && item"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
      role="dialog"
      aria-modal="true"
      @click.self="emit('close')"
    >
      <div
        class="bg-surface border border-border rounded-xl max-w-md w-full p-6 shadow-xl"
        @click.stop
      >
        <h3 class="text-lg font-semibold text-text-primary mb-2">Выставить на продажу</h3>
        <p class="text-sm text-text-secondary mb-4 line-clamp-2">{{ item.name }}</p>
        <div class="mb-4 rounded-lg border border-warning/40 bg-warning/10 p-3">
          <p class="text-sm text-text-primary">
            Сейчас предмет выставляется на продажу, минуя функционал расширения. Вы согласны продолжить?
          </p>
          <label class="mt-2 flex items-start gap-2 text-sm text-text-secondary">
            <input
              v-model="bypassExtensionConfirmed"
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-border bg-input text-primary focus:ring-primary"
            />
            <span>Да, согласен выставить предмет без расширения на текущем этапе.</span>
          </label>
          <p class="mt-2 text-xs text-text-muted">Функционал расширения будет добавлен позже.</p>
        </div>
        <label class="block text-sm text-text-secondary mb-1">Цена, ₽</label>
        <input
          v-model="price"
          type="text"
          inputmode="decimal"
          class="w-full bg-input border border-border rounded-md px-4 py-2.5 text-sm text-text-primary focus:border-border-focus focus:outline-none mb-2"
          placeholder="0.00"
          @keyup.enter="onSubmit"
        />
        <p v-if="error" class="text-sm text-danger mb-3">{{ error }}</p>
        <div class="flex gap-3 justify-end">
          <AppButton variant="secondary" :disabled="props.submitting" @click="emit('close')">Отмена</AppButton>
          <AppButton :loading="props.submitting" :disabled="!bypassExtensionConfirmed" @click="onSubmit">
            Выставить
          </AppButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>
