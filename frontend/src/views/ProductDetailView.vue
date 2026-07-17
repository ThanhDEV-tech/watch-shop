<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import ProductShowcaseGrid from '../components/ProductShowcaseGrid.vue'
import { getProductBySlug, getProducts } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

gsap.registerPlugin(ScrollTrigger)

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const product = ref(null)
const relatedProducts = ref([])
const selectedImage = ref('')
const mainImageRef = ref(null)
const specsRef = ref(null)
const isLoading = ref(false)
const isAdding = ref(false)
const errorMessage = ref('')
const cartMessage = ref('')
const cartError = ref('')
const quantity = ref(1)
let specsMatchMedia

const selected = reactive({
  strap_color: '',
  dial_color: '',
  diameter_mm: '',
  movement_type: '',
})

const colorMap = {
  'Đen': '#111111',
  'Bạc': '#c7c7c7',
  'Vàng gold': '#d4af37',
  'Vàng rose': '#b76e79',
  'Trắng': '#f8f7f2',
  'Xanh navy': '#1f3a5f',
  'Nâu': '#6b3f2a',
}

const variants = computed(() => product.value?.variants ?? [])
const activeVariants = computed(() => variants.value.filter((variant) => variant.is_active))

const variantImages = computed(() => activeVariants.value
  .map((variant) => variant.image)
  .filter(Boolean))

const galleryImages = computed(() => {
  const productImages = product.value?.product_images
    ?? product.value?.images
    ?? []
  const normalizedProductImages = productImages
    .map((image) => image.image_path ?? image.url ?? image.path)
    .filter(Boolean)

  return [...new Set([
    ...normalizedProductImages,
    product.value?.thumbnail,
    ...variantImages.value,
  ].filter(Boolean))]
})

const selectedVariant = computed(() => {
  if (!isSelectionComplete.value) return null

  return activeVariants.value.find((variant) => (
    variant.strap_color === selected.strap_color
    && variant.dial_color === selected.dial_color
    && Number(variant.diameter_mm) === Number(selected.diameter_mm)
    && variant.movement_type === selected.movement_type
  )) ?? null
})

const isSelectionComplete = computed(() => (
  Boolean(selected.strap_color)
  && Boolean(selected.dial_color)
  && Boolean(selected.diameter_mm)
  && Boolean(selected.movement_type)
))

const hasInvalidCompleteSelection = computed(() => isSelectionComplete.value && !selectedVariant.value)
const stockQuantity = computed(() => Number(selectedVariant.value?.stock_quantity ?? 0))
const isOutOfStock = computed(() => Boolean(selectedVariant.value) && stockQuantity.value <= 0)
const quantityMax = computed(() => Math.max(stockQuantity.value, 1))
const canAddToCart = computed(() => (
  Boolean(selectedVariant.value)
  && !isOutOfStock.value
  && quantity.value >= 1
  && quantity.value <= stockQuantity.value
  && !isAdding.value
))

const displayPrice = computed(() => {
  const price = selectedVariant.value?.final_price
    ?? selectedVariant.value?.discount_price
    ?? selectedVariant.value?.price
    ?? product.value?.min_final_price

  return price ? formatCurrency(price) : 'Liên hệ'
})

const movementLabel = (value) => ({
  quartz: 'Quartz',
  automatic: 'Automatic',
}[value] ?? value)

const genderLabel = computed(() => ({
  men: 'Nam',
  women: 'Nữ',
  unisex: 'Unisex',
}[product.value?.gender_target] ?? product.value?.gender_target))

const specs = computed(() => [
  ['Chất liệu vỏ', product.value?.case_material],
  ['Chất liệu dây', product.value?.strap_material],
  ['Mặt kính', product.value?.glass_material],
  ['Kháng nước', product.value?.water_resistance],
  ['Bảo hành', product.value?.warranty_months ? `${product.value.warranty_months} tháng` : null],
  ['Ghi chú bảo hành', product.value?.warranty_note],
].filter(([, value]) => Boolean(value)))

const options = computed(() => ({
  strap_color: [...new Set(activeVariants.value.map((variant) => variant.strap_color))],
  dial_color: [...new Set(activeVariants.value.map((variant) => variant.dial_color))],
  diameter_mm: [...new Set(activeVariants.value.map((variant) => Number(variant.diameter_mm)))].sort((a, b) => a - b),
  movement_type: [...new Set(activeVariants.value.map((variant) => variant.movement_type))],
}))

const partialMatch = (candidate) => activeVariants.value.some((variant) => (
  (!candidate.strap_color || variant.strap_color === candidate.strap_color)
  && (!candidate.dial_color || variant.dial_color === candidate.dial_color)
  && (!candidate.diameter_mm || Number(variant.diameter_mm) === Number(candidate.diameter_mm))
  && (!candidate.movement_type || variant.movement_type === candidate.movement_type)
))

const isOptionDisabled = (field, value) => {
  const candidate = {
    strap_color: selected.strap_color,
    dial_color: selected.dial_color,
    diameter_mm: selected.diameter_mm,
    movement_type: selected.movement_type,
    [field]: value,
  }

  return !partialMatch(candidate)
}

const selectOption = async (field, value, event) => {
  if (isOptionDisabled(field, value)) return

  selected[field] = selected[field] === value ? '' : value
  cartMessage.value = ''
  cartError.value = ''

  if (event?.currentTarget && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    gsap.fromTo(event.currentTarget, { scale: 0.94 }, { scale: 1, duration: 0.24, ease: 'back.out(1.8)' })
  }

  await nextTick()
}

const changeImage = (image) => {
  if (!image || image === selectedImage.value) return

  selectedImage.value = image
}

const clampQuantity = () => {
  quantity.value = Math.min(Math.max(Number(quantity.value) || 1, 1), quantityMax.value)
}

const addQuantity = (amount) => {
  quantity.value = Number(quantity.value) + amount
  clampQuantity()
}

const fetchProduct = async () => {
  isLoading.value = true
  errorMessage.value = ''
  product.value = null

  try {
    const response = await getProductBySlug(route.params.slug)
    const found = response.data.data

    if (!found) {
      errorMessage.value = 'Không tìm thấy sản phẩm.'
      return
    }

    product.value = found
    selectedImage.value = galleryImages.value[0] ?? found.thumbnail ?? ''
    preselectFirstAvailableVariant()
    await fetchRelatedProducts()
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải chi tiết sản phẩm.'
  } finally {
    isLoading.value = false
    await nextTick()
    setupSpecsReveal()
  }
}

const fetchRelatedProducts = async () => {
  if (!product.value) return

  const params = product.value.category?.slug
    ? { category: product.value.category.slug, per_page: 8 }
    : { brand: product.value.brand?.slug, per_page: 8 }

  const response = await getProducts(params)
  relatedProducts.value = (response.data.data?.items ?? [])
    .filter((item) => item.slug !== product.value.slug)
    .slice(0, 4)
}

const preselectFirstAvailableVariant = () => {
  const purchasable = activeVariants.value.find((variant) => Number(variant.stock_quantity) > 0)
  const fallback = activeVariants.value[0]
  const variant = purchasable ?? fallback

  selected.strap_color = variant?.strap_color ?? ''
  selected.dial_color = variant?.dial_color ?? ''
  selected.diameter_mm = variant?.diameter_mm ?? ''
  selected.movement_type = variant?.movement_type ?? ''
  quantity.value = 1
}

const setupSpecsReveal = () => {
  specsMatchMedia?.revert()

  if (!specsRef.value) return

  specsMatchMedia = gsap.matchMedia()

  specsMatchMedia.add('(prefers-reduced-motion: reduce)', () => {
    gsap.set(specsRef.value.querySelectorAll('.watch-spec-row'), { clearProps: 'all' })
  })

  specsMatchMedia.add('(prefers-reduced-motion: no-preference)', () => {
    const rows = specsRef.value.querySelectorAll('.watch-spec-row')
    gsap.set(rows, { autoAlpha: 0, y: 18 })

    const triggers = ScrollTrigger.batch(rows, {
      start: 'top 88%',
      once: true,
      batchMax: 6,
      interval: 0.08,
      onEnter: (batch) => {
        gsap.to(batch, {
          autoAlpha: 1,
          y: 0,
          duration: 0.45,
          ease: 'power2.out',
          stagger: 0.06,
          overwrite: true,
        })
      },
    })

    ScrollTrigger.refresh()

    return () => triggers.forEach((trigger) => trigger.kill())
  })
}

const addToCart = async () => {
  cartMessage.value = ''
  cartError.value = ''

  if (!authStore.isAuthenticated) {
    await router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  if (!canAddToCart.value) return

  isAdding.value = true

  try {
    await cartStore.addItem(selectedVariant.value.id, quantity.value)
    cartMessage.value = 'Đã thêm sản phẩm vào giỏ hàng.'
  } catch (error) {
    cartError.value = error.response?.data?.message ?? cartStore.error ?? 'Không thể thêm vào giỏ hàng.'
  } finally {
    isAdding.value = false
  }
}

watch(selectedVariant, (variant) => {
  if (variant?.image) {
    selectedImage.value = variant.image
  }

  clampQuantity()
})

watch(selectedImage, async () => {
  await nextTick()

  if (mainImageRef.value && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    gsap.fromTo(mainImageRef.value, { autoAlpha: 0.35 }, { autoAlpha: 1, duration: 0.32, ease: 'power1.out' })
  }
})

watch(() => route.params.slug, () => {
  fetchProduct()
})

onMounted(fetchProduct)

onBeforeUnmount(() => {
  specsMatchMedia?.revert()
})
</script>

<template>
  <main class="w-full min-w-0 bg-background text-on-surface">
    <section v-if="isLoading" class="mx-auto w-full max-w-container-max px-margin-mobile py-20 md:px-gutter">
      <div class="rounded-[var(--radius-watch-lg)] border border-border bg-surface p-10 text-center text-on-surface-variant">
        Đang tải chi tiết sản phẩm...
      </div>
    </section>

    <section v-else-if="errorMessage" class="mx-auto w-full max-w-container-max px-margin-mobile py-20 md:px-gutter">
      <div class="rounded-[var(--radius-watch-lg)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-8 text-[var(--accent-danger)]">
        {{ errorMessage }}
      </div>
    </section>

    <template v-else-if="product">
      <section class="mx-auto grid w-full max-w-container-max gap-10 px-margin-mobile py-12 md:px-gutter lg:grid-cols-[1.05fr_0.95fr]">
        <div class="w-full min-w-0">
          <div class="aspect-[4/5] w-full overflow-hidden rounded-[var(--radius-watch-lg)] border border-border bg-surface shadow-[var(--shadow-watch-soft)]">
            <img
              ref="mainImageRef"
              :src="selectedImage"
              :alt="product.name"
              class="h-full w-full object-cover"
            />
          </div>

          <div class="mt-4 grid w-full min-w-0 grid-cols-4 gap-3 sm:grid-cols-6">
            <button
              v-for="image in galleryImages"
              :key="image"
              type="button"
              class="aspect-square w-full overflow-hidden rounded-[var(--radius-watch-md)] border bg-surface transition-all hover:border-[var(--accent-primary)]"
              :class="image === selectedImage ? 'border-[var(--accent-primary)] ring-2 ring-[rgb(161_98_7/0.22)]' : 'border-border'"
              @click="changeImage(image)"
            >
              <img :src="image" :alt="product.name" class="h-full w-full object-cover" loading="lazy" />
            </button>
          </div>
        </div>

        <div class="flex w-full min-w-0 flex-col">
          <div class="w-full min-w-0">
            <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">
              {{ product.brand?.name ?? 'Watchora' }}
            </p>
            <h1 class="mt-3 w-full font-display text-[clamp(3rem,7vw,6.5rem)] font-semibold leading-[0.88] text-primary">
              {{ product.name }}
            </h1>

            <div class="mt-5 flex w-full min-w-0 flex-wrap gap-2">
              <span class="rounded-full border border-border bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant">
                {{ product.category?.name }}
              </span>
              <span class="rounded-full border border-[rgb(161_98_7/0.34)] bg-[rgb(161_98_7/0.08)] px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-[var(--accent-primary)]">
                {{ genderLabel }}
              </span>
              <span
                v-if="isOutOfStock"
                class="rounded-full border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-[var(--accent-danger)]"
              >
                Hết hàng
              </span>
            </div>

            <p class="watch-price-accent mt-6 w-full font-body text-3xl">
              {{ displayPrice }}
            </p>

            <p class="mt-5 w-full text-base leading-8 text-on-surface-variant">
              {{ product.description }}
            </p>
          </div>

          <div class="mt-8 space-y-6">
            <div class="w-full min-w-0">
              <p class="w-full text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Màu dây</p>
              <div class="mt-3 flex w-full min-w-0 flex-wrap gap-3">
                <button
                  v-for="value in options.strap_color"
                  :key="value"
                  type="button"
                  class="flex min-h-11 cursor-pointer items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold transition-all disabled:cursor-not-allowed disabled:opacity-35"
                  :class="selected.strap_color === value ? 'border-[var(--accent-primary)] bg-[rgb(161_98_7/0.08)] text-primary' : 'border-border bg-surface text-on-surface'"
                  :disabled="isOptionDisabled('strap_color', value)"
                  @click="selectOption('strap_color', value, $event)"
                >
                  <span class="h-5 w-5 rounded-full border border-black/15" :style="{ backgroundColor: colorMap[value] ?? '#d6d3d1' }"></span>
                  {{ value }}
                </button>
              </div>
            </div>

            <div class="w-full min-w-0">
              <p class="w-full text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Màu mặt</p>
              <div class="mt-3 flex w-full min-w-0 flex-wrap gap-3">
                <button
                  v-for="value in options.dial_color"
                  :key="value"
                  type="button"
                  class="flex min-h-11 cursor-pointer items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold transition-all disabled:cursor-not-allowed disabled:opacity-35"
                  :class="selected.dial_color === value ? 'border-[var(--accent-primary)] bg-[rgb(161_98_7/0.08)] text-primary' : 'border-border bg-surface text-on-surface'"
                  :disabled="isOptionDisabled('dial_color', value)"
                  @click="selectOption('dial_color', value, $event)"
                >
                  <span class="h-5 w-5 rounded-full border border-black/15" :style="{ backgroundColor: colorMap[value] ?? '#d6d3d1' }"></span>
                  {{ value }}
                </button>
              </div>
            </div>

            <div class="w-full min-w-0">
              <p class="w-full text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Đường kính</p>
              <div class="mt-3 flex w-full min-w-0 flex-wrap gap-3">
                <button
                  v-for="value in options.diameter_mm"
                  :key="value"
                  type="button"
                  class="min-h-11 cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-all disabled:cursor-not-allowed disabled:opacity-35"
                  :class="Number(selected.diameter_mm) === Number(value) ? 'border-[var(--accent-primary)] bg-[rgb(161_98_7/0.08)] text-primary' : 'border-border bg-surface text-on-surface'"
                  :disabled="isOptionDisabled('diameter_mm', value)"
                  @click="selectOption('diameter_mm', value, $event)"
                >
                  {{ value }}mm
                </button>
              </div>
            </div>

            <div class="w-full min-w-0">
              <p class="w-full text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Loại máy</p>
              <div class="mt-3 flex w-full min-w-0 flex-wrap gap-3">
                <button
                  v-for="value in options.movement_type"
                  :key="value"
                  type="button"
                  class="min-h-11 cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-all disabled:cursor-not-allowed disabled:opacity-35"
                  :class="selected.movement_type === value ? 'border-[var(--accent-primary)] bg-[rgb(161_98_7/0.08)] text-primary' : 'border-border bg-surface text-on-surface'"
                  :disabled="isOptionDisabled('movement_type', value)"
                  @click="selectOption('movement_type', value, $event)"
                >
                  {{ movementLabel(value) }}
                </button>
              </div>
            </div>
          </div>

          <p v-if="hasInvalidCompleteSelection" class="mt-5 w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] px-4 py-3 text-sm font-semibold text-[var(--accent-danger)]">
            Tổ hợp này hiện không có sẵn.
          </p>

          <div class="mt-8 flex w-full min-w-0 flex-col gap-3 sm:flex-row">
            <div class="flex h-12 w-full min-w-0 items-center rounded-[var(--radius-watch-md)] border border-border bg-surface sm:w-40">
              <button type="button" class="h-full w-12 cursor-pointer text-xl font-semibold disabled:cursor-not-allowed disabled:opacity-40" :disabled="quantity <= 1" @click="addQuantity(-1)">−</button>
              <input
                v-model.number="quantity"
                type="number"
                min="1"
                :max="quantityMax"
                class="h-full w-full min-w-0 border-x border-border bg-transparent text-center font-semibold outline-none"
                @blur="clampQuantity"
              />
              <button type="button" class="h-full w-12 cursor-pointer text-xl font-semibold disabled:cursor-not-allowed disabled:opacity-40" :disabled="quantity >= stockQuantity" @click="addQuantity(1)">+</button>
            </div>

            <button
              type="button"
              class="min-h-12 w-full cursor-pointer rounded-[var(--radius-watch-md)] bg-primary px-6 text-sm font-bold uppercase tracking-[0.14em] text-on-primary transition-colors hover:bg-[var(--accent-primary-hover)] disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="!canAddToCart"
              @click="addToCart"
            >
              {{ isAdding ? 'Đang thêm...' : isOutOfStock ? 'Hết hàng' : 'Thêm vào giỏ hàng' }}
            </button>
          </div>

          <p class="mt-3 w-full text-sm text-on-surface-variant">
            <template v-if="selectedVariant">
              Tồn kho: {{ stockQuantity }} sản phẩm · SKU {{ selectedVariant.sku }}
            </template>
            <template v-else>
              Chọn đủ 4 tùy chọn để xem tồn kho.
            </template>
          </p>
          <p v-if="cartMessage" class="mt-3 w-full text-sm font-semibold text-[var(--accent-success)]">{{ cartMessage }}</p>
          <p v-if="cartError" class="mt-3 w-full text-sm font-semibold text-[var(--accent-danger)]">{{ cartError }}</p>
        </div>
      </section>

      <section ref="specsRef" class="mx-auto w-full max-w-container-max px-margin-mobile py-12 md:px-gutter">
        <div class="grid w-full min-w-0 gap-8 lg:grid-cols-[0.75fr_1.25fr]">
          <div class="w-full min-w-0">
            <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">Technical notes</p>
            <h2 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
              Thông số tinh gọn.
            </h2>
          </div>
          <dl class="w-full min-w-0 divide-y divide-border border-y border-border">
            <div
              v-for="[label, value] in specs"
              :key="label"
              class="watch-spec-row grid w-full min-w-0 gap-2 py-5 sm:grid-cols-[0.45fr_0.55fr]"
            >
              <dt class="w-full text-sm font-bold uppercase tracking-[0.14em] text-primary/60">{{ label }}</dt>
              <dd class="w-full text-base leading-7 text-on-surface">{{ value }}</dd>
            </div>
          </dl>
        </div>
      </section>

      <section v-if="relatedProducts.length" class="border-t border-border bg-surface">
        <div class="mx-auto w-full max-w-container-max px-margin-mobile py-16 md:px-gutter">
          <div class="flex w-full min-w-0 flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div class="w-full min-w-0">
              <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">Related</p>
              <h2 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
                Có thể bạn cũng thích.
              </h2>
            </div>
            <RouterLink to="/products" class="watch-accent-strong w-fit text-sm font-bold uppercase tracking-[0.14em]">
              Xem catalog
            </RouterLink>
          </div>
          <ProductShowcaseGrid class="mt-10" :products="relatedProducts" />
        </div>
      </section>
    </template>
  </main>
</template>
