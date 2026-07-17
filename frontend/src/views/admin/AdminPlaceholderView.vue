<script setup>
defineProps({
  title: { type: String, required: true },
  eyebrow: { type: String, default: 'Admin' },
  description: { type: String, default: '' },
})
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">{{ eyebrow }}</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">{{ title }}</h1>
      <p class="mt-xs w-full text-body-sm text-on-surface-variant">{{ description }}</p>
    </div>
  </main>
</template>
