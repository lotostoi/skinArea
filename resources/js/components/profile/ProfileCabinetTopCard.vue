<script setup lang="ts">
import { ref } from 'vue'
import { useBalanceStore } from '@/stores/balance'
import { useAuthStore } from '@/stores/auth'
import { useDepositModalStore } from '@/stores/depositModal'
import AppButton from '@/components/ui/AppButton.vue'
import { useProfileTradeAndEmail } from '@/composables/useProfileTradeAndEmail'
import { showAppAlert } from '@/composables/appDialog'

const balance = useBalanceStore()
const authStore = useAuthStore()
const depositModal = useDepositModalStore()
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
  <div v-if="auth.user" class="relative rounded-xl border border-border bg-surface p-6 md:p-8">
    <div class="flex flex-col gap-6 md:flex-row md:items-start">
      <div class="flex shrink-0 justify-center md:block">
        <img
          v-if="auth.user.avatar_url"
          :src="auth.user.avatar_url"
          :alt="auth.user.username"
          class="h-28 w-28 rounded-full border-2 border-border object-cover md:h-32 md:w-32"
        />
        <div
          v-else
          class="flex h-28 w-28 items-center justify-center rounded-full border-2 border-border bg-[#16161e] text-4xl font-bold text-text-muted md:h-32 md:w-32"
        >
          ?
        </div>
      </div>

      <div class="min-w-0 flex-1 space-y-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-text-primary md:text-3xl">
              {{ auth.user.username }}
            </h1>
            <p class="mt-1 text-xs text-text-muted">Steam ID: {{ auth.user.steam_id }}</p>
            <p class="mt-2 text-sm text-text-secondary">
              Баланс:
              <button
                type="button"
                class="font-semibold text-primary underline decoration-dotted underline-offset-4 transition-colors hover:text-primary-hover"
                title="Пополнить баланс"
                @click="depositModal.open()"
              >
                {{ balance.mainBalance }} ₽
              </button>
              <span class="mx-2 text-border">|</span>
              На удержании:
              <span class="font-semibold text-warning">{{ balance.holdBalance }} ₽</span>
            </p>
          </div>
          <div class="flex shrink-0 flex-wrap gap-2">
            <select
              class="cursor-not-allowed rounded-md border border-border bg-input px-3 py-2 text-sm text-text-secondary opacity-80"
              disabled
              title="Скоро"
            >
              <option>RUB</option>
            </select>
            <select
              class="cursor-not-allowed rounded-md border border-border bg-input px-3 py-2 text-sm text-text-secondary opacity-80"
              disabled
              title="Скоро"
            >
              <option>Русский</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <div class="flex items-center gap-2 text-sm font-medium text-text-secondary">
            <span>Trade URL</span>
            <a
              :href="steamPrivacyUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary hover:text-primary-hover"
              title="Где взять ссылку"
            >
              <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
              </svg>
            </a>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row">
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
        </div>

        <div class="space-y-2">
          <div class="text-sm font-medium text-text-secondary">Email</div>
          <div class="flex flex-wrap items-center gap-2 text-sm">
            <template v-if="!auth.user.email">
              <span class="inline-flex items-center gap-1.5 text-danger">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                Email не задан
              </span>
            </template>
            <template v-else-if="!auth.user.email_verified_at">
              <span class="inline-flex items-center gap-1.5 text-danger">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                Email не подтверждён
              </span>
            </template>
            <template v-else>
              <span class="text-text-primary">{{ auth.user.email }}</span>
              <span class="text-xs text-success">подтверждён</span>
            </template>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
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
              {{ auth.user.email ? 'Сохранить email' : 'Добавить email' }}
            </AppButton>
          </div>
        </div>

        <div
          v-if="needsEmailBanner"
          class="rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm leading-relaxed text-danger"
        >
          <p class="mb-3">
            Укажите email и подтвердите его по ссылке из письма.
          </p>
          <AppButton
            v-if="auth.user?.email"
            variant="secondary"
            size="sm"
            class="uppercase tracking-wide"
            :loading="resendVerificationLoading"
            @click="resendVerificationEmail"
          >
            Выслать письмо ещё раз
          </AppButton>
        </div>
      </div>
    </div>
  </div>
</template>
