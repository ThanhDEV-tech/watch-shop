<script setup>
import { formatCurrency } from '../utils/formatCurrency'

defineProps({
  course: { type: Object, required: true },
})
</script>

<template>
  <article class="glass-card flex w-full min-w-0 flex-col sm:flex-row gap-md p-md rounded-lg">
    <div class="w-full sm:w-48 h-32 rounded-lg overflow-hidden shrink-0 border border-outline-variant">
      <img class="w-full h-full object-cover" :src="course.thumbnail" :alt="course.title" />
    </div>

    <div class="flex w-full min-w-0 flex-1 flex-col justify-between py-xs">
      <div class="w-full min-w-0">
        <div class="flex w-full flex-wrap items-start justify-between gap-sm">
          <div class="min-w-0 flex-1">
            <h3 class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ course.title }}</h3>
            <p class="mt-1 w-full text-body-sm text-on-surface-variant">By {{ course.instructor }}</p>
          </div>
          <span class="shrink-0 rounded bg-surface-container-highest px-2 py-1 font-mono text-[10px] text-primary">{{ course.level }}</span>
        </div>

        <div class="mt-sm flex flex-wrap gap-xs">
          <span v-for="tag in course.tags" :key="tag" class="rounded border border-outline-variant bg-surface-container-highest px-2 py-0.5 font-mono text-[10px] text-on-surface-variant">{{ tag }}</span>
        </div>
      </div>

      <div class="mt-md flex w-full flex-wrap items-end justify-between gap-sm">
        <div class="flex min-w-0 items-center gap-xs text-tertiary">
          <span class="material-symbols-outlined filled-star text-[18px]">star</span>
          <span class="font-mono text-body-sm">{{ course.rating }}</span>
        </div>

        <div class="flex min-w-0 shrink-0 flex-col items-end">
          <span v-if="course.originalPrice !== course.price" class="text-body-sm text-on-surface-variant line-through">{{ formatCurrency(course.originalPrice) }}</span>
          <span class="font-mono text-headline-sm text-primary">{{ formatCurrency(course.price) }}</span>
        </div>
      </div>
    </div>
  </article>
</template>
