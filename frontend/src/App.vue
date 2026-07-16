<script setup>
import { watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Footer from './components/Footer.vue'
import Header from './components/Header.vue'
import ChatbotWidget from './components/ChatbotWidget.vue'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'

const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

watch(
  [() => authStore.isAuthenticated, () => route.meta.dashboardLayout],
  async ([isAuthenticated, isDashboard]) => {
    if (isAuthenticated && !isDashboard) {
      try {
        await cartStore.fetchCart()
      } catch {
        // Shared interceptor/store exposes authentication and API failures.
      }
    } else if (!isAuthenticated) {
      cartStore.clearCart()
    }
  },
  { immediate: true },
)
</script>

<template>
  <div class="min-h-screen bg-background text-on-background font-body">
    <Header v-if="!route.meta.dashboardLayout" />
    <RouterView />
    <Footer v-if="!route.meta.hideFooter && !route.meta.dashboardLayout" />
    <ChatbotWidget v-if="authStore.isAuthenticated && route.name !== 'lesson-player'" />
  </div>
</template>
