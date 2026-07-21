<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  content: {
    type: Object,
    default: null,
  },
  selectedVariant: {
    type: Object,
    default: null,
  },
  priceDisplay: {
    type: String,
    default: '',
  },
  fallbackImage: {
    type: String,
    default: '',
  },
})

const headingId = `product-campaign-hero-${useId()}`

const readString = (value) => String(value ?? '').trim()
const stripHtml = (value) => readString(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()

const shortText = (value, limit = 190) => {
  const text = stripHtml(value)

  if (text.length <= limit) return text

  return `${text.slice(0, limit - 3).trim()}...`
}

const desktopImage = computed(() => (
  readString(props.content?.desktopImage)
  || readString(props.content?.mobileImage)
  || readString(props.fallbackImage)
  || readString(props.product.thumbnail)
))

const mobileImage = computed(() => (
  readString(props.content?.mobileImage)
  || desktopImage.value
))

const imageAlt = computed(() => (
  readString(props.content?.alt)
  || readString(props.product.name)
  || 'Watch product campaign image'
))

const objectPosition = computed(() => readString(props.content?.focalPoint) || 'center')
const eyebrow = computed(() => readString(props.content?.eyebrow) || readString(props.product.brand?.name) || 'Watchora')
const heading = computed(() => readString(props.content?.heading) || readString(props.product.name))
const body = computed(() => (
  readString(props.content?.body)
  || shortText(props.product.short_description)
  || shortText(props.product.description)
))
const ctaLabel = computed(() => readString(props.content?.ctaLabel) || 'Configure yours')

const handleCommerceScroll = (event) => {
  const target = document.getElementById('product-commerce')

  if (!target) return

  event.preventDefault()
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  target.scrollIntoView({
    behavior: prefersReducedMotion ? 'auto' : 'smooth',
    block: 'start',
  })
}
</script>

<template>
  <section
    class="relative min-h-[92svh] w-full min-w-0 overflow-hidden bg-[var(--watch-color-ink-950)] text-white md:min-h-screen"
    :aria-labelledby="heading ? headingId : undefined"
  >
    <picture v-if="desktopImage">
      <source
        v-if="mobileImage && mobileImage !== desktopImage"
        media="(max-width: 767px)"
        :srcset="mobileImage"
      >
      <img
        :src="desktopImage"
        :alt="imageAlt"
        class="absolute inset-0 h-full w-full object-cover"
        :style="{ objectPosition }"
        decoding="async"
        fetchpriority="high"
      >
    </picture>

    <div class="absolute inset-0 bg-black/34 md:bg-black/44"></div>
    <div class="absolute inset-y-0 left-0 hidden w-[62%] bg-black/24 md:block"></div>
    <div class="absolute inset-x-0 bottom-0 h-28 bg-black/28"></div>

    <div class="relative z-10 flex min-h-[92svh] w-full min-w-0 items-end px-6 pb-11 pt-32 md:min-h-screen md:px-20 md:pb-18 lg:px-[100px] lg:pb-20">
      <div class="w-full min-w-0 max-w-3xl">
        <p
          v-if="eyebrow"
          class="w-full font-body text-[10px] font-medium uppercase tracking-[0.36em] text-white/72"
        >
          {{ eyebrow }}
        </p>

        <h1
          v-if="heading"
          :id="headingId"
          class="mt-5 w-full max-w-[11ch] break-words font-display text-[clamp(4.25rem,14vw,8.5rem)] font-normal leading-[0.88] tracking-normal text-white md:text-[clamp(5.75rem,8.4vw,10.25rem)]"
        >
          {{ heading }}
        </h1>

        <p
          v-if="body"
          class="mt-6 w-full max-w-[34rem] font-body text-[15px] font-light leading-8 text-white/78 md:text-[17px] md:leading-9"
        >
          {{ body }}
        </p>

        <div class="mt-8 flex w-full min-w-0 flex-col gap-4 border-t border-white/18 pt-5 sm:flex-row sm:items-center sm:gap-7 sm:border-t-0 sm:pt-0">
          <p
            v-if="priceDisplay"
            class="w-full font-body text-[11px] font-medium uppercase tracking-[0.24em] text-white/68 sm:w-auto"
          >
            {{ priceDisplay }}
          </p>
          <a
            href="#product-commerce"
            class="inline-flex min-h-10 max-w-full items-center justify-center border border-white/62 px-5 font-body text-[10px] font-medium uppercase tracking-[0.28em] text-white transition-opacity hover:opacity-78 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 motion-reduce:transition-none"
            @click="handleCommerceScroll"
          >
            {{ ctaLabel }}
          </a>
        </div>
      </div>
    </div>
  </section>
</template>
