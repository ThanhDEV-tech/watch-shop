<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { gsap } from 'gsap'
import EditorialSignatureMoment from '../components/product-detail/EditorialSignatureMoment.vue'
import EditorialStorySection from '../components/product-detail/EditorialStorySection.vue'
import ProductCampaignHero from '../components/product-detail/ProductCampaignHero.vue'
import ProductCollectionSection from '../components/product-detail/ProductCollectionSection.vue'
import ProductGallery from '../components/product-detail/ProductGallery.vue'
import ProductQuoteSection from '../components/product-detail/ProductQuoteSection.vue'
import ProductSpecifications from '../components/product-detail/ProductSpecifications.vue'
import ProductPurchasePanel from '../components/product-detail/ProductPurchasePanel.vue'
import { getProductBySlug, getProductReviews, getProducts, submitProductReview } from '../api/axios'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'
import { resolveProductEditorial } from '../utils/productEditorial'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const product = ref(null)
const relatedProducts = ref([])
const selectedImage = ref('')
const isLoading = ref(false)
const isAdding = ref(false)
const isLoadingReviews = ref(false)
const isSubmittingReview = ref(false)
const errorMessage = ref('')
const cartMessage = ref('')
const cartError = ref('')
const reviewMessage = ref('')
const reviewError = ref('')
const quantity = ref(1)

const selected = reactive({
  strap_color: '',
  dial_color: '',
  diameter_mm: '',
  movement_type: '',
})

const reviews = ref([])
const reviewMeta = reactive({
  can_review: false,
  existing_review: null,
  rating_avg: 0,
  reviews_count: 0,
})
const reviewForm = reactive({
  rating: 5,
  comment: '',
})

const colorMap = {
  'Đen': '#111111',
  'Bạc': '#e5e5e5',
  'Vàng gold': '#d4af37',
  'Vàng rose': '#b76e79',
  'Trắng': '#f8f7f2',
  'Xanh navy': '#1f3a5f',
  'Nâu': '#6b3f2a',
}

const variants = computed(() => product.value?.variants ?? [])
const activeVariants = computed(() => variants.value.filter((variant) => variant.is_active))

const editorialContent = computed(() => {
  const routeSlug = String(route.params.slug ?? '').trim()
  const editorialProduct = product.value?.slug === routeSlug ? product.value : routeSlug

  return resolveProductEditorial(editorialProduct)
})

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

const readableValue = (value) => String(value ?? '').trim()
const hasText = (value) => Boolean(readableValue(value))
const stripHtml = (value) => readableValue(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()

const technicalIntro = computed(() => {
  const content = stripHtml(product.value?.content)
  const description = stripHtml(product.value?.description)

  if (content && content !== description) {
    return content.length > 180 ? `${content.slice(0, 177).trim()}...` : content
  }

  return ''
})

const warrantyValue = computed(() => {
  const months = product.value?.warranty_months
  const note = readableValue(product.value?.warranty_note)

  return [
    months ? `${months} tháng` : '',
    note,
  ].filter(hasText).join(' · ')
})

const specs = computed(() => [
  {
    key: 'case',
    label: 'Case material',
    value: product.value?.case_material,
  },
  {
    key: 'strap-material',
    label: 'Strap',
    value: product.value?.strap_material,
  },
  {
    key: 'glass',
    label: 'Glass',
    value: product.value?.glass_material,
  },
  {
    key: 'diameter',
    label: 'Diameter',
    value: selectedVariant.value?.diameter_mm ? `${selectedVariant.value.diameter_mm}mm` : '',
  },
  {
    key: 'movement',
    label: 'Movement',
    value: selectedVariant.value?.movement_type ? movementLabel(selectedVariant.value.movement_type) : '',
  },
  {
    key: 'water',
    label: 'Resistance',
    value: product.value?.water_resistance,
  },
  {
    key: 'strap-color',
    label: 'Strap color',
    value: selectedVariant.value?.strap_color,
  },
  {
    key: 'dial-color',
    label: 'Dial color',
    value: selectedVariant.value?.dial_color,
  },
  {
    key: 'warranty',
    label: 'Warranty',
    value: warrantyValue.value,
  },
  {
    key: 'sku',
    label: 'SKU',
    value: selectedVariant.value?.sku,
  },
].filter((item) => hasText(item.value)))

const options = computed(() => ({
  strap_color: [...new Set(activeVariants.value.map((variant) => variant.strap_color).filter(Boolean))],
  dial_color: [...new Set(activeVariants.value.map((variant) => variant.dial_color).filter(Boolean))],
  diameter_mm: [...new Set(activeVariants.value.map((variant) => Number(variant.diameter_mm)).filter(Boolean))].sort((a, b) => a - b),
  movement_type: [...new Set(activeVariants.value.map((variant) => variant.movement_type).filter(Boolean))],
}))

const displayRating = computed(() => Number(reviewMeta.rating_avg || product.value?.rating_avg || 0).toFixed(1))
const reviewCountLabel = computed(() => `${Number(reviewMeta.reviews_count || reviews.value.length || 0)} đánh giá`)
const reviewQuotes = computed(() => {
  if (reviews.value.length) {
    return reviews.value.slice(0, 2).map((review) => ({
      key: review.id,
      quote: review.comment || `${review.rating} / 5`,
      author: review.user?.name ?? 'Khách hàng Watchora',
    }))
  }

  return [
    {
      key: 'rating',
      quote: `${displayRating.value} / 5`,
      author: reviewCountLabel.value,
    },
  ]
})

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
    gsap.fromTo(event.currentTarget, { scale: 0.96 }, { scale: 1, duration: 0.24, ease: 'back.out(1.6)' })
  }

  await nextTick()
}

const changeImage = (image) => {
  if (!image || image === selectedImage.value) return

  selectedImage.value = image
}

const useNextGalleryImage = () => {
  const currentIndex = galleryImages.value.indexOf(selectedImage.value)
  const nextImage = galleryImages.value.slice(currentIndex + 1).find(Boolean)

  if (nextImage) {
    selectedImage.value = nextImage
  }
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
    await fetchProductReviews()
    await fetchRelatedProducts()
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải chi tiết sản phẩm.'
  } finally {
    isLoading.value = false
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
    .slice(0, 3)
}

const fetchProductReviews = async () => {
  if (!product.value?.id) return

  isLoadingReviews.value = true
  reviewError.value = ''

  try {
    const response = await getProductReviews(product.value.id, { per_page: 8 })
    const data = response.data.data
    reviews.value = data?.items ?? []
    reviewMeta.can_review = Boolean(data?.can_review)
    reviewMeta.existing_review = data?.existing_review ?? null
    reviewMeta.rating_avg = Number(data?.summary?.rating_avg ?? product.value.rating_avg ?? 0)
    reviewMeta.reviews_count = Number(data?.summary?.reviews_count ?? reviews.value.length)

    if (reviewMeta.existing_review) {
      reviewForm.rating = Number(reviewMeta.existing_review.rating ?? 5)
      reviewForm.comment = reviewMeta.existing_review.comment ?? ''
    } else {
      reviewForm.rating = 5
      reviewForm.comment = ''
    }
  } catch (error) {
    reviewError.value = error.response?.data?.message ?? 'Không thể tải đánh giá sản phẩm.'
  } finally {
    isLoadingReviews.value = false
  }
}

const submitReview = async () => {
  if (!product.value?.id || !reviewMeta.can_review || isSubmittingReview.value) return

  reviewMessage.value = ''
  reviewError.value = ''
  isSubmittingReview.value = true

  try {
    await submitProductReview(product.value.id, {
      rating: Number(reviewForm.rating),
      comment: reviewForm.comment,
    })
    reviewMessage.value = reviewMeta.existing_review
      ? 'Đã cập nhật đánh giá của bạn.'
      : 'Cảm ơn bạn đã đánh giá sản phẩm.'
    await fetchProductReviews()
  } catch (error) {
    const errors = error.response?.data?.errors
    reviewError.value = errors
      ? Object.values(errors).flat().join(' ')
      : error.response?.data?.message ?? 'Không thể gửi đánh giá lúc này.'
  } finally {
    isSubmittingReview.value = false
  }
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

watch(() => route.params.slug, () => {
  fetchProduct()
})

onMounted(fetchProduct)
</script>

<template>
  <main class="w-full min-w-0 bg-background text-on-surface">
    <section v-if="isLoading" class="mx-auto w-full max-w-container-max px-margin-mobile py-20 md:px-gutter">
      <div class="border border-outline-variant bg-surface p-10 text-center text-on-surface-variant">
        Đang tải chi tiết sản phẩm...
      </div>
    </section>

    <section v-else-if="errorMessage" class="mx-auto w-full max-w-container-max px-margin-mobile py-20 md:px-gutter">
      <div class="border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-8 text-[var(--accent-danger)]">
        {{ errorMessage }}
      </div>
    </section>

    <template v-else-if="product">
      <ProductCampaignHero
        :product="product"
        :content="editorialContent.hero"
        :selected-variant="selectedVariant"
        :price-display="displayPrice"
        :fallback-image="selectedImage || galleryImages[0]"
      />

      <section
        id="product-commerce"
        class="scroll-mt-24 bg-background px-6 py-18 md:px-20 md:py-26 lg:px-[100px]"
      >
        <div class="mx-auto mb-10 w-full max-w-[1480px] md:mb-12">
          <p class="w-full font-body text-[10px] font-medium uppercase tracking-[0.32em] text-primary/45">
            Product atelier
          </p>
          <h2 class="mt-4 w-full font-display text-[clamp(2.5rem,5vw,4.25rem)] font-normal leading-none text-primary">
            Configure your timepiece.
          </h2>
        </div>

        <div class="mx-auto grid w-full max-w-[1480px] items-start gap-12 lg:grid-cols-[minmax(0,0.62fr)_minmax(360px,0.38fr)] lg:gap-14 xl:gap-16">
          <ProductGallery
            v-model="selectedImage"
            :images="galleryImages"
            :product-name="product.name"
            @image-error="useNextGalleryImage"
            @select-image="changeImage"
          />

          <ProductPurchasePanel
            :product="product"
            :gender-label="genderLabel"
            :display-price="displayPrice"
            :options="options"
            :selected="selected"
            :selected-variant="selectedVariant"
            :stock-quantity="stockQuantity"
            :quantity="quantity"
            :quantity-max="quantityMax"
            :can-add-to-cart="canAddToCart"
            :is-adding="isAdding"
            :is-out-of-stock="isOutOfStock"
            :has-invalid-complete-selection="hasInvalidCompleteSelection"
            :cart-message="cartMessage"
            :cart-error="cartError"
            :color-map="colorMap"
            :is-option-disabled="isOptionDisabled"
            :movement-label="movementLabel"
            @select-option="selectOption"
            @add-quantity="addQuantity"
            @update:quantity="quantity = $event"
            @clamp-quantity="clampQuantity"
            @add-to-cart="addToCart"
          />
        </div>
      </section>

      <EditorialStorySection :content="editorialContent.story" />

      <EditorialSignatureMoment :content="editorialContent.signatureMoment" />

      <ProductSpecifications
        :specs="specs"
        :intro="technicalIntro"
      />

      <ProductQuoteSection
        :quotes="reviewQuotes"
        :can-review="authStore.isAuthenticated && reviewMeta.can_review"
        :rating="reviewForm.rating"
        :comment="reviewForm.comment"
        :existing-review="reviewMeta.existing_review"
        :is-submitting="isSubmittingReview"
        :message="reviewMessage"
        :error="reviewError"
        @update:rating="reviewForm.rating = $event"
        @update:comment="reviewForm.comment = $event"
        @submit-review="submitReview"
      />

      <ProductCollectionSection :products="relatedProducts" />
    </template>
  </main>
</template>
