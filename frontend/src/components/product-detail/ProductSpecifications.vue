<script setup>
defineProps({
  specs: {
    type: Array,
    default: () => [],
  },
  intro: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <section v-if="specs.length" class="bg-white px-6 py-28 md:px-20 md:py-44 lg:px-[100px]">
    <div class="mx-auto w-full max-w-6xl">
      <div class="mb-24 w-full text-center md:mb-28">
        <h2 class="w-full font-display text-[clamp(3.4rem,6vw,5rem)] font-normal leading-tight text-primary">
          Specifications
        </h2>
        <p v-if="intro" class="mx-auto mt-7 w-full max-w-3xl font-body text-lg leading-9 text-secondary">
          {{ intro }}
        </p>
      </div>

      <dl class="grid w-full min-w-0 grid-cols-1 gap-x-32 md:grid-cols-2">
        <div
          v-for="item in specs"
          :key="item.key"
          class="watch-spec-item flex w-full min-w-0 justify-between gap-10 border-b border-outline-variant/30 py-10"
        >
          <dt class="w-full min-w-0 font-body text-xs font-medium uppercase tracking-[0.22em] text-secondary">
            {{ item.label }}
          </dt>
          <dd class="w-full min-w-0 break-words text-right font-body text-[17px] leading-8 text-primary md:text-[19px]">
            {{ item.value }}
          </dd>
        </div>
      </dl>
    </div>
  </section>
</template>
