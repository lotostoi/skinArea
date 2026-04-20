<script setup lang="ts">
import { computed } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { formatPrice, wearTextClass } from '@/utils/format'

withDefaults(defineProps<{ open: boolean; loading?: boolean }>(), { loading: false })
const emit = defineEmits<{ close: []; checkout: [] }>()

const cart = useCartStore()
const auth = useAuthStore()

const totalFormatted = computed(() => formatPrice(cart.total))
</script>

<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="open" class="fixed inset-0 z-[100]">
        <div class="absolute inset-0 bg-black/70" @click="emit('close')" />
        <aside
          class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-surface border-l border-border shadow-2xl"
        >
          <header class="flex items-center justify-between px-5 py-4 border-b border-border">
            <h2 class="text-lg font-semibold text-text-primary">Корзина</h2>
            <button
              type="button"
              class="text-text-muted hover:text-text-primary transition-colors"
              @click="emit('close')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </header>

          <div v-if="cart.count === 0" class="flex-1 flex flex-col items-center justify-center px-6 text-center">
            <p class="text-text-secondary mb-2">Корзина пуста.</p>
            <p class="text-text-muted text-sm">Добавляйте лоты с витрины, чтобы оформить покупку.</p>
          </div>

          <div v-else class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
            <div
              v-for="it in cart.items"
              :key="it.id"
              class="flex items-center gap-3 rounded-md border border-border bg-body/40 p-2"
            >
              <div class="h-14 w-14 shrink-0 overflow-hidden rounded bg-input flex items-center justify-center">
                <img
                  v-if="it.image_url"
                  :src="it.image_url"
                  :alt="it.name"
                  class="max-h-full max-w-full object-contain"
                />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-text-primary truncate">{{ it.name }}</p>
                <p class="text-xs" :class="wearTextClass(it.wear)">{{ it.wear }}</p>
                <p class="text-sm font-bold text-primary">{{ formatPrice(it.price) }}</p>
              </div>
              <button
                type="button"
                class="text-text-muted hover:text-danger transition-colors"
                title="Убрать из корзины"
                @click="cart.remove(it.id)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>

          <footer class="border-t border-border px-5 py-4 space-y-3">
            <div class="flex items-center justify-between text-sm">
              <span class="text-text-secondary">К оплате</span>
              <span class="text-lg font-bold text-primary">{{ totalFormatted }}</span>
            </div>
            <div class="flex gap-2">
              <AppButton
                variant="secondary"
                size="sm"
                class="flex-1"
                :disabled="cart.count === 0"
                @click="cart.clear"
              >
                Очистить
              </AppButton>
              <AppButton
                variant="primary"
                size="sm"
                class="flex-1"
                :disabled="cart.count === 0 || loading"
                @click="emit('checkout')"
              >
                {{
                  loading
                    ? 'Оформляем…'
                    : auth.isAuthenticated
                      ? 'Оформить покупку'
                      : 'Войти и купить'
                }}
              </AppButton>
            </div>
            <p class="text-[11px] text-text-muted text-center">
              После оплаты вам придут трейды от продавцов в Steam.
            </p>
          </footer>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.2s ease;
}
.drawer-enter-active aside,
.drawer-leave-active aside {
  transition: transform 0.2s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}
.drawer-enter-from aside,
.drawer-leave-to aside {
  transform: translateX(100%);
}
</style>
