<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue'
</script>

<template>
  <div class="min-h-screen bg-body">
    <AppHeader />
    <main class="max-w-[1400px] mx-auto px-6 py-6">
      <router-view />
    </main>
  </div>
</template>
