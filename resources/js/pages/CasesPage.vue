<script setup lang="ts">
import EmptyStateGraphic from '@/components/ui/EmptyStateGraphic.vue'
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">Кейсы</h1>
    <div class="bg-surface border border-border rounded-xl p-12 text-center">
      <EmptyStateGraphic variant="cases" />
      <h2 class="text-xl font-semibold mb-2 text-text-primary">Кейсы скоро откроются</h2>
      <p class="text-text-secondary">Открытие кейсов CS2 с анимацией рулетки. Совсем скоро.</p>
    </div>
  </div>
</template>
