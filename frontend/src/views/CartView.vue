<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CartItemList from '../components/CartItemList.vue'
import CartSummary from '../components/CartSummary.vue'
import EmptyCartState from '../components/EmptyCartState.vue'
import { useCartStore } from '../stores/cart'

const router = useRouter()
const cartStore = useCartStore()

const itemPrice = (item) => Number(
  item.course?.final_price
    ?? item.course?.discount_price
    ?? item.price_snapshot
    ?? item.course?.price
    ?? 0,
)

const cartItems = computed(() => cartStore.items.map((item) => ({
  id: item.id,
  title: item.course?.title ?? 'Course unavailable',
  instructor: item.course?.instructor?.name ?? 'EduMarket Instructor',
  instructorRole: item.course?.instructor?.role?.display_name ?? 'Instructor',
  tag: item.course?.category?.name ?? 'Course',
  bestseller: false,
  rating: Number(item.course?.rating_avg ?? 0).toFixed(1),
  reviewCount: '0',
  originalPrice: Number(item.course?.price ?? item.price_snapshot ?? 0),
  price: itemPrice(item),
  thumbnail: item.course?.thumbnail_url ?? '',
})))

const originalPrice = computed(() => cartItems.value.reduce((sum, item) => sum + item.originalPrice, 0))
const discount = computed(() => originalPrice.value - cartStore.totalAmount)

const removeItem = async (itemId) => {
  try {
    await cartStore.removeItem(itemId)
  } catch {
    // The store exposes the backend error for the UI.
  }
}

onMounted(async () => {
  try {
    await cartStore.fetchCart()
  } catch {
    // Authentication and API errors are handled by the shared interceptor/store.
  }
})
</script>

<template>
  <main class="w-full max-w-container-max mx-auto px-margin-mobile md:px-gutter py-lg min-h-[calc(100vh-320px)]">
    <h1 class="w-full font-display text-headline-md mb-lg">
      Your Cart
      <span class="text-on-surface-variant text-body-md font-body font-normal ml-2">({{ cartStore.itemCount }} items)</span>
    </h1>

    <div v-if="cartStore.loading && !cartStore.items.length" class="grid w-full grid-cols-1 gap-xl lg:grid-cols-12">
      <div class="h-64 animate-pulse rounded-lg bg-surface lg:col-span-8"></div>
      <div class="h-80 animate-pulse rounded-lg bg-surface lg:col-span-4"></div>
    </div>

    <p v-else-if="cartStore.error && !cartStore.items.length" class="w-full rounded-lg border border-error/40 bg-error/10 p-md text-error">
      {{ cartStore.error }}
    </p>

    <div v-else-if="cartStore.items.length" class="cart-layout grid w-full min-w-0 grid-cols-1 lg:grid-cols-12 gap-xl items-start">
      <div class="cart-items-column w-full min-w-0 lg:col-span-8">
        <p v-if="cartStore.error" class="mb-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">
          {{ cartStore.error }}
        </p>
        <CartItemList :items="cartItems" @remove="removeItem" />
      </div>

      <div class="cart-summary-column w-full min-w-0 lg:col-span-4 lg:sticky lg:top-28">
        <CartSummary
          :original-price="originalPrice"
          :discount="discount"
          :total="cartStore.totalAmount"
          @checkout="router.push('/checkout')"
        />
      </div>
    </div>

    <EmptyCartState v-else />
  </main>
</template>

<style scoped>
@media (min-width: 1024px) {
  .cart-layout {
    grid-template-columns: repeat(12, minmax(0, 1fr));
  }

  .cart-items-column {
    grid-column: span 8 / span 8;
  }

  .cart-summary-column {
    position: sticky;
    top: 7rem;
    grid-column: span 4 / span 4;
    align-self: start;
  }
}
</style>
