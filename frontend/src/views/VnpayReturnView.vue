<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getVnpayReturn } from '../api/axios'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

const route = useRoute()
const cartStore = useCartStore()

const result = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')

onMounted(async () => {
  try {
    const response = await getVnpayReturn({ ...route.query })
    result.value = response.data.data

    if (result.value?.is_success) {
      try {
        await cartStore.fetchCart()
      } catch {
        // Payment result is already verified; cart will resync on the next authenticated fetch.
      }
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message
      ?? 'Không thể kiểm tra kết quả thanh toán. Vui lòng thử lại sau.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <main class="flex min-h-[calc(100vh-5rem)] w-full min-w-0 items-center justify-center px-margin-mobile py-xl md:px-gutter">
    <div
      v-if="isLoading"
      class="flex w-full min-w-0 max-w-[32rem] flex-col items-center justify-center rounded-xl border border-surface-variant bg-surface p-lg text-center"
    >
      <span class="material-symbols-outlined animate-spin text-5xl text-primary">progress_activity</span>
      <h1 class="mt-md w-full font-display text-headline-sm text-on-surface">Đang kiểm tra thanh toán</h1>
      <p class="mt-sm w-full text-body-md text-on-surface-variant">Vui lòng không đóng trang này.</p>
    </div>

    <div
      v-else-if="result?.is_success"
      class="w-full min-w-0 max-w-[32rem] rounded-xl border border-[var(--accent-success)]/40 bg-surface p-lg text-center"
    >
      <span class="material-symbols-outlined text-5xl text-[var(--accent-success)]">check_circle</span>
      <h1 class="mt-md w-full font-display text-headline-md text-on-surface">Thanh toán thành công</h1>
      <p class="mt-sm w-full text-body-md text-on-surface-variant">
        Đơn hàng <span class="font-mono text-primary">{{ result.order_code }}</span> đã được VNPay xác nhận trên trang trả về.
      </p>

      <div class="mt-lg w-full min-w-0 rounded-lg border border-surface-variant bg-background/40 p-md text-left">
        <div class="flex w-full min-w-0 items-center justify-between gap-md">
          <span class="text-body-sm text-on-surface-variant">Số tiền</span>
          <span class="shrink-0 font-mono text-headline-sm text-on-surface">{{ formatCurrency(result.amount) }}</span>
        </div>
      </div>

      <RouterLink to="/" class="mt-lg inline-block w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-on-primary transition-all hover:opacity-90 active:scale-[0.99]">
        Tiếp tục
      </RouterLink>
    </div>

    <div
      v-else-if="result"
      class="w-full min-w-0 max-w-[32rem] rounded-xl border border-tertiary/40 bg-surface p-lg text-center"
    >
      <span class="material-symbols-outlined text-5xl text-tertiary">warning</span>
      <h1 class="mt-md w-full font-display text-headline-md text-on-surface">Thanh toán không thành công</h1>
      <p class="mt-sm w-full text-body-md text-on-surface-variant">
        Mã phản hồi VNPay: <span class="font-mono text-tertiary">{{ result.response_code || 'Không xác định' }}</span>
      </p>
      <RouterLink to="/cart" class="mt-lg inline-block w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-on-primary transition-all hover:opacity-90 active:scale-[0.99]">
        Thử lại
      </RouterLink>
    </div>

    <div
      v-else
      class="w-full min-w-0 max-w-[32rem] rounded-xl border border-error/40 bg-surface p-lg text-center"
    >
      <span class="material-symbols-outlined text-5xl text-error">error</span>
      <h1 class="mt-md w-full font-display text-headline-md text-on-surface">Không thể kiểm tra thanh toán</h1>
      <p class="mt-sm w-full text-body-md text-on-surface-variant">{{ errorMessage }}</p>
      <RouterLink to="/" class="mt-lg inline-block w-full rounded-lg border border-surface-variant px-md py-3 font-display text-button-text text-on-surface transition-colors hover:border-primary hover:text-primary">
        Về trang chủ
      </RouterLink>
    </div>
  </main>
</template>
