<script setup>
import { computed, ref } from 'vue'
import CheckoutCourseList from '../components/CheckoutCourseList.vue'
import CheckoutSummary from '../components/CheckoutSummary.vue'
import { createVnpayPayment } from '../services/api'
import { useCartStore } from '../stores/cart'

const cartStore = useCartStore()
const isProcessing = ref(false)
const paymentError = ref('')
const pendingOrderId = ref(null)

const itemPrice = (item) => Number(
  item.course?.final_price
    ?? item.course?.discount_price
    ?? item.price_snapshot
    ?? item.course?.price
    ?? 0,
)

const courses = computed(() => cartStore.items.map((item) => ({
  id: item.course_id,
  title: item.course?.title ?? 'Course unavailable',
  instructor: item.course?.instructor?.name ?? 'EduMarket Instructor',
  level: (item.course?.level ?? 'All Levels').toUpperCase(),
  tags: [item.course?.category?.name ?? 'Course'],
  rating: Number(item.course?.rating_avg ?? 0).toFixed(1),
  originalPrice: Number(item.course?.price ?? item.price_snapshot ?? 0),
  price: itemPrice(item),
  thumbnail: item.course?.thumbnail_url ?? '',
})))

const originalPrice = computed(() => courses.value.reduce((sum, course) => sum + course.originalPrice, 0))
const discount = computed(() => originalPrice.value - cartStore.totalAmount)

const proceedToPayment = async () => {
  if (isProcessing.value) return

  isProcessing.value = true
  paymentError.value = ''

  try {
    if (!pendingOrderId.value) {
      const checkoutResponse = await cartStore.doCheckout()
      const order = checkoutResponse.data

      pendingOrderId.value = order?.id ?? order?.order_id ?? null

      if (!pendingOrderId.value) {
        throw new Error('Backend không trả về order_id của đơn hàng vừa tạo.')
      }
    }

    const paymentResponse = await createVnpayPayment(pendingOrderId.value)
    const paymentUrl = paymentResponse.data?.data?.payment_url

    if (!paymentUrl) {
      throw new Error('Không nhận được URL thanh toán từ VNPay.')
    }

    window.location.href = paymentUrl
  } catch (error) {
    paymentError.value = error.response?.data?.message
      ?? error.message
      ?? 'Không thể chuyển đến cổng thanh toán VNPay. Vui lòng thử lại.'
    isProcessing.value = false
  }
}
</script>

<template>
  <main class="w-full max-w-container-max mx-auto px-margin-mobile md:px-gutter py-lg min-h-[calc(100vh-5rem)]">
    <div v-if="isProcessing" class="flex min-h-[400px] w-full min-w-0 items-center justify-center text-center">
      <div class="flex w-full min-w-0 max-w-[32rem] flex-col items-center justify-center rounded-xl border border-surface-variant bg-surface p-lg">
        <span class="material-symbols-outlined motion-safe:animate-spin text-5xl text-primary">progress_activity</span>
        <p class="sr-only motion-reduce:not-sr-only motion-reduce:mt-sm motion-reduce:w-full motion-reduce:text-body-md motion-reduce:text-primary">Đang xử lý...</p>
        <h1 class="mt-md w-full font-display text-headline-sm text-on-surface">Đang chuyển đến cổng thanh toán VNPay...</h1>
        <p class="mt-sm w-full text-body-md text-on-surface-variant">Vui lòng không đóng hoặc tải lại trang.</p>
      </div>
    </div>

    <div v-else-if="pendingOrderId && paymentError" class="flex min-h-[400px] w-full min-w-0 items-center justify-center text-center">
      <div class="w-full min-w-0 max-w-[32rem] rounded-xl border border-error/40 bg-surface p-lg">
        <span class="material-symbols-outlined text-5xl text-error">error</span>
        <h1 class="mt-md w-full font-display text-headline-sm text-on-surface">Chưa thể mở cổng thanh toán</h1>
        <p class="mt-sm w-full text-body-md text-on-surface-variant">{{ paymentError }}</p>
        <button
          class="mt-lg w-full cursor-pointer rounded-lg bg-primary-container px-md py-3 font-display text-button-text font-semibold text-on-primary-container transition-all hover:opacity-90 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60"
          type="button"
          :disabled="isProcessing"
          @click="proceedToPayment"
        >
          Thử lại chuyển tới VNPay
        </button>
      </div>
    </div>

    <template v-else>
      <div class="mb-lg flex w-full flex-wrap items-end justify-between gap-md">
        <div class="w-full min-w-0 sm:w-auto">
          <p class="mb-sm font-mono text-label-mono uppercase tracking-widest text-primary">Review your order</p>
          <h1 class="w-full font-display text-headline-md text-on-surface">Checkout</h1>
          <p class="mt-sm w-full text-body-md text-on-surface-variant">Confirm your courses and total before continuing to payment.</p>
        </div>
        <RouterLink to="/cart" class="shrink-0 cursor-pointer rounded text-body-sm text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">Back to Cart</RouterLink>
      </div>

      <p v-if="paymentError || cartStore.error" class="mb-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">
        {{ paymentError || cartStore.error }}
      </p>

      <div v-if="cartStore.items.length" class="checkout-layout grid w-full min-w-0 grid-cols-1 lg:grid-cols-12 gap-xl items-start">
        <div class="checkout-courses-column w-full min-w-0 lg:col-span-8">
          <CheckoutCourseList :courses="courses" />
        </div>

        <div class="checkout-summary-column w-full min-w-0 lg:col-span-4 lg:sticky lg:top-28">
          <CheckoutSummary
            :original-price="originalPrice"
            :discount="discount"
            :total="cartStore.totalAmount"
            :loading="isProcessing || cartStore.loading"
            @pay="proceedToPayment"
          />
        </div>
      </div>

      <div v-else class="flex min-h-[300px] w-full min-w-0 items-center justify-center rounded-lg border border-surface-variant bg-surface/50 text-center">
        <div class="w-full min-w-0 max-w-[32rem] p-md">
          <p class="w-full text-on-surface-variant">Your cart is empty. Add a course before checking out.</p>
          <RouterLink to="/" class="mt-md inline-block cursor-pointer rounded text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">Browse courses</RouterLink>
        </div>
      </div>
    </template>
  </main>
</template>

<style scoped>
@media (min-width: 1024px) {
  .checkout-layout {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .checkout-courses-column {
    grid-column: span 8 / span 8;
  }

  .checkout-summary-column {
    position: sticky;
    top: 7rem;
    grid-column: span 4 / span 4;
    align-self: start;
  }
}
</style>
