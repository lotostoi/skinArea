import { computed, ref, watch } from 'vue'
import type { User } from '@/types/models'
import { useAuthStore } from '@/stores/auth'
import { showAppAlert } from '@/composables/appDialog'

const tradeUrlDraft = ref('')
const emailDraft = ref('')

let authUserWatchStarted = false

function syncDraftsFromUser(u: User | null): void {
  tradeUrlDraft.value = u?.trade_url?.trim() ? u.trade_url : ''
  emailDraft.value = u?.email?.trim() ? u.email : ''
}

export function useProfileTradeAndEmail() {
  const auth = useAuthStore()
  const tradeUrlSaving = ref(false)
  const emailSaving = ref(false)

  if (!authUserWatchStarted) {
    authUserWatchStarted = true
    watch(
      () => auth.user,
      (u: User | null) => {
        syncDraftsFromUser(u)
      },
      { immediate: true },
    )
  } else {
    syncDraftsFromUser(auth.user)
  }

  const steamPrivacyUrl = computed(() => {
    if (auth.user?.steam_trade_privacy_url) {
      return auth.user.steam_trade_privacy_url
    }
    if (auth.user?.steam_id) {
      return `https://steamcommunity.com/profiles/${encodeURIComponent(auth.user.steam_id)}/tradeoffers/privacy`
    }
    return 'https://steamcommunity.com/my/tradeoffers/privacy'
  })

  const needsEmailBanner = computed(() => {
    if (!auth.user?.email?.trim()) {
      return true
    }
    return !auth.user.email_verified_at
  })

  async function saveTradeUrl(): Promise<void> {
    tradeUrlSaving.value = true
    try {
      await auth.updateTradeUrl(tradeUrlDraft.value.trim())
    } catch (e: unknown) {
      const ax = e as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      const first = ax.response?.data?.errors?.trade_url?.[0]
      showAppAlert(
        (typeof first === 'string' ? first : null) ?? ax.response?.data?.message ?? 'Не удалось сохранить',
        { variant: 'error' },
      )
    } finally {
      tradeUrlSaving.value = false
    }
  }

  async function saveEmail(): Promise<void> {
    emailSaving.value = true
    try {
      await auth.updateEmail(emailDraft.value.trim() === '' ? null : emailDraft.value.trim())
      const u = auth.user
      if (u?.email?.trim()) {
        if (!u.email_verified_at) {
          showAppAlert(
            'Email сохранён. На этот адрес отправлено письмо со ссылкой для подтверждения — откройте письмо и нажмите кнопку в нём. Локально с Docker письма попадают в Mailpit (порт 8025).',
            { variant: 'success', title: 'Почта обновлена' },
          )
        } else {
          showAppAlert('Email сохранён.', { variant: 'success' })
        }
      } else {
        showAppAlert('Email убран из профиля.', { variant: 'info' })
      }
    } catch (e: unknown) {
      const ax = e as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      const first = ax.response?.data?.errors?.email?.[0]
      showAppAlert(
        (typeof first === 'string' ? first : null) ?? ax.response?.data?.message ?? 'Не удалось сохранить email',
        { variant: 'error' },
      )
    } finally {
      emailSaving.value = false
    }
  }

  return {
    auth,
    tradeUrlDraft,
    tradeUrlSaving,
    emailDraft,
    emailSaving,
    steamPrivacyUrl,
    needsEmailBanner,
    saveTradeUrl,
    saveEmail,
  }
}
