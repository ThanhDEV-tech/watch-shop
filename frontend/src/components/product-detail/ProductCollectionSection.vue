<script setup>
import { computed } from 'vue'
import { formatCurrency } from '../../utils/formatCurrency'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
})

const cardProducts = computed(() => props.products.slice(0, 3))

const imageFor = (product) => {
  const image = product.product_images?.[0]?.image_path
    ?? product.images?.[0]?.image_path
    ?? product.images?.[0]?.url
    ?? product.thumbnail
    ?? product.thumbnail_url
    ?? product.image_url
    ?? product.image

  return image || '/vite.svg'
}

const priceFor = (product) => (
  product.min_final_price ? `từ ${formatCurrency(product.min_final_price)}` : 'Liên hệ'
)
</script>

<template>
  <section v-if="cardProducts.length" class="bg-surface px-6 py-24 md:px-20 md:py-40 lg:px-[100px]">
    <div class="mx-auto w-full max-w-[1560px]">
      <div class="mb-14 flex w-full min-w-0 items-baseline justify-between gap-8 md:mb-16">
        <h2 class="w-full font-display text-[clamp(2.8rem,5vw,4rem)] font-normal leading-tight text-primary">
          The Collection
        </h2>
        <RouterLink
          to="/products"
          class="shrink-0 border border-primary/25 px-4 py-2 font-body text-xs font-medium uppercase tracking-[0.2em] text-primary transition-colors hover:border-primary"
        >
          View All
        </RouterLink>
      </div>

      <div class="grid w-full min-w-0 grid-cols-1 gap-10 md:grid-cols-3 md:gap-7">
        <RouterLink
          v-for="product in cardProducts"
          :key="product.id ?? product.slug"
          :to="`/products/${product.slug}`"
          class="group block w-full min-w-0"
        >
          <div class="aspect-[3/4] w-full overflow-hidden bg-surface-container-low">
            <img
              :src="imageFor(product)"
              :alt="product.name"
              class="h-full w-full object-contain p-1 opacity-95 transition-[opacity,transform] duration-700 group-hover:scale-[1.025] group-hover:opacity-100"
              loading="lazy"
            >
          </div>
          <div class="mt-7 w-full min-w-0 text-center">
            <p class="w-full truncate font-body text-[11px] font-medium uppercase tracking-[0.24em] text-primary/42">
              {{ product.brand?.name ?? priceFor(product) }}
            </p>
            <h3 class="mt-3 w-full font-display text-[clamp(1.8rem,2.5vw,2.5rem)] font-normal italic leading-tight text-primary">
              {{ product.name }}
            </h3>
          </div>
        </RouterLink>
      </div>
    </div>
  </section>
</template>
