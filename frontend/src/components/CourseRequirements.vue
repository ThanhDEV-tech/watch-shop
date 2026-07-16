<script setup>
defineProps({
  requirements: { type: Array, default: () => [] },
})
</script>

<template>
  <section class="glass-card w-full min-w-0 rounded-xl p-md">
    <div class="w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Before you begin</p>
      <h2 class="mt-xs w-full font-display text-headline-sm font-semibold text-on-surface">Yêu cầu</h2>
    </div>

    <ul class="mt-md grid w-full min-w-0 grid-cols-1 gap-sm md:grid-cols-2">
      <li
        v-for="requirement in requirements"
        :key="requirement"
        class="flex w-full min-w-0 items-start gap-sm rounded-lg border border-surface-variant bg-surface-container-low p-sm text-body-sm text-on-surface-variant"
      >
        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[18px] text-primary">task_alt</span>
        <span class="min-w-0 flex-1">{{ requirement }}</span>
      </li>
    </ul>
  </section>
</template>
