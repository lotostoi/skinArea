<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import api from '@/utils/api'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import { useBalanceStore } from '@/stores/balance'
import { useDepositModalStore } from '@/stores/depositModal'
import { DEPOSIT_SUCCESS_EVENT } from '@/utils/constants'

type Step = 'form' | 'processing' | 'success' | 'error'

const FAKE_PAYMENT_DURATION_MS = 2000

const depositModal = useDepositModalStore()
const balanceStore = useBalanceStore()

const amountInput = ref('')
const step = ref<Step>('form')
const progress = ref(0)
const errorMessage = ref<string | null>(null)
const depositedAmount = ref('0')

let progressTimer: ReturnType<typeof setInterval> | null = null

const isOpen = computed(() => depositModal.isOpen)

const amountNumber = computed(() => {
  const normalized = amountInput.value.replace(',', '.').trim()
  const n = Number(normalized)
  return Number.isFinite(n) ? n : NaN
})

const isAmountValid = computed(() => !Number.isNaN(amountNumber.value) && amountNumber.value > 0)

const formErrorText = ref<string | null>(null)

function formatMoney(value: string | number): string {
  const n = typeof value === 'number' ? value : Number(value)
  if (!Number.isFinite(n)) {
    return `${value} ₽`
  }
  return `${n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`
}

function resetState(): void {
  stopProgress()
  amountInput.value = ''
  step.value = 'form'
  progress.value = 0
  errorMessage.value = null
  formErrorText.value = null
  depositedAmount.value = '0'
}

function stopProgress(): void {
  if (progressTimer !== null) {
    clearInterval(progressTimer)
    progressTimer = null
  }
}

function startFakeProgress(): void {
  progress.value = 0
  const stepMs = 40
  const increment = (100 * stepMs) / FAKE_PAYMENT_DURATION_MS
  progressTimer = setInterval(() => {
    progress.value = Math.min(99, progress.value + increment)
  }, stepMs)
}

async function submit(): Promise<void> {
  formErrorText.value = null

  if (!isAmountValid.value) {
    formErrorText.value = 'Введите сумму больше нуля'
    return
  }

  const amount = amountNumber.value.toFixed(2)
  step.value = 'processing'
  startFakeProgress()

  const minWaitPromise = new Promise<void>((resolve) => {
    setTimeout(resolve, FAKE_PAYMENT_DURATION_MS)
  })

  try {
    await Promise.all([
      api.post('/balance/deposit', { amount: Number(amount) }),
      minWaitPromise,
    ])

    stopProgress()
    progress.value = 100
    depositedAmount.value = amount

    await balanceStore.fetchBalances()
    window.dispatchEvent(new CustomEvent(DEPOSIT_SUCCESS_EVENT))

    await nextTick()
    step.value = 'success'
  } catch (e: unknown) {
    stopProgress()
    const ax = e as {
      response?: { data?: { message?: string; errors?: Record<string, string[]> } }
    }
    const firstErr = ax.response?.data?.errors
    const flat = firstErr ? Object.values(firstErr).flat()[0] : undefined
    errorMessage.value =
      (typeof flat === 'string' ? flat : null) ??
      ax.response?.data?.message ??
      'Не удалось выполнить пополнение. Попробуйте ещё раз.'
    step.value = 'error'
  }
}

function closeModal(): void {
  depositModal.close()
}

function tryAgain(): void {
  step.value = 'form'
  progress.value = 0
  errorMessage.value = null
}

watch(
  () => depositModal.isOpen,
  (value) => {
    if (value) {
      resetState()
    } else {
      stopProgress()
    }
  },
)
</script>

<template>
  <AppModal :open="isOpen" title="Пополнение баланса" @close="closeModal">
    <div v-if="step === 'form'" class="space-y-5">
      <p class="text-sm text-text-secondary">
        Введите сумму пополнения в рублях. После оплаты средства будут зачислены на основной баланс.
      </p>

      <div>
        <AppInput
          v-model="amountInput"
          label="Сумма, ₽"
          type="text"
          placeholder="Например, 1000"
          :error="formErrorText ?? undefined"
        />
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <AppButton variant="secondary" @click="closeModal">Отмена</AppButton>
        <AppButton :disabled="!isAmountValid" @click="submit">Оплатить</AppButton>
      </div>
    </div>

    <div v-else-if="step === 'processing'" class="space-y-5 py-2">
      <div class="flex flex-col items-center gap-3 text-center">
        <div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full animate-spin" />
        <h3 class="text-base font-semibold text-text-primary">Обработка платежа…</h3>
        <p class="text-sm text-text-secondary">
          Переводим вас в платёжную систему. Не закрывайте окно.
        </p>
      </div>

      <div class="w-full h-2 rounded-full bg-[#16161e] overflow-hidden">
        <div
          class="h-full bg-primary transition-[width] duration-100 ease-linear"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>

    <div v-else-if="step === 'success'" class="space-y-5">
      <div class="flex flex-col items-center gap-3 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/15 text-success">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <h3 class="text-base font-semibold text-text-primary">Пополнение прошло</h3>
        <p class="text-sm text-text-secondary">
          На основной баланс зачислено
          <span class="font-semibold text-primary">{{ formatMoney(depositedAmount) }}</span>
        </p>
      </div>

      <div class="flex items-center justify-end pt-2">
        <AppButton @click="closeModal">Закрыть</AppButton>
      </div>
    </div>

    <div v-else-if="step === 'error'" class="space-y-5">
      <div class="flex flex-col items-center gap-3 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-danger/15 text-danger">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
          </svg>
        </div>
        <h3 class="text-base font-semibold text-text-primary">Ошибка пополнения</h3>
        <p class="text-sm text-text-secondary">{{ errorMessage }}</p>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <AppButton variant="secondary" @click="closeModal">Закрыть</AppButton>
        <AppButton @click="tryAgain">Попробовать снова</AppButton>
      </div>
    </div>
  </AppModal>
</template>
