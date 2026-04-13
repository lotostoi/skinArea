<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/ui/AppButton.vue'
import {
  SESSION_POST_STEAM_WELCOME_MODAL,
  welcomeModalSkippedStorageKey,
} from '@/utils/constants'

const emit = defineEmits<{
  close: []
}>()

const auth = useAuthStore()

const emailDraft = ref('')
const tradeUrlDraft = ref('')
const submitting = ref(false)
const formError = ref<string | null>(null)

const privacyUrl = computed(() => {
  if (auth.user?.steam_trade_privacy_url) {
    return auth.user.steam_trade_privacy_url
  }
  if (auth.user?.steam_id) {
    return `https://steamcommunity.com/profiles/${encodeURIComponent(auth.user.steam_id)}/tradeoffers/privacy`
  }
  return 'https://steamcommunity.com/my/tradeoffers/privacy'
})

watch(
  () => auth.user,
  (u) => {
    emailDraft.value = u?.email?.trim() ? u.email : ''
    tradeUrlDraft.value = u?.trade_url?.trim() ? u.trade_url : ''
  },
  { immediate: true },
)

function dismissSessionFlag(): void {
  sessionStorage.removeItem(SESSION_POST_STEAM_WELCOME_MODAL)
}

function onSkip(): void {
  if (auth.user) {
    localStorage.setItem(welcomeModalSkippedStorageKey(auth.user.id), '1')
  }
  dismissSessionFlag()
  emit('close')
}

async function onContinue(): void {
  formError.value = null
  const emailVal = emailDraft.value.trim()
  const tradeVal = tradeUrlDraft.value.trim()

  if (emailVal === '' && tradeVal === '') {
    formError.value = 'Укажите email и/или trade-ссылку, либо нажмите «Закрыть» в углу окна.'
    return
  }

  submitting.value = true
  try {
    await auth.patchWelcomeProfile({
      email: emailVal === '' ? undefined : emailVal,
      tradeUrl: tradeVal === '' ? undefined : tradeVal,
    })
    dismissSessionFlag()
    emit('close')
  } catch (e: unknown) {
    const ax = e as {
      response?: { data?: { message?: string; errors?: Record<string, string[]> } }
    }
    const errs = ax.response?.data?.errors
    const first =
      errs?.trade_url?.[0] ?? errs?.email?.[0] ?? ax.response?.data?.message ?? 'Проверьте введённые данные.'
    formError.value = first
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 animate-[fadeIn_0.2s_ease-out]"
      role="dialog"
      aria-modal="true"
      aria-labelledby="welcome-modal-title"
    >
      <div
        class="relative w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl animate-[slideUp_0.2s_ease-out]"
      >
        <button
          type="button"
          class="absolute top-4 right-4 p-1 rounded-md text-text-muted hover:text-text-primary hover:bg-surface-hover transition-colors"
          aria-label="Пропустить"
          @click="onSkip"
        >
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <div class="text-center mb-6 pt-1">
          <p class="text-xl font-bold text-text-primary" id="welcome-modal-title">
            Добро пожаловать в SkinsArena
          </p>
          <p class="text-text-secondary text-sm mt-2">
            Укажите контакты для маркета — можно заполнить не всё и закрыть окно.
          </p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-text-muted mb-1.5">Email</label>
            <input
              v-model="emailDraft"
              type="email"
              autocomplete="email"
              placeholder="Введите email"
              class="w-full px-3 py-2.5 rounded-md bg-input border border-border text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-text-muted mb-1.5">Trade URL</label>
            <input
              v-model="tradeUrlDraft"
              type="url"
              autocomplete="off"
              placeholder="https://steamcommunity.com/tradeoffer/new/?partner=…&token=…"
              class="w-full px-3 py-2.5 rounded-md bg-input border border-border text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
            />
            <p class="text-xs text-text-secondary mt-2">
              Не знаете trade URL?
              <a
                :href="privacyUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary hover:text-primary-hover font-medium underline"
              >Открыть страницу Steam</a>
            </p>
          </div>

          <p v-if="formError" class="text-sm text-danger">{{ formError }}</p>

          <AppButton class="w-full" size="lg" :loading="submitting" @click="onContinue">
            Продолжить
          </AppButton>

          <p class="text-[11px] text-text-muted text-center leading-relaxed">
            Нажимая «Продолжить», вы подтверждаете, что указали корректные данные.
            Полные условия использования будут опубликованы на сайте позже.
          </p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
