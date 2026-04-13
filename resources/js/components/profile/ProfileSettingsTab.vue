<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useAuthStore } from '@/stores/auth'
import { useProfileTradeAndEmail } from '@/composables/useProfileTradeAndEmail'
import { showAppAlert } from '@/composables/appDialog'

defineProps<{
  active: boolean
}>()

const authStore = useAuthStore()
const resendVerificationLoading = ref(false)

const {
  auth,
  tradeUrlDraft,
  tradeUrlSaving,
  emailDraft,
  emailSaving,
  steamPrivacyUrl,
  needsEmailBanner,
  saveTradeUrl,
  saveEmail,
} = useProfileTradeAndEmail()

async function resendVerificationEmail(): Promise<void> {
  resendVerificationLoading.value = true
  try {
    const msg = await authStore.resendEmailVerification()
    showAppAlert(msg, { variant: 'success', title: 'Письмо отправлено' })
  } catch (e: unknown) {
    const ax = e as { response?: { data?: { message?: string } } }
    showAppAlert(ax.response?.data?.message ?? 'Не удалось отправить письмо', { variant: 'error' })
  } finally {
    resendVerificationLoading.value = false
  }
}
</script>

<template>
  <div v-show="active" class="space-y-6">
    <h2 class="text-xl font-bold uppercase tracking-wide text-text-primary md:text-2xl">Настройки</h2>
    <p class="text-sm text-text-secondary">
      Токен расширения, уведомления, Telegram, электронная почта и трейд-ссылка. Почту и ссылку на обмен можно также
      изменить в шапке кабинета.
    </p>

    <section class="rounded-xl border border-border bg-surface p-6">
      <h3 class="mb-2 text-base font-semibold text-text-primary">Браузерное расширение (токен доступа)</h3>
      <p class="mb-4 text-sm text-text-secondary">
        Ключ для связи расширения с аккаунтом. Сгенерировать или отозвать токен пока нельзя.
      </p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          disabled
          class="cursor-not-allowed rounded-md bg-primary/40 px-4 py-2 text-sm font-medium text-[#0e0e12]"
        >
          Сгенерировать токен
        </button>
      </div>
    </section>

    <section class="rounded-xl border border-border bg-surface p-6">
      <h3 class="mb-2 text-base font-semibold text-text-primary">Уведомления</h3>
      <p class="mb-4 text-sm text-text-secondary">
        Включение и выключение по типам событий. Переключатели пока не сохраняются на сервере.
      </p>
      <ul class="space-y-3 text-sm text-text-secondary">
        <li class="flex items-center justify-between gap-4">
          <span>Сделки и статусы трейда</span>
          <input type="checkbox" disabled class="h-4 w-4 rounded border-border" checked />
        </li>
        <li class="flex items-center justify-between gap-4">
          <span>Пополнение и вывод баланса</span>
          <input type="checkbox" disabled class="h-4 w-4 rounded border-border" checked />
        </li>
        <li class="flex items-center justify-between gap-4">
          <span>Системные сообщения</span>
          <input type="checkbox" disabled class="h-4 w-4 rounded border-border" checked />
        </li>
      </ul>
    </section>

    <section class="rounded-xl border border-border bg-surface p-6">
      <h3 class="mb-2 text-base font-semibold text-text-primary">Telegram</h3>
      <p class="mb-3 text-sm text-text-secondary">Привязка бота для оповещений. Поле и кнопка пока неактивны.</p>
      <div class="flex flex-wrap gap-2">
        <input
          type="text"
          disabled
          placeholder="@username или ID"
          class="min-w-[200px] flex-1 rounded-md border border-border bg-input px-3 py-2 text-sm text-text-muted"
        />
        <button
          type="button"
          disabled
          class="cursor-not-allowed rounded-md border border-border px-4 py-2 text-sm text-text-muted"
        >
          Привязать
        </button>
      </div>
    </section>

    <section v-if="auth.user" class="rounded-xl border border-border bg-surface p-6">
      <h3 class="mb-2 text-base font-semibold text-text-primary">Электронная почта</h3>
      <div class="flex flex-wrap items-center gap-2 text-sm">
        <template v-if="!auth.user.email?.trim()">
          <span class="text-danger">Не указана</span>
        </template>
        <template v-else-if="!auth.user.email_verified_at">
          <span class="text-danger">Не подтверждена</span>
        </template>
        <template v-else>
          <span class="text-text-primary">{{ auth.user.email }}</span>
          <span class="text-xs text-success">подтверждена</span>
        </template>
      </div>
      <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
          v-model="emailDraft"
          type="email"
          autocomplete="email"
          placeholder="Введите email"
          class="min-h-[44px] w-full max-w-xl rounded-md border border-border bg-input px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:border-primary focus:outline-none"
        />
        <AppButton
          class="shrink-0 uppercase tracking-wide sm:min-w-[180px]"
          :loading="emailSaving"
          @click="saveEmail"
        >
          {{ auth.user.email?.trim() ? 'Сохранить' : 'Добавить' }}
        </AppButton>
      </div>
      <div
        v-if="needsEmailBanner"
        class="mt-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger"
      >
        <p class="mb-3">Укажите email и подтвердите его по ссылке из письма.</p>
        <AppButton
          v-if="auth.user?.email?.trim()"
          variant="secondary"
          size="sm"
          class="uppercase tracking-wide"
          :loading="resendVerificationLoading"
          @click="resendVerificationEmail"
        >
          Выслать письмо ещё раз
        </AppButton>
      </div>
    </section>

    <section v-if="auth.user" class="rounded-xl border border-border bg-surface p-6">
      <h3 class="mb-2 text-base font-semibold text-text-primary">Трейд-ссылка</h3>
      <p class="mb-3 text-sm text-text-secondary">
        Ссылка на обмен Steam, нужна для получения предметов и выставления лотов.
      </p>
      <div class="flex items-center gap-2 text-sm font-medium text-text-secondary">
        <span>Где взять</span>
        <a
          :href="steamPrivacyUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary hover:text-primary-hover"
        >
          Открыть настройки приватности Steam
        </a>
      </div>
      <div class="mt-4 flex flex-col gap-2 sm:flex-row">
        <input
          v-model="tradeUrlDraft"
          type="url"
          autocomplete="off"
          placeholder="https://steamcommunity.com/tradeoffer/new/?partner=…&token=…"
          class="min-h-[44px] flex-1 rounded-md border border-border bg-input px-3 py-2.5 text-sm text-text-primary placeholder-text-muted focus:border-primary focus:outline-none"
        />
        <AppButton
          class="shrink-0 uppercase tracking-wide sm:min-w-[140px]"
          :loading="tradeUrlSaving"
          @click="saveTradeUrl"
        >
          Сохранить
        </AppButton>
      </div>
    </section>
  </div>
</template>
