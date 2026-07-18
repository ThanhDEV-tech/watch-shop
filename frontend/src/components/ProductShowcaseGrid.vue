<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import ProductCard from './ProductCard.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
})

gsap.registerPlugin(ScrollTrigger)

let mediaMatch
const gridRef = ref(null)

const clearReveal = () => {
  mediaMatch?.revert()
  mediaMatch = null
}

const setupReveal = async () => {
  clearReveal()
  await nextTick()

  const cards = gsap.utils.toArray(gridRef.value?.querySelectorAll('.watch-product-card') ?? [])

  if (!cards.length) return

  mediaMatch = gsap.matchMedia()

  mediaMatch.add('(prefers-reduced-motion: reduce)', () => {
    gsap.set(cards, { clearProps: 'all' })
  })

  mediaMatch.add('(prefers-reduced-motion: no-preference)', () => {
    gsap.set(cards, { autoAlpha: 0, y: 24 })

    const triggers = ScrollTrigger.batch(cards, {
      start: 'top 86%',
      once: true,
      batchMax: 4,
      interval: 0.08,
      onEnter: (batch) => {
        gsap.to(batch, {
          autoAlpha: 1,
          y: 0,
          duration: 0.55,
          ease: 'power2.out',
          stagger: 0.07,
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
  <div ref="gridRef" class="grid w-full min-w-0 gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <ProductCard
      v-for="product in products"
      :key="product.id ?? product.slug"
      :product="product"
    />
  </div>
</template>
