<script setup>
defineProps({
  outcomes: { type: Array, required: true },
})
</script>

<template>
  <section class="glass-card w-full min-w-0 p-md rounded-xl">
    <h2 class="font-display text-headline-sm mb-md text-on-surface">What you'll learn</h2>
    <div class="grid w-full grid-cols-1 md:grid-cols-2 gap-sm">
      <div v-for="outcome in outcomes" :key="outcome" class="flex w-full min-w-0 gap-sm">
        <span class="material-symbols-outlined shrink-0 text-primary">check_circle</span>
        <span class="min-w-0 text-body-md text-on-surface-variant">{{ outcome }}</span>
      </div>
    </div>
  </section>
</template>
