import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { showAppAlert } from '@/composables/appDialog'

/** Safely convert any value to a trimmed string. Returns '' for non-strings. */
function safeStr(v: unknown): string {
  return typeof v === 'string' ? v.trim() : ''
}

export function useProfileTradeAndEmail() {
  const auth = useAuthStore()

  // Each component instance gets its own reactive draft refs.
  // Using safeStr() guards against runtime values that don't match the TS type
  // (e.g. null/undefined from API responses that don't include optional fields).
  const tradeUrlDraft = ref(safeStr(auth.user?.trade_url))
  const emailDraft = ref(safeStr(auth.user?.email))

  const tradeUrlSaving = ref(false)
  const emailSaving = ref(false)

  // Sync drafts whenever auth.user changes (e.g. after loadUser() completes).
  watch(
    () => auth.user,
    (u) => {
      tradeUrlDraft.value = safeStr(u?.trade_url)
      emailDraft.value = safeStr(u?.email)
    },
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

  const needsEmailBanner = computed(() => {
    if (!safeStr(auth.user?.email)) {
      return true
    }
    return !auth.user?.email_verified_at
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
      if (safeStr(u?.email)) {
        if (!u.email_verified_at) {
          showAppAlert(
            'Email сохранён. На этот адрес отправлено письмо со ссылкой для подтверждения — откройте письмо и нажмите кнопку в нём. Локально с Docker письма в Mailpit: тот же хост, путь /mailpit/.',
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
