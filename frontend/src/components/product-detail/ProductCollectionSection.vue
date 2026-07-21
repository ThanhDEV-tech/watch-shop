<script setup>
import { computed } from 'vue'
import ProductShowcaseGrid from '../ProductShowcaseGrid.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
})

const cardProducts = computed(() => props.products.slice(0, 3))
</script>

<template>
  <section v-if="cardProducts.length" class="bg-surface px-6 py-24 md:px-20 md:py-36 lg:px-[100px]">
    <div class="mx-auto grid w-full max-w-[1560px] gap-14 lg:grid-cols-[minmax(260px,0.32fr)_minmax(0,0.68fr)] lg:gap-20">
      <div class="flex w-full min-w-0 flex-col lg:sticky lg:top-28 lg:h-fit">
        <p class="w-full font-body text-[10px] font-medium uppercase tracking-[0.32em] text-primary/45">
          Curated selection
        </p>
        <h2 class="mt-5 w-full font-display text-[clamp(3rem,7vw,5.25rem)] font-normal leading-[0.95] text-primary">
          The Collection
        </h2>
        <p class="mt-7 w-full max-w-sm font-body text-base font-light leading-8 text-secondary">
          A quiet edit of related pieces chosen to sit naturally beside this watch.
        </p>
        <RouterLink
          to="/products"
          class="mt-10 inline-flex min-h-10 w-fit max-w-full items-center justify-center border border-primary/25 px-5 font-body text-[10px] font-medium uppercase tracking-[0.24em] text-primary transition-colors hover:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 motion-reduce:transition-none"
        >
          View All
        </RouterLink>
      </div>

      <ProductShowcaseGrid
        :products="cardProducts"
        variant="editorial"
      />
    </div>
  </section>
</template>
