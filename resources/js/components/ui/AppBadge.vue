<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'secondary'
}>(), {
  variant: 'primary',
})
</script>

<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="{
      'bg-primary/20 text-primary': variant === 'primary',
      'bg-success/20 text-success': variant === 'success',
      'bg-warning/20 text-warning': variant === 'warning',
      'bg-danger/20 text-danger': variant === 'danger',
      'bg-info/20 text-info': variant === 'info',
      'bg-secondary/20 text-secondary': variant === 'secondary',
    }"
  >
    <slot />
  </span>
</template>
