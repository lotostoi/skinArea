<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
}>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
})
</script>

<template>
  <button
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center font-semibold rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
    :class="[
      {
        'bg-primary text-text-on-primary hover:bg-primary-hover': variant === 'primary',
        'bg-transparent text-text-primary border border-border hover:bg-surface-hover': variant === 'secondary',
        'bg-danger text-white hover:bg-red-600': variant === 'danger',
        'bg-transparent text-text-secondary hover:text-text-primary': variant === 'ghost',
      },
      {
        'px-3 py-1.5 text-xs': size === 'sm',
        'px-5 py-2.5 text-sm': size === 'md',
        'px-6 py-3 text-base': size === 'lg',
      },
    ]"
  >
    <div v-if="loading" class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin mr-2" />
    <slot />
  </button>
</template>
