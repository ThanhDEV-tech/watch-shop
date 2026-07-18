<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { gsap } from 'gsap'
import { formatCurrency } from '../utils/formatCurrency'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const cardRef = ref(null)
const quickActionRef = ref(null)
const imageIndex = ref(0)
let hoverTimeline
let reduceMotionQuery

const productHref = computed(() => `/products/${props.product.slug}`)
const brandName = computed(() => props.product.brand?.name ?? 'Watchora')
const imageCandidates = computed(() => {
  const galleryImage = (props.product.product_images ?? props.product.images ?? [])
    .map((image) => image.image_path ?? image.url ?? image.path)
    .filter(Boolean)

  return [...new Set([
    props.product.thumbnail,
    ...galleryImage,
    ...(props.product.variants ?? []).map((variant) => variant.image),
    '/vite.svg',
  ].filter(Boolean))]
})
const imageSrc = computed(() => imageCandidates.value[imageIndex.value] ?? '/vite.svg')
const priceLabel = computed(() => (
  props.product.min_final_price
    ? `từ ${formatCurrency(props.product.min_final_price)}`
    : 'Liên hệ'
))

const useNextImage = () => {
  if (imageIndex.value < imageCandidates.value.length - 1) {
    imageIndex.value += 1
  }
}

const playHover = () => {
  if (!hoverTimeline || reduceMotionQuery?.matches) return

  hoverTimeline.play()
}

const reverseHover = () => {
  if (!hoverTimeline || reduceMotionQuery?.matches) return

  hoverTimeline.reverse()
}

onMounted(() => {
  reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')

  if (reduceMotionQuery.matches || !cardRef.value || !quickActionRef.value) {
    return
  }

  gsap.set(quickActionRef.value, { autoAlpha: 0, y: 8 })

  hoverTimeline = gsap.timeline({
    paused: true,
    defaults: { duration: 0.18, ease: 'power2.out', overwrite: 'auto' },
  })

  hoverTimeline
    .to(cardRef.value, {
      y: -5,
      scale: 1.015,
      boxShadow: '0 24px 70px rgb(12 10 9 / 0.13)',
    })
    .to(quickActionRef.value, { autoAlpha: 1, y: 0 }, '<0.05')
})

onBeforeUnmount(() => {
  hoverTimeline?.kill()
})

watch(() => props.product.slug, () => {
  imageIndex.value = 0
})
</script>

<template>
  <article
    ref="cardRef"
    class="watch-product-card group flex h-full w-full min-w-0 flex-col overflow-hidden rounded-[var(--radius-watch-lg)] border border-border bg-surface shadow-[var(--shadow-watch-soft)] outline-none transition-colors hover:border-primary/30 focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
    @mouseenter="playHover"
    @mouseleave="reverseHover"
  >
    <RouterLink
      :to="productHref"
      class="flex h-full w-full min-w-0 flex-col outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      @focus="playHover"
      @blur="reverseHover"
    >
      <div class="relative aspect-[4/5] w-full overflow-hidden bg-surface-container-low">
        <img
          :src="imageSrc"
          :alt="product.name"
          class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
          loading="lazy"
          @error="useNextImage"
        />
        <span
          v-if="product.is_out_of_stock"
          class="absolute left-3 top-3 rounded-[var(--radius-watch-sm)] border border-white/55 bg-[rgb(12_10_9/0.78)] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-white"
        >
          Hết hàng
        </span>
      </div>

      <div class="flex w-full min-w-0 flex-1 flex-col gap-3 p-4">
        <div class="w-full min-w-0">
        <p class="watch-accent-text w-full truncate text-xs font-bold uppercase tracking-[0.16em]">
            {{ brandName }}
          </p>
          <h3 class="mt-2 w-full min-w-0 text-xl font-semibold leading-tight text-on-surface">
            {{ product.name }}
          </h3>
        </div>

        <div class="mt-auto flex w-full min-w-0 items-end justify-between gap-3">
          <p class="watch-price-accent w-full min-w-0 font-body text-base">
            {{ priceLabel }}
          </p>
          <span
            ref="quickActionRef"
            class="watch-accent-strong shrink-0 rounded-[var(--radius-watch-sm)] border border-[rgb(161_98_7/0.42)] bg-[rgb(161_98_7/0.07)] px-3 py-2 text-xs font-bold uppercase tracking-[0.12em]"
          >
            Xem chi tiết
          </span>
        </div>
      </div>
    </RouterLink>
  </article>
</template>
