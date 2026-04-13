<script setup lang="ts">
import EmptyStateGraphic from '@/components/ui/EmptyStateGraphic.vue'
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">Апгрейд</h1>
    <div class="bg-surface border border-border rounded-xl p-12 text-center">
      <EmptyStateGraphic variant="upgrade" />
      <h2 class="text-xl font-semibold mb-2 text-text-primary">Апгрейд скоро будет</h2>
      <p class="text-text-secondary">Поставь баланс — получи скин дороже. Скоро.</p>
    </div>
  </div>
</template>
