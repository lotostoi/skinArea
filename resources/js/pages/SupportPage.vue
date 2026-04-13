<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import {
  createSupportTicket,
  fetchSupportTicket,
  fetchSupportTickets,
  postSupportMessage,
} from '@/utils/support'
import type { SupportTicket } from '@/types/models'

const tickets = ref<SupportTicket[]>([])
const selected = ref<SupportTicket | null>(null)
const loadingList = ref(true)
const loadingTicket = ref(false)
const sending = ref(false)
const newSubject = ref('')
const messageBody = ref('')
const errorBanner = ref('')

async function loadList() {
  loadingList.value = true
  errorBanner.value = ''
  try {
    const res = await fetchSupportTickets(1, 50)
    tickets.value = res.data
    if (selected.value) {
      const still = res.data.find((t) => t.id === selected.value!.id)
      if (!still) {
        selected.value = null
      }
    }
  } catch {
    errorBanner.value = 'Не удалось загрузить обращения.'
  } finally {
    loadingList.value = false
  }
}

async function openTicket(id: number) {
  loadingTicket.value = true
  errorBanner.value = ''
  messageBody.value = ''
  try {
    selected.value = await fetchSupportTicket(id)
  } catch {
    errorBanner.value = 'Не удалось открыть обращение.'
    selected.value = null
  } finally {
    loadingTicket.value = false
  }
}

async function onCreateTicket() {
  errorBanner.value = ''
  loadingTicket.value = true
  try {
    const t = await createSupportTicket(newSubject.value || null)
    newSubject.value = ''
    await loadList()
    await openTicket(t.id)
  } catch {
    errorBanner.value = 'Не удалось создать обращение.'
  } finally {
    loadingTicket.value = false
  }
}

async function onSendMessage() {
  if (!selected.value || !messageBody.value.trim()) {
    return
  }
  sending.value = true
  errorBanner.value = ''
  try {
    await postSupportMessage(selected.value.id, messageBody.value.trim())
    messageBody.value = ''
    await openTicket(selected.value.id)
    await loadList()
  } catch (e: unknown) {
    const ax = e as { response?: { status?: number; data?: { message?: string } } }
    if (ax.response?.status === 422) {
      errorBanner.value = ax.response.data?.message ?? 'Сообщение не отправлено.'
    } else {
      errorBanner.value = 'Не удалось отправить сообщение.'
    }
  } finally {
    sending.value = false
  }
}

function ticketTitle(t: SupportTicket): string {
  return t.subject?.trim() ? t.subject : `Обращение #${t.id}`
}

onMounted(() => {
  void loadList()
})
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-text-primary">Поддержка</h1>

    <p v-if="errorBanner" class="text-sm text-danger">
      {{ errorBanner }}
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 min-h-[420px]">
      <div class="lg:col-span-1 bg-surface border border-border rounded-xl p-4 flex flex-col gap-4">
        <div class="space-y-2">
          <label class="block text-xs text-text-muted">Тема нового обращения (необязательно)</label>
          <input
            v-model="newSubject"
            type="text"
            maxlength="255"
            class="w-full px-3 py-2 rounded-md bg-[#16161e] border border-border text-text-primary text-sm focus:outline-none focus:border-primary"
            placeholder="Кратко, о чём речь"
          />
          <AppButton class="w-full" size="sm" :disabled="loadingTicket" @click="onCreateTicket">
            Новое обращение
          </AppButton>
        </div>

        <div class="border-t border-border pt-4 flex-1 min-h-0 flex flex-col">
          <h2 class="text-sm font-semibold text-text-secondary mb-2">Мои обращения</h2>
          <div v-if="loadingList" class="text-text-muted text-sm py-4">Загрузка…</div>
          <p v-else-if="tickets.length === 0" class="text-text-muted text-sm py-2">Пока нет обращений.</p>
          <ul v-else class="space-y-1 overflow-y-auto max-h-64 lg:max-h-[320px]">
            <li v-for="t in tickets" :key="t.id">
              <button
                type="button"
                class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors"
                :class="
                  selected?.id === t.id
                    ? 'bg-surface-hover text-text-primary border border-border'
                    : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                "
                @click="openTicket(t.id)"
              >
                <span class="block font-medium truncate">{{ ticketTitle(t) }}</span>
                <span class="block text-xs text-text-muted capitalize">{{ t.status }}</span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="lg:col-span-2 bg-surface border border-border rounded-xl p-6 flex flex-col min-h-[360px]">
        <div v-if="loadingTicket && !selected" class="text-text-muted flex-1 flex items-center justify-center">
          Загрузка…
        </div>
        <div v-else-if="!selected" class="text-text-muted flex-1 flex items-center justify-center text-center px-4">
          Выберите обращение слева или создайте новое.
        </div>
        <template v-else>
          <div class="border-b border-border pb-4 mb-4">
            <h2 class="text-lg font-semibold text-text-primary">{{ ticketTitle(selected) }}</h2>
            <p class="text-xs text-text-muted mt-1">
              Статус: <span class="capitalize">{{ selected.status }}</span>
            </p>
          </div>

          <div class="flex-1 overflow-y-auto space-y-3 mb-4 max-h-64 lg:max-h-80 pr-1">
            <div
              v-for="m in selected.messages ?? []"
              :key="m.id"
              class="rounded-lg px-3 py-2 text-sm border"
              :class="
                m.is_staff
                  ? 'border-secondary/40 bg-[#252530] text-text-primary ml-4'
                  : 'border-border bg-[#16161e] text-text-primary mr-4'
              "
            >
              <p class="whitespace-pre-wrap">{{ m.body }}</p>
              <p class="text-[11px] text-text-muted mt-1">
                {{ m.is_staff ? 'Поддержка' : 'Вы' }} · {{ new Date(m.created_at).toLocaleString() }}
              </p>
            </div>
            <p v-if="!(selected.messages?.length)" class="text-text-muted text-sm">
              Сообщений пока нет — напишите ниже.
            </p>
          </div>

          <div class="space-y-2 mt-auto">
            <label class="block text-xs text-text-muted">Сообщение</label>
            <textarea
              v-model="messageBody"
              rows="4"
              class="w-full px-3 py-2 rounded-md bg-[#16161e] border border-border text-text-primary text-sm focus:outline-none focus:border-primary resize-y"
              placeholder="Опишите проблему или задайте вопрос"
            />
            <AppButton :loading="sending" :disabled="!messageBody.trim()" @click="onSendMessage">
              Отправить
            </AppButton>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
