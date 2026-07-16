<script setup>
const brands = ['GOOGLE', 'NETLIFY', 'STRIPE', 'POSTMAN', 'GITHUB']
</script>

<template>
  <section class="bg-background border-t border-surface-variant py-lg overflow-hidden font-body">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter mb-base">
      <p class="text-center font-mono text-label-mono text-on-surface-variant uppercase tracking-widest">Powering engineering teams at</p>
    </div>
    <div class="flex whitespace-nowrap overflow-hidden relative">
      <div class="flex min-w-0 shrink-0 gap-xl animate-marquee items-center grayscale opacity-40 hover:grayscale-0 hover:opacity-100 transition-all">
        <template v-for="copy in 2" :key="copy">
          <span v-for="brand in brands" :key="`${copy}-${brand}`" class="text-on-surface text-2xl font-display font-bold px-lg">{{ brand }}</span>
        </template>
      </div>
    </div>
  </section>
</template>
