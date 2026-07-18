<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createVnpayPayment, getShippingZones } from '../api/axios'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

const router = useRouter()
const cartStore = useCartStore()

const isProcessing = ref(false)
const isLoadingShippingZones = ref(true)
const paymentError = ref('')
const pendingOrderId = ref(null)
const shippingZones = ref([])

const form = reactive({
  receiver_name: '',
  receiver_phone: '',
  shipping_address: '',
  shipping_note: '',
  shipping_zone_id: '',
})

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
const subtotal = computed(() => cartStore.items.reduce((sum, item) => sum + lineTotal(item), 0))
const selectedZone = computed(() => shippingZones.value.find((zone) => zone.id === Number(form.shipping_zone_id)) ?? shippingZones.value[0] ?? null)
const shippingFee = computed(() => Number(selectedZone.value?.fee ?? 0))
const totalAmount = computed(() => subtotal.value + shippingFee.value)

const invalidItems = computed(() => cartStore.items.filter((item) => {
  const variant = item.product_variant
  const product = variant?.product

  return !variant
    || !product
    || !variant.is_active
    || product.status !== 'active'
    || Number(variant.stock_quantity ?? 0) < Number(item.quantity ?? 1)
}))

const formErrors = computed(() => {
  const errors = {}

  if (!form.receiver_name.trim()) errors.receiver_name = 'Vui lòng nhập tên người nhận.'
  if (!form.receiver_phone.trim()) errors.receiver_phone = 'Vui lòng nhập số điện thoại.'
  if (!form.shipping_address.trim()) errors.shipping_address = 'Vui lòng nhập địa chỉ giao hàng.'
  if (!shippingZones.value.length) errors.shipping_zone_id = 'Chưa có khu vực giao hàng khả dụng.'
  else if (!selectedZone.value) errors.shipping_zone_id = 'Vui lòng chọn khu vực giao hàng.'

  return errors
})

const canSubmit = computed(() => (
  cartStore.items.length > 0
  && invalidItems.value.length === 0
  && Object.keys(formErrors.value).length === 0
  && !isProcessing.value
  && !isLoadingShippingZones.value
))

const itemImage = (item) => (
  item.product_variant?.image
    || item.product_variant?.product?.thumbnail
    || 'https://placehold.co/600x600/F8F5EF/111111?text=Watchora'
)

const proceedToPayment = async () => {
  paymentError.value = ''

  if (!canSubmit.value) {
    paymentError.value = invalidItems.value.length
      ? 'Giỏ hàng có sản phẩm không còn đủ điều kiện checkout.'
      : 'Vui lòng kiểm tra đầy đủ thông tin giao hàng.'
    return
  }

  isProcessing.value = true

  try {
    if (!pendingOrderId.value) {
      const checkoutResponse = await cartStore.doCheckout({
        receiver_name: form.receiver_name.trim(),
        receiver_phone: form.receiver_phone.trim(),
        shipping_address: form.shipping_address.trim(),
        shipping_note: form.shipping_note.trim() || null,
        shipping_zone_id: Number(form.shipping_zone_id),
      })
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

const fetchShippingZones = async () => {
  isLoadingShippingZones.value = true

  try {
    const response = await getShippingZones()
    shippingZones.value = response.data.data ?? []

    if (!selectedZone.value && shippingZones.value.length) {
      form.shipping_zone_id = shippingZones.value[0].id
    }
  } catch (error) {
    paymentError.value = error.response?.data?.message ?? 'Không thể tải khu vực giao hàng.'
  } finally {
    isLoadingShippingZones.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([cartStore.fetchCart(), fetchShippingZones()])
  } catch {
    // Shared interceptor/store handles auth and API errors.
  }
})
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-container-max px-margin-mobile py-12 md:px-gutter">
    <div v-if="isProcessing" class="flex min-h-[420px] w-full min-w-0 items-center justify-center text-center">
      <div class="flex w-full min-w-0 max-w-xl flex-col items-center justify-center rounded-[var(--radius-watch-lg)] border border-border bg-surface p-8 shadow-[var(--shadow-watch-soft)]">
        <span class="material-symbols-outlined motion-safe:animate-spin text-5xl text-[var(--accent-primary)]">progress_activity</span>
        <h1 class="mt-5 w-full font-display text-4xl font-semibold text-primary">Đang chuyển đến VNPay</h1>
        <p class="mt-3 w-full text-base text-on-surface-variant">Đơn hàng pending đã được tạo. Vui lòng không đóng trang.</p>
      </div>
    </div>

    <template v-else>
      <div class="flex w-full min-w-0 flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="w-full min-w-0">
          <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">Secure checkout</p>
          <h1 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
            Thanh toán
          </h1>
          <p class="mt-4 w-full max-w-2xl text-base leading-7 text-on-surface-variant">
            Nhập thông tin nhận hàng, chọn khu vực giao hàng và thanh toán qua VNPay Sandbox.
          </p>
        </div>
        <RouterLink to="/cart" class="watch-accent-strong w-fit text-sm font-bold uppercase tracking-[0.14em]">
          Quay lại giỏ hàng
        </RouterLink>
      </div>

      <p v-if="paymentError || cartStore.error" class="mt-8 w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-4 text-sm text-[var(--accent-danger)]">
        {{ paymentError || cartStore.error }}
      </p>

      <div v-if="cartStore.items.length" class="mt-10 grid w-full min-w-0 grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
        <section class="w-full min-w-0 space-y-8">
          <form class="w-full min-w-0 rounded-[var(--radius-watch-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-watch-soft)]" @submit.prevent="proceedToPayment">
            <h2 class="w-full font-display text-3xl font-semibold text-primary">Thông tin nhận hàng</h2>

            <div class="mt-6 grid w-full min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
              <label class="block w-full min-w-0">
                <span class="w-full text-sm font-semibold text-on-surface">Người nhận</span>
                <input
                  v-model="form.receiver_name"
                  class="mt-2 h-12 w-full rounded-[var(--radius-watch-md)] border border-border bg-background px-4 text-on-surface outline-none focus:border-[var(--accent-primary)]"
                  type="text"
                  autocomplete="name"
                />
                <span v-if="formErrors.receiver_name" class="mt-1 block w-full text-xs text-[var(--accent-danger)]">{{ formErrors.receiver_name }}</span>
              </label>

              <label class="block w-full min-w-0">
                <span class="w-full text-sm font-semibold text-on-surface">Số điện thoại</span>
                <input
                  v-model="form.receiver_phone"
                  class="mt-2 h-12 w-full rounded-[var(--radius-watch-md)] border border-border bg-background px-4 text-on-surface outline-none focus:border-[var(--accent-primary)]"
                  type="tel"
                  autocomplete="tel"
                />
                <span v-if="formErrors.receiver_phone" class="mt-1 block w-full text-xs text-[var(--accent-danger)]">{{ formErrors.receiver_phone }}</span>
              </label>
            </div>

            <label class="mt-5 block w-full min-w-0">
              <span class="w-full text-sm font-semibold text-on-surface">Địa chỉ giao hàng</span>
              <textarea
                v-model="form.shipping_address"
                class="mt-2 min-h-28 w-full rounded-[var(--radius-watch-md)] border border-border bg-background px-4 py-3 text-on-surface outline-none focus:border-[var(--accent-primary)]"
                autocomplete="street-address"
              ></textarea>
              <span v-if="formErrors.shipping_address" class="mt-1 block w-full text-xs text-[var(--accent-danger)]">{{ formErrors.shipping_address }}</span>
            </label>

            <label class="mt-5 block w-full min-w-0">
              <span class="w-full text-sm font-semibold text-on-surface">Ghi chú giao hàng</span>
              <textarea
                v-model="form.shipping_note"
                class="mt-2 min-h-24 w-full rounded-[var(--radius-watch-md)] border border-border bg-background px-4 py-3 text-on-surface outline-none focus:border-[var(--accent-primary)]"
                placeholder="Tùy chọn"
              ></textarea>
            </label>

            <fieldset class="mt-8 w-full min-w-0">
              <legend class="w-full font-display text-3xl font-semibold text-primary">Khu vực giao hàng</legend>
              <p v-if="isLoadingShippingZones" class="mt-4 w-full rounded-[var(--radius-watch-md)] border border-border bg-background p-4 text-sm text-on-surface-variant">
                Đang tải khu vực giao hàng...
              </p>
              <div class="mt-4 grid w-full min-w-0 grid-cols-1 gap-3 md:grid-cols-3">
                <label
                  v-for="zone in shippingZones"
                  :key="zone.id"
                  class="flex min-h-28 w-full min-w-0 cursor-pointer flex-col justify-between rounded-[var(--radius-watch-md)] border p-4 transition-colors"
                  :class="Number(form.shipping_zone_id) === zone.id ? 'border-[var(--accent-primary)] bg-[rgb(161_98_7/0.08)]' : 'border-border bg-background hover:border-[var(--accent-primary)]'"
                >
                  <input v-model.number="form.shipping_zone_id" class="sr-only" type="radio" :value="zone.id" />
                  <span class="w-full font-semibold text-primary">{{ zone.name }}</span>
                  <span class="watch-price-accent mt-3 w-full text-xl">{{ formatCurrency(zone.fee) }}</span>
                </label>
              </div>
              <p v-if="formErrors.shipping_zone_id" class="mt-3 w-full text-xs text-[var(--accent-danger)]">{{ formErrors.shipping_zone_id }}</p>
            </fieldset>
          </form>

          <section v-if="invalidItems.length" class="w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-4 text-sm text-[var(--accent-danger)]">
            Có {{ invalidItems.length }} sản phẩm không còn đủ tồn kho hoặc đã ngừng bán. Vui lòng quay lại giỏ hàng để điều chỉnh.
          </section>
        </section>

        <aside class="w-full min-w-0 lg:sticky lg:top-28">
          <div class="rounded-[var(--radius-watch-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-watch-soft)]">
            <h2 class="w-full font-display text-3xl font-semibold text-primary">Đơn hàng</h2>

            <div class="mt-6 w-full min-w-0 divide-y divide-border border-y border-border">
              <article v-for="item in cartStore.items" :key="item.id" class="flex w-full min-w-0 gap-3 py-4">
                <img :src="itemImage(item)" :alt="item.product_variant?.product?.name" class="h-20 w-20 shrink-0 rounded-[var(--radius-watch-md)] object-cover" />
                <div class="w-full min-w-0">
                  <p class="line-clamp-2 w-full font-semibold text-on-surface">{{ item.product_variant?.product?.name }}</p>
                  <p class="mt-1 w-full text-xs text-on-surface-variant">
                    {{ item.product_variant?.strap_color }} / {{ item.product_variant?.dial_color }} / {{ item.product_variant?.diameter_mm }}mm / {{ movementLabel(item.product_variant?.movement_type) }}
                  </p>
                  <div class="mt-2 flex w-full justify-between gap-3 text-sm">
                    <span class="text-on-surface-variant">x{{ item.quantity }}</span>
                    <span class="font-semibold text-on-surface">{{ formatCurrency(lineTotal(item)) }}</span>
                  </div>
                </div>
              </article>
            </div>

            <div class="mt-6 space-y-4">
              <div class="flex w-full justify-between gap-4 text-sm text-on-surface-variant">
                <span>Tạm tính</span>
                <span class="shrink-0 font-semibold text-on-surface">{{ formatCurrency(subtotal) }}</span>
              </div>
              <div class="flex w-full justify-between gap-4 text-sm text-on-surface-variant">
                <span>Phí giao hàng · {{ selectedZone?.name ?? 'Chưa chọn' }}</span>
                <span class="shrink-0 font-semibold text-on-surface">{{ formatCurrency(shippingFee) }}</span>
              </div>
              <div class="h-px w-full bg-border"></div>
              <div class="flex w-full items-center justify-between gap-4">
                <span class="font-display text-2xl font-semibold text-primary">Tổng cộng</span>
                <span class="watch-price-accent shrink-0 text-2xl">{{ formatCurrency(totalAmount) }}</span>
              </div>
            </div>

            <button
              type="button"
              class="mt-6 min-h-12 w-full cursor-pointer rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-bold uppercase tracking-[0.14em] text-on-primary transition-colors hover:bg-[var(--accent-primary-hover)] disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="!canSubmit"
              @click="proceedToPayment"
            >
              Thanh toán qua VNPay
            </button>

            <p class="mt-4 w-full text-center text-xs leading-5 text-on-surface-variant">
              VNPay Return chỉ hiển thị kết quả. Trạng thái đơn hàng được xử lý bởi IPN server-to-server.
            </p>
          </div>
        </aside>
      </div>

      <section v-else class="mt-12 flex min-h-[300px] w-full min-w-0 flex-col items-center justify-center rounded-[var(--radius-watch-lg)] border border-border bg-surface p-8 text-center">
        <span class="material-symbols-outlined text-5xl text-[var(--accent-primary)]">shopping_bag</span>
        <h2 class="mt-4 w-full font-display text-4xl font-semibold text-primary">Giỏ hàng đang trống</h2>
        <p class="mt-3 w-full max-w-lg text-on-surface-variant">Thêm đồng hồ vào giỏ trước khi checkout.</p>
        <RouterLink to="/products" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-bold uppercase tracking-[0.14em] text-on-primary">
          Khám phá sản phẩm
        </RouterLink>
      </section>
    </template>
  </main>
</template>
