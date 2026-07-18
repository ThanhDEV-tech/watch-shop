<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { gsap } from 'gsap'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

const router = useRouter()
const cartStore = useCartStore()
const cartRoot = ref(null)
let ctx

const movementLabel = (value) => ({
  quartz: 'Quartz',
  automatic: 'Automatic',
}[value] ?? value)

const unitPrice = (item) => Number(
  item.product_variant?.final_price
    ?? item.product_variant?.discount_price
    ?? item.product_variant?.price
    ?? 0,
)

const lineTotal = (item) => unitPrice(item) * Number(item.quantity ?? 1)

const itemImage = (item) => (
  item.product_variant?.image
    || item.product_variant?.product?.thumbnail
    || 'https://placehold.co/600x600/F8F5EF/111111?text=Watchora'
)

const itemStatus = (item) => {
  const variant = item.product_variant
  const product = variant?.product

  if (!variant || !product || !variant.is_active || product.status !== 'active') {
    return {
      valid: false,
      message: 'Sản phẩm hoặc SKU này hiện không còn được bán.',
    }
  }

  if (Number(variant.stock_quantity ?? 0) <= 0) {
    return {
      valid: false,
      message: 'SKU này đã hết hàng.',
    }
  }

  if (Number(item.quantity ?? 1) > Number(variant.stock_quantity ?? 0)) {
    return {
      valid: false,
      message: `Chỉ còn ${variant.stock_quantity} sản phẩm trong kho.`,
    }
  }

  return { valid: true, message: '' }
}

const hasInvalidItems = computed(() => cartStore.items.some((item) => !itemStatus(item).valid))
const subtotal = computed(() => cartStore.items.reduce((sum, item) => sum + lineTotal(item), 0))

const removeItem = async (item, event) => {
  const target = event?.currentTarget?.closest('[data-cart-item]')

  if (target && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    await gsap.to(target, {
      autoAlpha: 0,
      x: -24,
      height: 0,
      marginBottom: 0,
      paddingTop: 0,
      paddingBottom: 0,
      duration: 0.26,
      ease: 'power2.inOut',
    })
  }

  try {
    await cartStore.removeItem(item.id)
  } catch {
    // Store exposes backend error.
  }
}

const changeQuantity = async (item, amount) => {
  const nextQuantity = Number(item.quantity ?? 1) + amount

  if (nextQuantity < 1) return
  if (nextQuantity > Number(item.product_variant?.stock_quantity ?? 0)) return

  try {
    await cartStore.setItemQuantity(item, nextQuantity)
  } catch {
    // Store exposes backend error.
  }
}

const goCheckout = () => {
  if (hasInvalidItems.value || !cartStore.items.length) return
  router.push('/checkout')
}

onMounted(async () => {
  ctx = gsap.context(() => {}, cartRoot.value)

  try {
    await cartStore.fetchCart()
    await nextTick()

    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      ctx.add(() => {
        gsap.from('[data-cart-item]', {
          autoAlpha: 0,
          y: 14,
          duration: 0.34,
          ease: 'power2.out',
          stagger: 0.05,
        })
      })
    }
  } catch {
    // Authentication/API errors are surfaced by store/interceptor.
  }
})

onUnmounted(() => {
  ctx?.revert()
})
</script>

<template>
  <main ref="cartRoot" class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-container-max px-margin-mobile py-12 md:px-gutter">
    <div class="flex w-full min-w-0 flex-col gap-3 md:flex-row md:items-end md:justify-between">
      <div class="w-full min-w-0">
        <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">Shopping cart</p>
        <h1 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
          Giỏ hàng của bạn
        </h1>
        <p class="mt-4 w-full max-w-2xl text-base leading-7 text-on-surface-variant">
          Kiểm tra đúng SKU, màu dây, màu mặt và số lượng trước khi thanh toán qua VNPay.
        </p>
      </div>
      <RouterLink to="/products" class="watch-accent-strong w-fit text-sm font-bold uppercase tracking-[0.14em]">
        Khám phá thêm
      </RouterLink>
    </div>

    <div v-if="cartStore.loading && !cartStore.items.length" class="mt-10 grid w-full grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
      <div class="h-72 animate-pulse rounded-[var(--radius-watch-lg)] bg-surface"></div>
      <div class="h-72 animate-pulse rounded-[var(--radius-watch-lg)] bg-surface"></div>
    </div>

    <p v-else-if="cartStore.error && !cartStore.items.length" class="mt-8 w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-4 text-[var(--accent-danger)]">
      {{ cartStore.error }}
    </p>

    <section v-else-if="cartStore.items.length" class="mt-10 grid w-full min-w-0 grid-cols-1 gap-8 lg:grid-cols-[1fr_360px]">
      <div class="w-full min-w-0 space-y-4">
        <p v-if="cartStore.error" class="w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-4 text-sm text-[var(--accent-danger)]">
          {{ cartStore.error }}
        </p>

        <article
          v-for="item in cartStore.items"
          :key="item.id"
          data-cart-item
          class="grid w-full min-w-0 gap-4 rounded-[var(--radius-watch-lg)] border border-border bg-surface p-4 shadow-[var(--shadow-watch-soft)] md:grid-cols-[150px_1fr_auto]"
        >
          <RouterLink :to="`/products/${item.product_variant?.product?.slug ?? ''}`" class="aspect-square w-full overflow-hidden rounded-[var(--radius-watch-md)] bg-background md:w-[150px]">
            <img :src="itemImage(item)" :alt="item.product_variant?.product?.name" class="h-full w-full object-cover" />
          </RouterLink>

          <div class="flex w-full min-w-0 flex-col">
            <div class="w-full min-w-0">
              <p class="w-full text-xs font-bold uppercase tracking-[0.16em] text-[var(--accent-primary)]">
                {{ item.product_variant?.product?.brand?.name ?? 'Watchora' }}
              </p>
              <RouterLink :to="`/products/${item.product_variant?.product?.slug ?? ''}`" class="mt-2 block w-full font-display text-3xl font-semibold leading-none text-primary hover:text-[var(--accent-primary)]">
                {{ item.product_variant?.product?.name ?? 'Sản phẩm không khả dụng' }}
              </RouterLink>
              <p class="mt-2 w-full text-sm text-on-surface-variant">
                SKU {{ item.product_variant?.sku }} · {{ item.product_variant?.strap_color }} dây / {{ item.product_variant?.dial_color }} mặt / {{ item.product_variant?.diameter_mm }}mm / {{ movementLabel(item.product_variant?.movement_type) }}
              </p>
            </div>

            <p v-if="!itemStatus(item).valid" class="mt-4 w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] px-4 py-3 text-sm font-semibold text-[var(--accent-danger)]">
              {{ itemStatus(item).message }}
            </p>

            <div class="mt-auto flex w-full min-w-0 flex-wrap items-center gap-4 pt-5">
              <div class="flex h-11 w-36 items-center rounded-[var(--radius-watch-md)] border border-border bg-background">
                <button
                  type="button"
                  class="h-full w-11 cursor-pointer text-lg font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="Number(item.quantity) <= 1 || cartStore.loading"
                  @click="changeQuantity(item, -1)"
                >
                  -
                </button>
                <span class="w-full min-w-0 border-x border-border text-center font-semibold">{{ item.quantity }}</span>
                <button
                  type="button"
                  class="h-full w-11 cursor-pointer text-lg font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="cartStore.loading || Number(item.quantity) >= Number(item.product_variant?.stock_quantity ?? 0)"
                  @click="changeQuantity(item, 1)"
                >
                  +
                </button>
              </div>
              <p class="min-w-0 text-sm text-on-surface-variant">
                Còn {{ item.product_variant?.stock_quantity ?? 0 }} trong kho
              </p>
            </div>
          </div>

          <div class="flex w-full min-w-0 flex-row items-end justify-between gap-4 md:w-36 md:flex-col md:text-right">
            <button
              type="button"
              class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-border text-on-surface-variant transition-colors hover:border-[var(--accent-danger)] hover:text-[var(--accent-danger)]"
              aria-label="Xóa khỏi giỏ hàng"
              @click="removeItem(item, $event)"
            >
              <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
            <div class="min-w-0">
              <p class="w-full text-sm text-on-surface-variant">{{ formatCurrency(unitPrice(item)) }}</p>
              <p class="watch-price-accent mt-1 w-full text-xl">{{ formatCurrency(lineTotal(item)) }}</p>
            </div>
          </div>
        </article>
      </div>

      <aside class="w-full min-w-0 lg:sticky lg:top-28">
        <div class="rounded-[var(--radius-watch-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-watch-soft)]">
          <h2 class="w-full font-display text-3xl font-semibold text-primary">Tóm tắt</h2>
          <div class="mt-6 space-y-4">
            <div class="flex w-full justify-between gap-4 text-sm text-on-surface-variant">
              <span>Tạm tính</span>
              <span class="shrink-0 font-semibold text-on-surface">{{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="flex w-full justify-between gap-4 text-sm text-on-surface-variant">
              <span>Số sản phẩm</span>
              <span class="shrink-0 font-semibold text-on-surface">{{ cartStore.itemCount }}</span>
            </div>
            <div class="h-px w-full bg-border"></div>
            <div class="flex w-full items-center justify-between gap-4">
              <span class="font-display text-2xl font-semibold text-primary">Subtotal</span>
              <span class="watch-price-accent shrink-0 text-2xl">{{ formatCurrency(subtotal) }}</span>
            </div>
          </div>

          <p v-if="hasInvalidItems" class="mt-5 w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-3 text-sm text-[var(--accent-danger)]">
            Vui lòng xóa hoặc điều chỉnh sản phẩm không khả dụng trước khi checkout.
          </p>

          <button
            type="button"
            class="mt-6 min-h-12 w-full cursor-pointer rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-bold uppercase tracking-[0.14em] text-on-primary transition-colors hover:bg-[var(--accent-primary-hover)] disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="hasInvalidItems || cartStore.loading"
            @click="goCheckout"
          >
            Tiến hành thanh toán
          </button>
        </div>
      </aside>
    </section>

    <section v-else class="mt-12 flex w-full min-w-0 flex-col items-center justify-center rounded-[var(--radius-watch-lg)] border border-border bg-surface px-6 py-16 text-center shadow-[var(--shadow-watch-soft)]">
      <div class="flex h-20 w-20 items-center justify-center rounded-full border border-[rgb(161_98_7/0.28)] bg-[rgb(161_98_7/0.08)]">
        <span class="material-symbols-outlined text-4xl text-[var(--accent-primary)]">shopping_bag</span>
      </div>
      <h2 class="mt-6 w-full font-display text-4xl font-semibold text-primary">Giỏ hàng đang trống</h2>
      <p class="mt-3 w-full max-w-xl text-base leading-7 text-on-surface-variant">
        Chọn một mẫu đồng hồ, cấu hình đúng variant và quay lại đây để hoàn tất thanh toán.
      </p>
      <RouterLink to="/products" class="mt-7 inline-flex min-h-12 items-center justify-center rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-bold uppercase tracking-[0.14em] text-on-primary transition-colors hover:bg-[var(--accent-primary-hover)]">
        Khám phá sản phẩm
      </RouterLink>
    </section>
  </main>
</template>
