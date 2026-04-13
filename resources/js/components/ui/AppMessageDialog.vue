<script setup lang="ts">
import { computed } from 'vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useAppDialogPresentation, type AppDialogVariant } from '@/composables/appDialog'

const { open, title, body, variant, closeAppAlert } = useAppDialogPresentation()

const stripeClass = computed((): string => {
  const map: Record<AppDialogVariant, string> = {
    info: 'border-l-[var(--color-primary)]',
    success: 'border-l-[#22c55e]',
    error: 'border-l-[var(--color-danger)]',
  }
  const v = variant.value as AppDialogVariant
  return map[v]
})
</script>

<template>
  <AppModal :open="open" :title="title" @close="closeAppAlert">
    <div class="border-l-4 pl-4 -ml-2" :class="stripeClass">
      <p class="text-sm text-text-primary leading-relaxed whitespace-pre-wrap">{{ body }}</p>
    </div>
    <div class="mt-6 flex justify-end gap-3">
      <AppButton class="min-w-[120px]" @click="closeAppAlert">Понятно</AppButton>
    </div>
  </AppModal>
</template>
