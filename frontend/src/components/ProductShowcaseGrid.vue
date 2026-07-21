<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import ProductCard from './ProductCard.vue'
import { motionTokens } from '../utils/motion'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  variant: {
    type: String,
    default: 'default',
  },
})

gsap.registerPlugin(ScrollTrigger)

let mediaMatch
let revealSetupId = 0
const gridRef = ref(null)

const clearReveal = () => {
  mediaMatch?.revert()
  mediaMatch = null
}

const setupReveal = async () => {
  const setupId = ++revealSetupId

  clearReveal()
  await nextTick()

  if (setupId !== revealSetupId) return

  const cards = gsap.utils.toArray(gridRef.value?.querySelectorAll('.watch-product-card') ?? [])

  if (!cards.length) return

  mediaMatch = gsap.matchMedia()

  mediaMatch.add('(prefers-reduced-motion: reduce)', () => {
    gsap.set(cards, { clearProps: 'all' })
  })

  mediaMatch.add('(prefers-reduced-motion: no-preference)', () => {
    const revealY = window.matchMedia('(max-width: 767px)').matches
      ? motionTokens.revealYMobile
      : motionTokens.revealYDesktop

    const triggers = ScrollTrigger.batch(cards, {
      start: 'top 86%',
      once: true,
      batchMax: 4,
      interval: 0.08,
      onEnter: (batch) => {
        gsap.fromTo(batch, {
          autoAlpha: 0,
          y: revealY,
        }, {
          autoAlpha: 1,
          y: 0,
          duration: motionTokens.durationReveal,
          ease: motionTokens.easeReveal,
          stagger: motionTokens.staggerSmall,
          overwrite: true,
        })
      },
    })

    ScrollTrigger.refresh()

    return () => triggers.forEach((trigger) => trigger.kill())
  })
}

watch(
  () => props.products,
  () => setupReveal(),
  { deep: true, immediate: true },
)

onBeforeUnmount(() => {
  clearReveal()
})
</script>

<template>
  <div
    ref="gridRef"
    class="grid w-full min-w-0"
    :class="variant === 'editorial'
      ? 'gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3'
      : variant === 'landing-dark'
        ? 'gap-6 sm:grid-cols-2 lg:grid-cols-4 xl:gap-8'
        : 'gap-6 sm:grid-cols-2 lg:grid-cols-4'"
  >
    <ProductCard
      v-for="product in products"
      :key="product.id ?? product.slug"
      :product="product"
      :variant="variant"
    />
  </div>
</template>
