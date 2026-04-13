import { ref, type Ref } from 'vue'

export type AppDialogVariant = 'info' | 'success' | 'error'

const open = ref(false)
const title = ref('')
const body = ref('')
const variant = ref<AppDialogVariant>('info')

function defaultTitleFor(v: AppDialogVariant): string {
  if (v === 'error') {
    return 'Ошибка'
  }
  if (v === 'success') {
    return 'Готово'
  }
  return 'Сообщение'
}

export function showAppAlert(
  message: string,
  options?: { title?: string; variant?: AppDialogVariant },
): void {
  body.value = message
  const v = options?.variant ?? 'info'
  variant.value = v
  title.value = options?.title ?? defaultTitleFor(v)
  open.value = true
}

export function closeAppAlert(): void {
  open.value = false
}

export function useAppDialogPresentation(): {
  open: Ref<boolean>
  title: Ref<string>
  body: Ref<string>
  variant: Ref<AppDialogVariant>
  closeAppAlert: () => void
} {
  return { open, title, body, variant, closeAppAlert }
}
