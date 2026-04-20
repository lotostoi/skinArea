<script setup lang="ts">
withDefaults(
  defineProps<{
    compact?: boolean
  }>(),
  { compact: false },
)
</script>

<template>
  <div
    class="flex flex-col border border-border rounded-lg bg-surface p-3 animate-pulse pointer-events-none select-none"
    aria-hidden="true"
  >
    <div class="aspect-square bg-input rounded-md mb-2" />
    <div class="h-3.5 bg-input rounded mb-1" :class="compact ? 'w-[92%]' : 'w-[88%]'" />
    <div class="h-3 bg-input rounded w-1/3 mb-1" />
    <div class="h-5 bg-input rounded w-24 mb-2" />
    <div class="mt-2 flex items-center justify-between gap-2">
      <div class="h-3 bg-input rounded flex-1 max-w-[40%]" />
      <div class="h-7 bg-input rounded w-[4.5rem] shrink-0" />
    </div>
  </div>
</template>
