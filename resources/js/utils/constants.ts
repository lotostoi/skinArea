/** После успешного обмена кода Steam — показать приветственную модалку (один раз за сессию входа). */
export const SESSION_POST_STEAM_WELCOME_MODAL = 'skinsarena_post_steam_welcome'

export function welcomeModalSkippedStorageKey(userId: number): string {
  return `skinsarena_welcome_modal_skipped_${userId}`
}
