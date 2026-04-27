<script setup lang="ts">
import { useRoute } from 'vue-router'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppMessageDialog from '@/components/ui/AppMessageDialog.vue'
import DepositBalanceModal from '@/components/layout/DepositBalanceModal.vue'
import { useDepositModalStore } from '@/stores/depositModal'

const route = useRoute()
const depositModal = useDepositModalStore()
</script>

<template>
  <div class="min-h-screen bg-body">
    <AppHeader />
    <main class="max-w-[1400px] mx-auto px-6 py-6">
      <router-view :key="String(route.name ?? route.path)" />
    </main>
    <DepositBalanceModal v-if="depositModal.isOpen" />
    <AppMessageDialog />
  </div>
</template>
