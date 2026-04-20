<script setup lang="ts">
withDefaults(
  defineProps<{
    /** Узкая карточка для горизонтальной ленты на главной */
    ribbon?: boolean
  }>(),
  { ribbon: false },
)
</script>

<template>
  <div
    class="rounded-lg border border-border bg-surface p-3 flex flex-col h-full animate-pulse pointer-events-none select-none w-full min-h-0"
    aria-hidden="true"
  >
    <div class="aspect-[4/5] bg-input rounded-md mb-3" />
    <template v-if="ribbon">
      <div class="h-3.5 bg-input rounded w-full mb-1.5" />
      <div class="h-3.5 bg-input rounded w-14" />
    </template>
    <template v-else>
      <div class="h-4 bg-input rounded w-[80%] mb-2" />
      <div class="h-3 bg-input rounded w-2/5 mb-3" />
      <div class="mt-auto flex items-center justify-between gap-2">
        <div class="h-6 bg-input rounded w-20" />
        <div class="h-8 bg-input rounded w-24 shrink-0" />
      </div>
    </template>
  </div>
</template>
