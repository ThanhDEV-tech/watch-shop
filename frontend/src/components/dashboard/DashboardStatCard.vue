<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: String, required: true },
  detail: { type: String, default: '' },
})
</script>

<template>
  <article class="glass-card flex w-full min-w-0 items-start justify-between gap-md rounded-lg p-md">
    <div class="w-full min-w-0">
      <p class="w-full text-body-sm text-on-surface-variant">{{ label }}</p>
      <p class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">{{ value }}</p>
      <p v-if="detail" class="mt-xs w-full text-xs text-on-surface-variant">{{ detail }}</p>
    </div>
    <span class="material-symbols-outlined shrink-0 rounded-lg bg-primary/10 p-sm text-primary">{{ icon }}</span>
  </article>
</template>
