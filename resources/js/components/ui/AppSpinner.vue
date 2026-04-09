<script setup lang="ts">
withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg'
}>(), {
  size: 'md',
})
</script>

<template>
  <div
    class="border-4 border-primary border-t-transparent rounded-full animate-spin"
    :class="{
      'w-5 h-5 border-2': size === 'sm',
      'w-8 h-8 border-4': size === 'md',
      'w-12 h-12 border-4': size === 'lg',
    }"
  />
</template>
