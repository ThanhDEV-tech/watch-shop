<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { gsap } from 'gsap'
import { formatCurrency } from '../utils/formatCurrency'
import { motionTokens } from '../utils/motion'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  variant: {
    type: String,
    default: 'default',
  },
})

const cardRef = ref(null)
const quickActionRef = ref(null)
const imageIndex = ref(0)
let hoverTimeline
let reduceMotionQuery
let hoverTarget

const productHref = computed(() => `/products/${props.product.slug}`)
const brandName = computed(() => props.product.brand?.name ?? 'Watchora')
const imageCandidates = computed(() => {
  const gallery = [
    ...(props.product.product_images ?? []),
    ...(props.product.images ?? []),
  ]
  const primaryImage = gallery.find((image) => image?.is_primary)
  const normalizedGallery = gallery
    .map((image) => image?.image_path ?? image?.url ?? image?.path)
    .filter(Boolean)

  return [...new Set([
    primaryImage?.image_path ?? primaryImage?.url ?? primaryImage?.path,
    props.product.thumbnail,
    props.product.thumbnail_url,
    props.product.image_url,
    props.product.image,
    ...normalizedGallery,
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
  if (props.variant === 'editorial') return

  reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  hoverTarget = cardRef.value?.querySelector('a')

  if (reduceMotionQuery.matches || !hoverTarget || !quickActionRef.value) {
    return
  }

  gsap.set(quickActionRef.value, { autoAlpha: 0, y: 8 })

  hoverTimeline = gsap.timeline({
    paused: true,
    defaults: { duration: motionTokens.durationMicro, ease: motionTokens.easeReveal, overwrite: 'auto' },
    onReverseComplete: () => {
      gsap.set(hoverTarget, { clearProps: 'transform,boxShadow' })
    },
  })
  hoverTimeline
    .to(hoverTarget, {
      y: -motionTokens.cardLift,
      scale: motionTokens.cardScale,
      boxShadow: '0 16px 44px rgb(12 10 9 / 0.09)',
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
    class="watch-product-card group flex h-full w-full min-w-0 flex-col overflow-hidden border outline-none transition-colors focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background motion-reduce:transition-none motion-reduce:transform-none"
    :class="variant === 'editorial'
      ? 'rounded-none border-transparent bg-transparent shadow-none'
      : 'rounded-[var(--radius-watch-lg)] border-border bg-surface shadow-[var(--shadow-watch-soft)] hover:border-primary/30'"
    @mouseenter="playHover"
    @mouseleave="reverseHover"
  >
    <RouterLink
      :to="productHref"
      class="flex h-full w-full min-w-0 flex-col outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background motion-reduce:transform-none"
      @focus="playHover"
      @blur="reverseHover"
    >
      <div
        class="relative w-full overflow-hidden"
        :class="variant === 'editorial' ? 'aspect-[3/4] bg-[#fbfaf7]' : 'aspect-[4/5] bg-surface-container-low'"
      >
        <img
          :src="imageSrc"
          :alt="product.name"
          :style="{ '--watch-card-image-scale': motionTokens.imageScale }"
          class="h-full w-full transition-[opacity,transform] duration-500 motion-reduce:transition-none motion-reduce:transform-none"
          :class="variant === 'editorial' ? 'object-contain p-1 opacity-95 group-hover:scale-[1.025] group-hover:opacity-100 motion-reduce:group-hover:scale-100' : 'object-cover group-hover:scale-[var(--watch-card-image-scale)] motion-reduce:group-hover:scale-100'"
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

      <div
        class="flex w-full min-w-0 flex-1 flex-col"
        :class="variant === 'editorial' ? 'gap-3 px-0 pb-0 pt-4' : 'gap-3 p-4'"
      >
        <div class="w-full min-w-0">
          <p
            class="w-full truncate text-xs uppercase"
            :class="variant === 'editorial' ? 'font-medium tracking-[0.24em] text-primary/42' : 'font-bold watch-accent-text tracking-[0.16em]'"
          >
            {{ brandName }}
          </p>
          <h3
            class="w-full min-w-0 leading-tight text-on-surface"
            :class="variant === 'editorial' ? 'mt-2 font-display text-[clamp(1.65rem,2.4vw,2.35rem)] font-semibold leading-[1.02] text-primary' : 'mt-2 text-xl font-semibold'"
          >
            {{ product.name }}
          </h3>
        </div>

        <div
          class="mt-auto flex w-full min-w-0 items-end justify-between gap-3"
          :class="variant === 'editorial' ? 'pt-2' : ''"
        >
          <p
            class="w-full min-w-0 font-body text-base"
            :class="variant === 'editorial' ? 'font-medium text-primary/62' : 'watch-price-accent'"
          >
            {{ priceLabel }}
          </p>
          <span
            ref="quickActionRef"
            class="shrink-0 border px-3 py-2 text-xs font-bold uppercase"
            :class="variant === 'editorial'
              ? 'border-primary/12 bg-transparent tracking-[0.16em] text-primary/70 opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-focus-within:opacity-100 motion-reduce:transition-none'
              : 'watch-accent-strong rounded-[var(--radius-watch-sm)] border-[rgb(161_98_7/0.42)] bg-[rgb(161_98_7/0.07)] tracking-[0.12em]'"
          >
            Xem chi tiết
          </span>
        </div>
      </div>
    </RouterLink>
  </article>
</template>
